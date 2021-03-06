<?php

namespace auth\models;

use Yii;
use yii\base\Model;
use auth\models\User;

/**
 * LoginForm is the model behind the login form.
 */
class LoginForm extends Model
{
	public $username;
	public $password;
	public $rememberMe = true;
	public $verifyCode;

	private $_user = false;

	/**
	 * @return array the validation rules.
	 */
	public function rules()
	{
		return [
			// username and password are both required
			[['username', 'password'], 'required'],
			// password is validated by validatePassword()
			['password', 'validatePassword'],
			// rememberMe must be a boolean value
			['rememberMe', 'boolean'],
			['verifyCode', 'captcha', 'captchaAction' => 'auth/default/captcha', 'on' => 'withCaptcha'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'username' => Yii::t('auth.user', 'Usuario o correo electrónico'),
			'password' => Yii::t('auth.user', 'Contraseña'),
			'rememberMe' => Yii::t('auth.user', 'Recordarme'),
			'verifyCode' => Yii::t('auth.user', 'Código de Verificación'),
		];
	}


	/**
	 * Validates the password.
	 * This method serves as the inline validation for password.
	 */
	public function validatePassword()
	{
		$user = $this->getUser();
		if (!$user || !$user->validatePassword($this->password)) {
			$this->addError('password', Yii::t('auth.user', 'Nombre de usuario o contraseña erronea.'));
		}
	}

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login()
    {
        global $user, $auth;

        if ($this->validate()) {
            // Start session management
            $user->session_begin();
            $auth->acl($user->data);
            $user->data['user_lang'] = 'en';
            $user->data['user_style'] = '1';
            $_SERVER['language'] = 'en';
            $user->setup();

            $_SESSION['USERNAME_AUTH'] = $this->username;

            $result = $auth->login($this->username, $this->password);

            return $this->getUser()->login($this->rememberMe ? Yii::$app->getModule('auth')->rememberMeTime : 0);
        } else {
            return false;
        }
    }

	/**
	 * Finds user by [[username]]
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		if ($this->_user === false) {
			$this->_user = User::findByUsername($this->username);
		}
		return $this->_user;
	}
}
