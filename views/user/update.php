<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var auth\models\User $model
 */

$this->title = Yii::t('auth.user', 'Actualizar Usuario') . ': ' . $model->username;
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->username, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('auth.user', 'Actualizar');
?>
<div class="user-update">

	<h1><?= Html::encode($this->title) ?></h1>

	<?php echo $this->render('_form', [
		'model' => $model,
	]); ?>

</div>
