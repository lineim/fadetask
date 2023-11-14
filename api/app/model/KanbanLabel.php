<?php
namespace app\model;

use support\Model;

class KanbanLabel extends Model
{

    protected $table = 'kanban_label';

    public $timestamps = false;

    public function sort($kanbanId, array $labelIds)
    {
        $kanbanId = (int) $kanbanId;
        if (!$labelIds) {
            return true;
        }
        $sql = "UPDATE $this->table SET sort =  CASE id " . PHP_EOL ;
        $tmpIds = [];
        foreach ($labelIds as $sort => $labelId) {
            $labelId = (int) $labelId;
            $tmpIds[] = $labelId;
            $sort = (int) $sort;
            $sql .= "WHEN {$labelId} THEN {$sort} " . PHP_EOL; 
        }
        $sql .= " END " . PHP_EOL;
        $sql .= " WHERE kanban_id = {$kanbanId} AND id IN (" . implode(',' , $tmpIds) . ")";
        return $this->getConnection()->update($sql);
    }
    
}