<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var auth\models\UserSearch $searchModel
 */

$this->title = Yii::t('auth.user', 'Usuarios');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

	<h1><?= Html::encode($this->title) ?></h1>

	<?php //echo $this->render('_search', ['model' => $searchModel]); ?>

	<p>
		<?= Html::a('<i class="glyphicon glyphicon-plus-sign"></i> ' . Yii::t('auth.user', 'Dar de alta un usuario'), ['create'], ['class' => 'btn btn-success']) ?>
	</p>

	<?php echo GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => [
			//['class' => 'yii\grid\SerialColumn'],
			//'id',
			'username',
			'email:email',
			//'password_hash',
			//'password_reset_token',
			// 'auth_key',
			[
				'attribute' => 'status',
				'value' => function ($model) {
						return $model->getStatus();
					}
			],
			'last_visit_time',
			// 'create_time',
			// 'update_time',
			// 'delete_time',

			//['class' => 'yii\grid\ActionColumn'],
			[
                'attribute' => 'Acciones',
                'value' => function( $model ){
                    $repayment_button = '<a href="' . Url::to(['/user/view', 'id' => $model->id, 'time' => time()]) . '" title="View" data-pjax="0"><span class="glyphicon glyphicon-eye-open"></span></a>';
                    $repayment_button .= '<a href="' . Url::to(['/user/update', 'id' => $model->id, 'time' => time()]) . '" title="Update" data-pjax="0"><span class="glyphicon glyphicon-pencil"></span></a>';
                    $repayment_button .= '<a href="' . Url::to(['/user/delete', 'id' => $model->id, 'time' => time()]) . '" title="Delete" data-confirm="¿Está seguro de eliminar este elemento?" data-method="post" data-pjax="0"><span class="glyphicon glyphicon-trash"></span></a>';
                    return $repayment_button;
                },
                'format' => 'raw'
            ]
		],
	]); ?>

</div>
