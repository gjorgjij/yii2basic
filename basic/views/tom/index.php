<?php

use yii\helpers\Html;

?>

<?php
foreach (Yii::$app->params['languages'] as $key => $language) {
    echo '<a class="language" href="'.$key.'">'.$language.'   </a>';
}
?>


<h1>Projects</h1>
<div class="container h-100">

    <?php foreach ($tomproject as $project): ?>
        <div class="row">
            <div class="col-sm-4">
                <div class="row table-bordered">
                    <?php $dataTarget = HTML::encode('#collapseTarget' . $project->id); ?>

                    <button class="btn" data-toggle="collapse" data-target="#collapseExample" type="button"
                            aria-expanded="true" aria-controls="$collapseExample">
                        <?= Html::encode(" {$project->name}") ?>
                    </button>
                    <ul id="collapseExample">
                        <?php foreach ($tomtask as $task) : ?>
                            <?php if ($task->project_id == $project->id) : ?>
                                <li>
                                    <?= Html::encode("{$task->name}") ?>
                                </li>
                                <ul>
                                    <?php foreach ($tomreport as $report) : ?>
                                        <?php if ($report->task_id == $task->id) : ?>
                                            <li>
                                                <?= Html::encode("{$report->name}") ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="col-sm-4">
                <?= yii\bootstrap\Progress::widget(['percent' => $progressProjects[$project->id - 1][0], 'label' => $progressProjects[$project->id - 1][0] . '%']) ?>
            </div>
            <div class="col-sm-4">Duration:
                <?= $progressProjects[$project->id - 1][1] ?>
            </div>

        </div>

    <?php endforeach; ?>
</div>

