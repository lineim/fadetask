<?php
namespace app\controller\admin;

use app\common\exception\AccessDeniedException;
use app\controller\Base;
use support\Request;
use app\model\User as UserModel;
use app\model\UserBind as UserBindModel;
use app\module\User as ModuleUser;
use stdClass;

class User extends Base
{
    public function search(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);

        
        $users = UserModel::offset(($page-1) * $pageSize)
            ->orderBy('id', 'DESC')
            ->limit($pageSize)
            ->get();
        $userIds = $users->pluck('id');

        $binds = UserBindModel::whereIn('user_id', $userIds)->get();
        $bindsArr = [];
        foreach ($binds as $bind) {
            $bindsArr[$bind->user_id] = $bind;
        }

        foreach ($users as &$user) {
            $user->type = 'normal';
            if (isset($bindsArr[$user->id])) {
                $user->type = $bindsArr[$user->id]->type;
            }
            $user->bind = $bindsArr[$user->id] ?? new stdClass;
        }
        $resp = [
            'users' => $users,
            'total' => UserModel::count(),
            'pageSize' => $pageSize,
            'page' => $page
        ];
        return $this->json($resp);
    }

    public function get(Request $request, $uuid)
    {
        $user = $this->getUserModule()->getByUuid($uuid);

        return $this->json($user);
    }

    public function add(Request $request)
    {
        $user = [
            'name' => $request->post('name'),
            'email' => $request->post('email'),
            'mobile' => $request->post('mobile'),
            'password' => $request->post('password'),
            'title' => $request->post('title'),
            'hired_time' => strtotime($request->post('hireDate')),
            'verified' => 1,
        ];

        $addResult = $this->getUserModule()->reg($user);
        if ($addResult) {
            return $this->json(true);
        }
        return $this->json(false);
    }

    public function checkEmail(Request $request)
    {
        $email = $request->post('email');
        $uuid = $request->post('uuid', 0);
        $available = false;
        if (!$uuid) {
            if ($this->getUserModule()->getByEmail($email, ['id'])) {
                $available = false;
            } else {
                $available = true;
            }
        } else {
            $available = $this->getUserModule()->emailAvailableForUpdate($uuid, $email);
        }
        return $this->json($available);
    }

    public function checkMobile(Request $request)
    {
        $mobile = $request->post('mobile');
        $uuid = $request->post('uuid', 0);
        $available = false;
        if (!$uuid) {
            if ($this->getUserModule()->getByMobile($mobile, ['id'])) {
                $available = false;
            } else {
                $available = true;
            }
        } else {
            $available = $this->getUserModule()->mobileAvailableForUpdate($uuid, $mobile);
        }
        return $this->json($available);
    }

    public function updateVerify(Request $request, $uuid)
    {
        if (!$this->isAdmin($request)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $verified = $request->post('verified', 1);
        if ($verified) {
            $success = $this->getUserModule()->markVerified($uuid);
        } else {
            $success = $this->getUserModule()->markUnverified($uuid);
        }
        return $this->json($success);
    }

    public function updatePassword(Request $request, $uuid)
    {
        if (!$this->isAdmin($request)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $password = $request->post('password', '');

        $success = $this->getUserModule()->updatePassword($uuid, $password);

        return $this->json($success);
    }

}
