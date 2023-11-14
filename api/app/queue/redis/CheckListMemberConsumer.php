<?php
namespace app\queue\redis;

use app\common\mailer\Factory;
use app\common\toolkit\ModuleTrait;
use Webman\RedisQueue\Consumer;
use support\bootstrap\Log;

class CheckListMemberConsumer implements Consumer
{
    use ModuleTrait;
    // 要消费的队列名
    public $queue = 'checklist:member:change';

    // 连接名，对应 config/redis_queue.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $logger = Log::channel();
        $logger->info("[async.event][" . $this->queue. "] event received", $data);

        $requiredParams = ['type', 'checklist_id', 'member_id', 'creator'];
        if (array_diff($requiredParams, array_keys($data))) {
            $logger->error("[async.event][" . $this->queue. "] params miss required fields", $requiredParams);
            return false;
        }
        $member = $this->getUserModule()->getByUserId($data['member_id']);
        if (!$member) {
            $logger->error("[async.event][" . $this->queue. "] member #{$data['member_id']} not found");
            return false;
        }
        if (!$member->verified) {
            $logger->error("[async.event][" . $this->queue. "] member #{$data['member_id']} not valided");
            return false;
        }
        $checklist = $this->getTaskCheckListModule()->get($data['checklist_id'], ['task_id', 'title']);
        if (!$checklist) {
            $logger->error("[async.event][" . $this->queue. "] checklist #{$data['checklist_id']} not found");
            return false;
        }
        $task = $this->getKanbanTaskModule()->getTask($checklist->task_id, ['title', 'kanban_id']);
        if (!$task) {
            $logger->error("[async.event][" . $this->queue. "] task #{$data['checklist_id']} not found");
            return false;
        }
        $operator = $this->getUserModule()->getByUserId($data['creator']);
        if (!$operator) {
            $logger->error("[async.event][" . $this->queue. "] trigger user not #{$data['creator']} not found");
            return false;
        }

        $data['task_id'] = $checklist->task_id;
        $data['kanban_id'] = $task->kanban_id;
        $data['task_title'] = $task->title;
        $data['check_list_id'] = $data['checklist_id'];
        $data['check_list_title'] = $checklist->title;
        $data['to'] = $member->email;

        $this->getNotificationModule()->joinCheckListNotification($data['member_id'], $data['type'], $data, $operator->toArray());
        if (!$member->email || stripos($member->email, '@sys.com') !== false) {
            $logger->error("[async.event][" . $this->queue. "] memmber email empty or is sys.com email, email: {$member->email}");
            return false;
        }
        $this->sendMail($data, $operator);
    }

    protected function sendMail($data, $operator)
    {
        $logger = Log::channel();
        try {
            $kanbanUrl = config('app.kanban_url');
            $params = [
                'operator' => $operator['name'] ?? '',
                'taskTitle' => $data['task_title'] ?? '',
                'checklistTitle' => $data['check_list_title'] ?? '',
                'url' => str_replace('{id}', $data['kanban_id'], $kanbanUrl) . '?' . http_build_query(['card_id' => $data['task_id']]),
            ];
            $tpl = $data['type'] == 'add' ? 'notification/join_checklist' : 'notification/rm_from_checklist';
            $htmlContent = render($tpl, $params);
            $mailer = Factory::mailer();
            $from = [config('mailer.smtp.user') => config('mailer.smtp.username')];
            $mailer->batchSend($from, [$data['to']], '指派检查项通知', $htmlContent);
        } catch (\Exception $e) {
            $logger->error(sprintf("[async.event][" . $this->queue. ']send mail error: %s', $e->getMessage()), ['params' => $data, 'trace' => $e->getTrace()]);
        }
    }

}
