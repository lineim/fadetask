<?php
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\common\exception\ResourceNotFoundException;
use app\common\toolkit\Validator;
use app\module\Notification\Factory;

class KanbanMember extends Kanban
{
    const INVITE_TYPE_LINK = 'link';
    const INVITE_TYPE_EMAIL = 'email';

    public function invite($kanbanId, array $emails, $frontUrl, $operatorId)
    {
        foreach ($emails as $email) {
            if (!Validator::email($email)) {
                throw new BusinessException('invalid mail format ' . $email);
            }
        }

        if (!$this->isAdmin($kanbanId, $operatorId)) {
            throw new AccessDeniedException();
        }

        $notExistEmails = $this->getUserModule()->getNotExistEmails($emails);
        if ($notExistEmails) {
            throw new BusinessException(sprintf('邮箱 %s 不是系统用户', implode(', ', $notExistEmails)));
        }

        $hasBeenMemberEmails = $this->getHasBeenMemberEmails($kanbanId, $emails);
        $emails = array_diff($emails, $hasBeenMemberEmails);

        $operator = $this->getUserModule()->getByUserId($operatorId, ['name']);
        $kanban = $this->get($kanbanId, ['name', 'uuid']);

        $redis = $this->getStorageRedis();
        $token = $this->makeInviteToken($kanbanId, $operatorId);
        $expireAt = time() + 10*60;
        $tokenInfo = [
            'invite_type' => self::INVITE_TYPE_EMAIL,
            'emails' => json_encode($emails),
            'kanban_id' => $kanbanId,
            'kanban_uuid' => $kanban->uuid,
            'expire_at' => $expireAt
        ];
        $redis->hMSet($token, $tokenInfo);
        $redis->expireAt($token, $expireAt);

        $channel = Factory::getChannel('mail');
        $channel->sendBatchMsg(
            $emails, 
            'invite_join_kanban', 
            [
                'subject' => '邀请加入看板',
                'url' => $this->makeInviteUrl($frontUrl, $token, $kanban->uuid),
                'invitor' => $operator->name,
                'kanban' => $kanban->name,
            ]
        );

        return true;
    }

    public function joinByInvite($kanbanUUid, $userId, $token)
    {
        if (!$this->verifyInviteToken($token, $kanbanUUid, $userId)) {
            throw new BusinessException('Invalid Link!');
        }

        $kanban = $this->getByUuid($kanbanUUid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if ($this->isMember($kanbanId, $userId)) {
            return true;
        }
        return $this->joinKanban($kanbanId, $userId, self::MEMBER_ROLE_USER);
    }

    public function inviteUrlLink($kanbanUUid, $operator, $frontUrl)
    {
        $kanban = $this->getByUuid($kanbanUUid, ['id']);
        if (!$kanban) {
            throw new ResourceNotFoundException('kanban.not_found');
        }
        $kanbanId = $kanban->id;
        if (!$this->isAdmin($kanbanId, $operator)) {
            throw new AccessDeniedException();
        }
        $token = $this->makeInviteToken($kanbanUUid, $operator);
        $redis = $this->getStorageRedis();
        $expireAt = time() + 24*60;
        $tokenInfo = [
            'invite_type' => self::INVITE_TYPE_LINK,
            'kanban_uuid' => $kanbanUUid,
            'expire_at' => $expireAt
        ];
        $redis->hMSet($token, $tokenInfo);
        $redis->expireAt($token, $expireAt);

        return $this->makeInviteUrl($frontUrl, $token, $kanbanUUid);
    }

    protected function makeInviteToken($kanbanId, $makerId)
    {
        $strs = ['1', 'a', '@', '$', 'c', '?', '&', '>', '(', 'G', '*'];
        shuffle($strs);
        $salt = array_slice($strs, 0, 5);
        $salt = implode('', $salt);

        return hash('sha512', sprintf('invite:tk:%d%d%s%d', $kanbanId, $makerId, $salt, time()));
    }

    protected function makeInviteUrl($baseUrl, $token, $kanbanUUid)
    {
        return $baseUrl . '?' . http_build_query(['token' => $token, 'kanban_id' => $kanbanUUid]);
    }

    protected function verifyInviteToken($token, $kanbanId, $userId)
    {
        $redis = $this->getStorageRedis();
        $tokenInfo = $redis->hGetAll($token);
        if (!$tokenInfo) {
            return false;
        }
        $expireAt = $tokenInfo['expire_at'] ?? 0;
        if (time() > $expireAt) {
            return false;
        }
        $kanbanIdInToken = $tokenInfo['kanban_uuid'] ?? 0;
        if ($kanbanIdInToken != $kanbanId) {
            return false;
        }
        // 防止链接泄露给其他人，其他人通过链接加入看板，因此需要校验访问者是否在邀请列表中.
        if ($tokenInfo['invite_type'] == self::INVITE_TYPE_EMAIL) {
            $emails = $tokenInfo['emails'] ? json_decode($tokenInfo['emails'], true) : [];
            $user = $this->getUserModule()->getByUserId($userId, ['email']);
            if (!$user || !in_array($user->email, $emails)) {
                return false;
            }
        }
        return true;
    }

    public function getHasBeenMemberEmails($kanbanId, array $emails)
    {
        $users = $this->getUserModule()->getByEmails($emails, ['id', 'email']);
        $hasBeenMemberEmails = [];
        foreach ($users as $user) {
            if ($this->isMember($kanbanId, $user->id)) {
                $hasBeenMemberEmails[] = $user->email;
            }
        }
        return $hasBeenMemberEmails;
    }

}
