<?php

use cornernote\workflow\manager\models\Transition;
use dmstr\helpers\Html;
use yii\helpers\Url;
use yii\jui\Sortable;
use yii\web\JsExpression;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var cornernote\workflow\manager\models\Workflow $model
 */
$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('workflow', 'Workflow'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="workflow-default-view">

    <h1>
        <?= Html::encode($this->title) ?>
        <div class="pull-right">
            <?= Html::a('<span class="glyphicon glyphicon-pencil"></span> ' . Yii::t('workflow', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-info']) ?>
            <?= Html::a('<span class="glyphicon glyphicon-trash"></span> ' . Yii::t('workflow', 'Delete'), ['delete', 'id' => $model->id], [
                'class' => 'btn btn-danger',
                'data-confirm' => '' . Yii::t('workflow', 'Are you sure?') . '',
                'data-method' => 'post',
            ]) ?>
        </div>
    </h1>

    <?php
    $sortables = [];
    foreach ($model->statuses as $status) {
        $actions = [];
        $actions[] = '<span class="glyphicon glyphicon-move sortable-handle" style="cursor: move"></span>';
        if ($model->initial_status_id != $status->id) {
            $actions[] = Html::a('<span class="glyphicon glyphicon-star"></span>', ['initial', 'id' => $model->id, 'status_id' => $status->id], ['title' => Yii::t('workflow', 'Set Initial')]);
        }
        $actions[] = Html::a('<span class="glyphicon glyphicon-pencil"></span>', ['status/update', 'id' => $status->id], ['title' => Yii::t('workflow', 'Update')]);
        $actions[] = Html::a('<span class="glyphicon glyphicon-trash"></span>', ['status/delete', 'id' => $status->id], [
            'title' => Yii::t('workflow', 'Delete'),
            'data-confirm' => '' . Yii::t('workflow', 'Are you sure?') . '',
            'data-method' => 'post',
        ]);

        $transitions = [];
        foreach ($status->startTransitions as $transition) {
            $transitions[] = $transition->endStatus->name;
        }
        $transitions = !empty($transitions) ? '<br><small><span class="glyphicon glyphicon-chevron-right"></span> ' . implode(', ', $transitions) . '</small>' : '';

        $sortables[] = [
            'content' => '<div class="pull-right">' . implode(' ', $actions) . '</div>' . $status->name . $transitions,
            'options' => [
                'id' => 'Status_' . $status->id,
                'class' => 'list-group-item',
            ],
        ];
    }
    echo DetailView::widget([
        'model' => $model,
        'attributes' => [
            'name',
            [
                'attribute' => 'initial_status_id',
                'value' => $model->initialStatus ? $model->initialStatus->name : null,
            ],
            [
                'label' => Yii::t('workflow', 'Status') . '<br>' . Html::a(Yii::t('workflow', 'Create Status'), ['status/create', 'workflow_id' => $model->id], ['class' => 'btn btn-success btn-xs']),
                'value' => Sortable::widget([
                    'items' => $sortables,
                    'options' => [
                        'class' => 'list-group',
                        'style' => 'margin-bottom:0;',
                    ],
                    'clientOptions' => [
                        'axis' => 'y',
                        'update' => new JsExpression("function(event, ui){
                                    $.ajax({
                                        type: 'POST',
                                        url: '" . Url::to(['status/sort']) . "',
                                        data: $(event.target).sortable('serialize') + '&_csrf=" . Yii::$app->request->getCsrfToken() . "',
                                        success: function() {
                                            location.reload();
                                        }
                                    });
                                }"),
                    ],
                ]),
                'format' => 'raw',
            ],
        ],
    ]);
    ?>

    <?php if ($model->statuses) { ?>
        <?= Html::beginForm(); ?>
        <h2><?= Yii::t('workflow', 'Transitions') ?></h2>
        <table class="table table-bordered table-condensed">
            <tr>
                <th colspan="2" rowspan="2"></th>
                <th class="text-center" colspan="<?= count($model->statuses) ?>"><?= Yii::t('workflow', 'End Status') ?></th>
            </tr>
            <tr>
                <?php foreach ($model->statuses as $endStatus) { ?>
                    <th class="text-center">
                        <?= $endStatus->name ?>
                    </th>
                <?php } ?>
            </tr>
            <?php foreach ($model->statuses as $k => $startStatus) { ?>
                <tr>
                    <?php if (!$k) { ?>
                        <th class="text-center" rowspan="<?= count($model->statuses) ?>"><?= Yii::t('workflow', 'Start Status') ?></th>
                    <?php } ?>
                    <th class="text-right"><?= $startStatus->name ?></th>
                    <?php foreach ($model->statuses as $endStatus) { ?>
                        <td class="text-center">
                            <?php
                            $options = ['uncheck' => 0];
                            if ($startStatus->id == $endStatus->id) {
                                unset($options['uncheck']);
                                $options['disabled'] = true;
                            }
                            $transition = Transition::findOne(['start_status_id' => $startStatus->id, 'end_status_id' => $endStatus->id]);
                            echo Html::checkbox('Status[' . $startStatus->id . '][' . $endStatus->id . ']', $transition ? true : false, $options);
                            ?>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
        </table>
        <?= Html::submitButton(Yii::t('workflow', 'Save'), ['class' => 'btn btn-success']) ?>
        <?= Html::endForm(); ?>
    <?php } ?>

</div>
