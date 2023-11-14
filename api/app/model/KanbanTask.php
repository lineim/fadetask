<?php
namespace app\model;

use support\Model;

class KanbanTask extends Model
{

    protected $table = 'kanban_task';

    public $timestamps = false;

    public function sortTasks($listId, array $tasksSort)
    {
        $listId = (int) $listId;
        if (!$tasksSort) {
            return true;
        }
        $sql = "UPDATE $this->table SET list_sort =  CASE id " . PHP_EOL ;
        $tmpIds = [];
        foreach ($tasksSort as $taskId => $sort) {
            $taskId = (int) $taskId;
            $tmpIds[] = $taskId;
            $sort = (int) $sort;
            $sql .= "WHEN {$taskId} THEN {$sort} " . PHP_EOL; 
        }
        $sql .= " END " . PHP_EOL;
        $sql .= " WHERE list_id = {$listId} AND id IN (" . implode(',' , $tmpIds) . ")";
        return $this->getConnection()->update($sql);
    }
    
}