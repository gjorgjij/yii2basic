<?php

namespace app\controllers;

use MongoDB\BSON\Timestamp;
use yii\db\Query;
use yii\web\Controller;
use app\models\Tomproject;
use app\models\Tomtask;
use app\models\Tomreport;

class TomController extends Controller
{

    /**
     * Get Duration for given project and update third column --> Duration = end_time-start_time
     * @param $projectId
     */
    public function getDuration($projectId)
    {
        //check if project is finished, if yes then calculate else show RUNNING
        $durationSeconds = 0;
        $duration = null;

        $project = Tomproject::find()->where(['id' => $projectId])->one();
        if ($project->progress_bar == 100) {

            $taskQuery = new Query();
            $taskQuery->select('start_date, end_date')
                ->from('tomtask')
                ->where('tomtask.project_id=:projectId', array(':projectId' => $projectId));

            $taskDates = $taskQuery->all();

            foreach ($taskDates as $taskDate) {
                $startUnix = strtotime($taskDate['start_date']);
                $endUnix = strtotime($taskDate['end_date']);
                $durationSeconds += ($endUnix - $startUnix);
                $duration = gmdate('z\d H:i:s\h', $durationSeconds);
            }

        } else {
            \Yii::$app->language = 'ru';
            return \Yii::t('app', "RUNNING");
        }
        return $duration;
    }

    /**
     * Update column "completed" in table "tomtask"
     * @param $taskId
     * @param $status
     */
    public function updateTaskStatus($taskId, $status)
    {
        $task = Tomtask::find()->where(['id' => $taskId])->one();
        $task->completed = $status;
        $task->save();
    }

    /**
     * Update column "progress_bar" in table "tomproject" for given projectId
     * @param $projectId
     * @param $projectProgress
     */
    public function updateProjectProgress($projectId, $projectProgress)
    {
        $project = Tomproject::find()->where(['id' => $projectId])->one();
        if ($project->progress_bar < $projectProgress) {
            $project->progress_bar = $projectProgress;
            $project->save();
        }
    }

    /**
     * Check all reports for taskId, if SUM(reports.percent_done)/total_number_of_reports ==> UpdateTaskStatus()
     * @param $taskId
     */
    public function taskPercentage($taskId)
    {
        $taskPercentage = 0;
        $reportQuery = new Query();
        $reportQuery->select('COUNT(*) as count, SUM(percent_done) as sum')
            ->from('tomreport')
            ->where('tomreport.task_id=:taskId', array(':taskId' => $taskId));

        $reportPercentages = $reportQuery->all();

        $sumReports = $reportPercentages[0]['sum'];
        $allReports = $reportPercentages[0]['count'];
        if ($sumReports == null) {
            $sumReports = 100; // if task exists but has no reports
            $allReports = 1;
        }
        if ($allReports != null && $allReports != 0) {
            $taskPercentage = $sumReports / $allReports;
        }

        if ($taskPercentage == 100) {
            $this->updateTaskStatus($taskId, 1);
        }
    }

    /**
     * Calculate finished percentage for projectId, COUNT(completed tasks)/total_number_of_tasks, if finished calculate duration
     * @param $projectId
     * @return array
     */
    public function projectProgress($projectId)
    {
        $taskQuery = new Query();
        $taskQuery->select('id')
            ->from('tomtask')
            ->where('tomtask.project_id=:projectId', array(':projectId' => $projectId));

        $taskIds = $taskQuery->all();
        foreach ($taskIds as $taskId) {
            $this->taskPercentage(intval($taskId["id"]));
        }
        $allTasks = $taskQuery->count();

        $completedTasks = new Query();
        $completedTasks->select('completed')
            ->from('tomtask')
            ->where('tomtask.project_id=:projectId AND tomtask.completed=1', array(':projectId' => $projectId));
        $countCompletedTasks = $completedTasks->count();

        $percentage = floor(($countCompletedTasks / $allTasks) * 100);
        $this->updateProjectProgress($projectId, $percentage);
        $duration = $this->getDuration($projectId);

        $percentageDurationPerProjectId = array($percentage, $duration);

        return $percentageDurationPerProjectId;
    }

    //render frontend data
    public function actionIndex()
    {
        $tom_project = Tomproject::find();
        $tom_task = Tomtask::find();
        $tom_report = Tomreport::find();

        $projectRes = $tom_project->orderBy('id')->all();
        $taskRes = $tom_task->orderBy('id')->all();
        $reportRes = $tom_report->orderBy('id')->all();

        $progressProjects = [];
        foreach ($projectRes as $project) {
            array_push($progressProjects, $this->projectProgress($project->id));
        }

        return $this->render('index', [
            'tomproject' => $projectRes,
            'tomtask' => $taskRes,
            'tomreport' => $reportRes,
            'progressProjects' => $progressProjects
        ]);
    }
}