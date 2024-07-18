<?php
/**
 * This file is part of fade task project.
 *
 * @author    lvshuang1201@gmail.com
 * @copyright lvshuang1201@gmail.com
 * @link      https://www.fadetask.com
 */
namespace app\controller\workspace;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\controller\Base;
use support\Request;

class WorkspaceMember extends Base
{
    public function get($uuid)
    {
        return $this->getWorkspaceModule()->getByUuid($uuid);
    }

    public function add(Request $request)
    {

    }

    public function list(Request $request, $uuid)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('query', 20);        
        $members = $this->getWorkspaceMemberModule()->getWorkspaceMembers($uuid, $page, $pageSize, ['uuid', 'name', 'email']);

        return $this->json($members);
    }

    public function put(Request $request, $uuid)
    {

    }

    public function invite(Request $request, $uuid)
    {
        $user = $this->getUser();
        $type = $request->get('type', 'link');
        $frontUrl = config('app.workspace_invite_url');
        $role = strtolower($request->get('role', 'member'));
        if (empty($frontUrl)) {
            throw new BusinessException('请配置工作空间邀请链接');
        }
        if ($type == 'link') {
            return $this->json(['link' => $this->getWorkspaceMemberModule()->inviteUrl($frontUrl, $user['id'], $uuid, $role)]);
        }
        return $this->json([], -1, 'Not support invite type');
    }

    public function join(Request $request)
    {
        $user = $this->getUser();
        $token = $request->post('token', $request->get('token', ''));
        try {
            $workspace = $this->getWorkspaceMemberModule()->joinByToken($user['id'], $token);
            if ($workspace) {
                $this->getUserModule()->changeUserCurrentWorkspace($user['id'], $workspace->id);
            }
            return $this->json(['success' => 1, 'workspace_uuid' => $workspace]);
        } catch (BusinessException $e) {
            return $this->json(['success' => 0, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delete($uuid, $memberId)
    {
        $user = $this->getUser();
        $this->getWorkspaceMemberModule()->deleteMember($uuid, $memberId, $user['id']);

        return $this->json([true]);
    }

    protected function checkWorkspaceAdminPermission($workspaceId, $userId)
    {
        if ($this->getWorkspaceModule()->hasAdminPermission($workspaceId, $userId)) {
            throw new AccessDeniedException();
        }
        return true;
    }

}
