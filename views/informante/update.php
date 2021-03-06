<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\mant\Informante */

$this->title = 'Actualizar Informante: ' . ' ' . $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Informantes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->codigo, 'url' => ['view', 'id' => $model->codigo]];
$this->params['breadcrumbs'][] = 'Actualizar';
?>
<div class="informante-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
