<?php

namespace auth\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "User".
 *
 * @property integer $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $auth_key
 * @property integer $status
 * @property string $last_visit_time
 * @property string $create_time
 * @property string $update_time
 * @property string $delete_time
 *
 * @property ProfileFieldValue $profileFieldValue
 */
class User extends ActiveRecord implements IdentityInterface
{
	const STATUS_DELETED = 0;
	const STATUS_INACTIVE = 1;
	const STATUS_ACTIVE = 2;
	const STATUS_SUSPENDED = 3;

	/**
	 * @var string the raw password. Used to collect password input and isn't saved in database
	 */
	public $password;

	/**
	 * Nuevo registro
	 * @var boolean
	 */
	public $asNewRecord = false;

	/**
	 * Rol del usuario
	 * @var String
	 */
	public $roleName;

	private $_isSuperAdmin = null;

	private $statuses = [
		self::STATUS_DELETED => 'Borrado',
		self::STATUS_INACTIVE => 'Inactivo',
		self::STATUS_ACTIVE => 'Activo',
		self::STATUS_SUSPENDED => 'Suspendido',
	];

	public function behaviors()
	{
		return [
			'timestamp' => [
				'class' => 'yii\behaviors\TimestampBehavior',
				'attributes' => [
					self::EVENT_BEFORE_INSERT => ['create_time', 'update_time'],
					self::EVENT_BEFORE_DELETE => 'delete_time',
				],
				'value' => function () {
					return new Expression('CURRENT_TIMESTAMP');
				}
			],
		];
	}

	public function getStatus($status = null)
	{
		if ($status === null) {
			return Yii::t('auth.user', $this->statuses[$this->status]);
		}
		return Yii::t('auth.user', $this->statuses[$status]);
	}

	/**
	 * Finds an identity by the given ID.
	 *
	 * @param string|integer $id the ID to be looked for
	 * @return IdentityInterface|null the identity object that matches the given ID.
	 */
	public static function findIdentity($id)
	{
		return static::findOne($id);
	}

	/**
	 * Finds user by username
	 *
	 * @param string $username
	 * @return null|User
	 */
	public static function findByUsername($username)
	{
		return static::find()
					 ->andWhere(['and', ['or', ['username' => $username], ['email' => $username]], ['status' => static::STATUS_ACTIVE]])
					 ->one();
	}

	/**
	 * @inheritdoc
	 */
	public static function findIdentityByAccessToken($token, $type = null)
	{
		throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
	}

	/**
	 * Finds user by password reset token
	 *
	 * @param string $token password reset token
	 * @return static|null
	 */
	public static function findByPasswordResetToken($token)
	{
		$expire = Yii::$app->getModule('auth')->passwordResetTokenExpire;
		$parts = explode('_', $token);
		$timestamp = (int)end($parts);
		if ($timestamp + $expire < time()) {
			// token expired
			return null;
		}

		return static::findOne([
			'password_reset_token' => $token,
			'status' => self::STATUS_ACTIVE,
		]);
	}

	/**
	 * @return int|string current user ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string current user auth key
	 */
	public function getAuthKey()
	{
		return $this->auth_key;
	}

	/**
	 * @param string $authKey
	 * @return boolean if auth key is valid for current user
	 */
	public function validateAuthKey($authKey)
	{
		return $this->auth_key === $authKey;
	}

	/**
	 * @param string $password password to validate
	 * @return bool if password provided is valid for current user
	 */
	public function validatePassword($password)
	{
		return Yii::$app->getSecurity()->validatePassword($password, $this->password_hash);
	}

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return Yii::$app->getModule('auth')->tableMap['User'];
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['status', 'default', 'value' => static::STATUS_ACTIVE, 'on' => 'signup'],
			['status', 'safe'],
			['username', 'filter', 'filter' => 'trim'],
			['username', 'required', 'message' => Yii::t('auth.user', '"{attribute}" requerido.')],
			['username', 'unique', 'message' => Yii::t('auth.user', 'El nombre de usuario ya esta en uso.')],
			[['username'], 'string_max', 'params' => ['min' => 3, 'max' => 100]],

			['email', 'filter', 'filter' => 'trim'],
			['email', 'required', 'message' => Yii::t('auth.user', '"{attribute}" requerido.')],
			['email', 'email', 'message' => Yii::t('auth.user', 'La dirección de correo es inválida.')],
			['email', 'unique', 'message' => Yii::t('auth.user', 'El correo electrónico ya esta siendo usado por otro usuario.')],
			['email', 'exist', 'message' => Yii::t('auth.user', 'No existe un usuario con este correo asignado.'), 'on' => 'requestPasswordResetToken'],

			['roleName', 'required', 'message' => Yii::t('auth.user', '"{attribute}" requerido.')],
			[['roleName'], 'exist', 'targetClass' => 'app\models\AuthItem', 'targetAttribute' => 'name', 'message' => Yii::t('app', '{attribute} inválido')],

			['password', 'required', 'on' => 'signup'],
			[['password'], 'string_max', 'params' => ['min' => 3, 'max' => 100]],
			['password', 'required', 'message' => Yii::t('auth.user', '"{attribute}" requerido.')],

		];
	}

	public function scenarios()
	{
		return [
			'signup' => ['username', 'email', 'password'],
			'profile' => ['username', 'email', 'password'],
			'resetPassword' => ['password'],
			'requestPasswordResetToken' => ['email'],
			'login' => ['last_visit_time'],
		] + parent::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'username' => Yii::t('auth.user', 'Nombre de usuario'),
			'email' => Yii::t('auth.user', 'Correo'),
			'password' => Yii::t('auth.user', 'Contraseña'),
			'password_hash' => Yii::t('auth.user', 'Contraseña cifrada'),
			'password_reset_token' => Yii::t('auth.user', 'Token'),
			'auth_key' => Yii::t('auth.user', 'Clave de autenticación'),
			'status' => Yii::t('auth.user', 'Estado'),
			'last_visit_time' => Yii::t('auth.user', 'Último login'),
			'create_time' => Yii::t('auth.user', 'Fecha de Creación'),
			'update_time' => Yii::t('auth.user', 'Fecha de Actualización'),
			'delete_time' => Yii::t('auth.user', 'Fecha de Borrado'),
			'roleName' => 'Permiso'
		];
	}

	/**
	 * @return \yii\db\ActiveRelation
	 */
	public function getProfileFieldValue()
	{
		return $this->hasOne(ProfileFieldValue::className(), ['id' => 'user_id']);
	}

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if (Yii::$app->getModule('auth')->signupWithEmailOnly) {
                $this->username = $this->email;
            }

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (($this->isNewRecord || in_array($this->getScenario(), ['resetPassword', 'profile'])) && !empty($this->password)) {
                $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($this->password);
            }
            if ($this->isNewRecord) {
                $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
            }
            if ($this->getScenario() !== \yii\web\User::EVENT_AFTER_LOGIN) {
                $this->setAttribute('update_time', new Expression('CURRENT_TIMESTAMP'));
            }

            if ($this->isNewRecord) {
                // Creamos el usuario del foro.
                $user_actkey = md5(rand(0, 100) . time());
                $user_actkey = substr($user_actkey, 0, rand(8, 12));

                $user_row = array(
                    'username' => $this->username,
                    'user_password' => phpbb_hash($this->password),
                    'user_email' => $this->email,
                    'group_id' => (int)4,
                    'user_timezone' => (float)-6,
                    'user_lang' => 'en',
                    'user_type' => 0,
                    'user_actkey' => $user_actkey,
                    'user_ip' => request_var('REMOTE_ADDR', ''),
                    'user_regdate' => time(),
                    'user_dateformat' => 'D M d, Y g:i a'
                );

                // El identificador nos puede servir para almacenarlo en el oauth del foro
                $user_id = user_add($user_row);
            }

            return true;
        }
        return false;
    }

	 /**
     * Valida que el campo solo contenga n caracteres como minimo y n
     * caracteres como máximo
     * @param  string $attribute
     * @param  array $params
     * @return
     */
    public function string_max($attribute, $params) {
        // Si es nulo entonces no hay limite
        $min = null;
        $max = null;

        if (array_key_exists('min', $params)) {
            $min = intval($params['min']);
        }

        if (array_key_exists('max', $params)) {
            $max = intval($params['max']);
        }

        // Tamaño de la cadena
        $long = strlen($this->$attribute);

        if ( ($min !== null & $long < $min) ) {
            $this->addError($attribute, Yii::t('auth.user', '"{attribute}" requiere como
                minimo {min} caracteres y como máximo {max} caracteres.', ['attribute' => $this->attributeLabels()[$attribute], 'min' => $min, 'max' => $max]));
            return;
        }

        if ( ($max !== null & $long > $max) ) {
            $this->addError($attribute, Yii::t('auth.user', '"{attribute}" requiere como
                minimo {min} caracteres y como máximo {max} caracteres.', ['attribute' => $this->attributeLabels()[$attribute], 'min' => $min, 'max' => $max]));
            return;
        }
    }

	public function delete()
	{
		$db = static::getDb();
		$transaction = $this->isTransactional(self::OP_DELETE) && $db->getTransaction() === null ? $db->beginTransaction() : null;
		try {
			$result = false;
			if ($this->beforeDelete()) {
				$this->setAttribute('status', static::STATUS_DELETED);
				$this->save(false);
			}
			if ($transaction !== null) {
				if ($result === false) {
					$transaction->rollback();
				} else {
					$transaction->commit();
				}
			}
		} catch (\Exception $e) {
			if ($transaction !== null) {
				$transaction->rollback();
			}
			throw $e;
		}
		return $result;
	}

	/**
	 * Returns whether the logged in user is an administrator.
	 *
	 * @return boolean the result.
	 */
	public function getIsSuperAdmin()
	{
		if ($this->_isSuperAdmin !== null) {
			return $this->_isSuperAdmin;
		}

		$this->_isSuperAdmin = in_array($this->username, Yii::$app->getModule('auth')->superAdmins);
		return $this->_isSuperAdmin;
	}

	public function login($duration = 0)
	{
		return Yii::$app->user->login($this, $duration);
	}

	/**
	 * Generates new password reset token
	 */
	public function generatePasswordResetToken()
	{
		$this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
	}

	/**
	 * Removes password reset token
	 */
	public function removePasswordResetToken()
	{
		$this->password_reset_token = null;
	}

    /**
     * This method is called at the end of inserting or updating a record.
     * The default implementation will trigger an [[EVENT_AFTER_INSERT]] event when `$insert` is true,
     * or an [[EVENT_AFTER_UPDATE]] event if `$insert` is false. The event class used is [[AfterSaveEvent]].
     * When overriding this method, make sure you call the parent implementation so that
     * the event is triggered.
     * @param boolean $insert whether this method called while inserting a record.
     * If false, it means the method is called while updating a record.
     * @param array $changedAttributes The old values of attributes that had changed and were saved.
     * You can use this parameter to take action based on the changes made for example send an email
     * when the password had changed or implement audit trail that tracks all the changes.
     * `$changedAttributes` gives you the old attribute values while the active record (`$this`) has
     * already the new, updated values.
     */
    public function afterSave($insert, $changedAttributes)
    {
    	if ($this->asNewRecord && $this->roleName != null) {
	    	$auth = Yii::$app->authManager;

	    	$role = $auth->getRole($this->roleName);

	    	$auth->assign($role, $this->id);
	    }

    	return parent::afterSave($insert, $changedAttributes);
    }


    /**
     * @inheritdoc
     * @return CommentQuery
     */
    public static function find()
    {
        return new \app\models\UserQuery(get_called_class());
    }



    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRoles() {
        return $this->hasMany( \app\models\AuthAssignment::className(), [ 'user_id' => 'id' ] );
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRole() {
        return $this->hasOne( \app\models\AuthAssignment::className(), [ 'user_id' => 'id' ] );
    }



    /**
     * This method is called when the AR object is created and populated with the query result.
     * The default implementation will trigger an [[EVENT_AFTER_FIND]] event.
     * When overriding this method, make sure you call the parent implementation to ensure the
     * event is triggered.
     */
    public function afterFind()
    {
    	#$this->roleName = $this->role->item_name;

        return parent::afterFind();
    }
}
