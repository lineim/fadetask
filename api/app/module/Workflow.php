<?php
namespace app\module;

use app\common\exception\ResourceNotFoundException;
use app\model\WorkflowGroup;
use app\model\Workflow as WorkflowModel;
use stdClass;

class Workflow extends BaseModule
{

    public function getFlowGroupById($groupId, array $fields = ['*'])
    {
        $group = WorkflowGroup::where('id', $groupId, $fields);
        if (!$group) {
            return false;
        }
        return $group;
    }

    public function getFlowGroupWithFlows($groupId, array $groupFields = ['*'], array $flowFields = ['*']) : stdClass
    {
        $group = $this->getFlowGroupById($groupId, $groupFields);
        if (!$group) {
            throw new ResourceNotFoundException('worker flow group not found');
        }
        $flows = $this->getFlowsByGroupId($groupId, $flowFields);
        $groupAndFlows = new stdClass;
        $groupAndFlows->group = $group;
        $groupAndFlows->flows = $flows;

        return $groupAndFlows;
    }

    public function getFlowsByGroupId($groupId, $fields = ['*'])
    {
        return WorkflowModel::where('group_id', $groupId)->orderBy('seq', 'asc')->get($fields);
    }

}