<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller;

use app\common\exception\AccessDeniedException;
use support\Request;

class ProjectKanban extends Base
{

    public function add(Request $request, $uuid)
    {
        $kanbanUuid = $request->post('kanban_uuid');
        $user = $this->getUser($request);

        $associte = $this->getKanbanModule()->associateToProject($kanbanUuid, $uuid, $user['id']);

        return $this->json($associte);
    }

    public function delete(Request $request, $uuid)
    {
        $kanbanUuid = $request->post('kanban_id', '');
        $user = $this->getUser($request);
        
        $removed = $this->getProjectModule()->rmKanban($uuid, $kanbanUuid, $user['id']);

        return $this->json($removed);
    }

    public function get(Request $request, $uuid)
    {
        
    }

    public function search(Request $request, $uuid)
    {
        $user = $this->getUser($request);
        if (!$this->getProjectModule()->isMember($uuid, $user['id'])) {
            throw new AccessDeniedException();
        }
        $kanbans = $this->getProjectModule()->searchKanbans($uuid, ['uuid', 'name']);
        
        return $this->json($kanbans);
    }

}
