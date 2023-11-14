<?php
namespace app\module;

use app\model\Task as TaskModel;

class Task extends BaseModule
{

    public function createTask()
    {

    }

    public function moveToFlow($taskUuid, $flowId)
    {

    }

    public function assignTo($taskUuid, $userUuid)
    {
        
    }

    public function groupTaskByFlowIds(array $flowIds, array $fields = ['*'])
    {
        $tasks = $this->getFlowsTasks($flowIds, $fields);

        $flowTasks = [];
        foreach ($tasks as $task) {
            if (!isset($flowTasks[$task['flow_id']])) {
                $flowTasks[$task['flow_id']] = [];
            }
            $flowTasks[$task['flow_id']][] = $task;
        }
        return $flowTasks;
    }

    public function getFlowsTasks(array $flowIds, array $fields = ['*'])
    {
        return TaskModel::whereIn('flow_id', $flowIds)->get($fields);
    }

}