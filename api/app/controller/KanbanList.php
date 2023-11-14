<?php
namespace app\controller;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\module\Kanban as KanbanModule;
use support\Request;
use app\common\toolkit\ArrayHelper;

class KanbanList extends Base
{
    public function create(Request $request, $id)
    {
        $name = $request->post('name', '');
        $created = $this->getKanbanModule()->createList($id, $name);

        if ($created) {
            return $this->json(['success']);
        }
        return $this->json(['failed'], 500);
    }

    public function update(Request $request, $id, $listId)
    {
        $user = $this->getUser($request);
        $name = $request->post('name', '');

        $updated = $this->getKanbanModule()->changeListName($listId, $name, $user['id']);

        return $this->json($updated);
    }

    public function changeListSort(Request $request, $id)
    {
        $sortInfo = $request->post();
        if (!$sortInfo) {
            return $this->json(['post data error'], 500);
        }
        $user = $this->getUser($request);
        
        $r = $this->getKanbanModule()->kanbanListSort($id, $sortInfo, $user['id']);

        return $this->json($r);
    }

    public function archive(Request $request, $listId)
    {
        $user = $this->getUser($request);

        $archive = $this->getKanbanModule()->archiveList($listId, $user['id']);

        return $this->json($archive);
    }

    public function unarchive(Request $request, $listId)
    {
        $user = $this->getUser($request);

        $unarchive = $this->getKanbanModule()->unarchiveList($listId, $user['id']);
        
        return $this->json($unarchive);
    }

    public function archivedList(Request $request, $kanbanId)
    {
        $user = $this->getUser($request);
        $kanban = $this->getKanbanModule()->getByUuid($kanbanId, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if (!$this->getKanbanModule()->isAdmin($kanbanId, $user['id'])) {
            throw new AccessDeniedException('Access Denied!');
        }
        $list = $this->getKanbanModule()->getKanbanArchivedList($kanbanId);

        return $this->json($list);
    }

    public function completed(Request $request, $listId)
    {
        $user = $this->getUser($request);
        $this->getKanbanModule()->setListCompleted($listId, $user['id'], KanbanModule::IS_COMPLETED_LIST);

        return $this->json([], 0, '设置完成列成功');
    }

    public function cancelCompleted(Request $request, $listId)
    {
        $user = $this->getUser($request);
        $this->getKanbanModule()->setListCompleted($listId, $user['id'], KanbanModule::NOT_COMPLETED_LIST);

        return $this->json([], 0, '取消完成列成功');
    }

}