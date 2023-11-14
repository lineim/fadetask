<?php
namespace process;

use Workerman\Timer;
use app\common\toolkit\ModuleTrait;
use support\bootstrap\Log;
use app\module\Notification\Factory;

// 到期提醒通知
class DueNotify
{
    use ModuleTrait;

    public function onWorkerStart()
    {
        if (config('unit_test')) {
            return;
        }
        // 每隔1分钟检查一次是否有到期提醒任务
        Timer::add(60, function() {
            $logger = Log::channel('due_notify');
            $kanbanUrl = config('app.kanban_url');
            $logger->info("due notify timer start");
            
            // 查询通知时间在5分钟之内的任务
            $date = date('Y-m-d H:i:');
            $starttime = strtotime($date . '00');
            $endtime = $starttime + 60;

            try {
                $tasks = $this->getKanbanTaskModule()->getNeedDueNotifyTasksBeforeTime($starttime, $endtime);
                $logger->info('task ids ', $tasks->toArray());
                foreach ($tasks as $task) {
                    $members = $this->getTaskMemberModule()->getTaskMembers($task->id);
                    $userIds = $members->pluck('member_id');
                    $users = [];
                    if ($userIds->toArray()) {
                        $users = $this->getUserModule()->getByUserIds($userIds->toArray(), ['id', 'verified', 'email']);
                    }
                    $emails = [];
                    $validUserIds = [];
                    foreach ($users as $m) {
                        if ($m->verified) {
                            $emails[] = $m->email;
                            $validUserIds[] = $m->id;
                        }
                    }
                    if (empty($emails)) {
                        continue;
                    }
                    $delay = $task['due_notify_time'] - time(); // due_notify_time 为发送通知的时间点，在设置卡片截止日期时，就已计算好
                    if ($delay <= 0) {
                        $delay = 0;
                    }
                    $params = [
                        'id' => $task->id,
                        'kanban_uuid' => $task->kanban_uuid,
                        'userIds' => $validUserIds,
                        'subject' => sprintf('您的待办即将过期'),
                        'url' => str_replace('{id}', $task->kanban_uuid, $kanbanUrl) . '?' . http_build_query(['card_id' => $task->id]),
                        'title' => $task->title,
                        'due_time' => date('Y-m-d H:i', $task->end_time),
                        'delay_send' => $delay, // 延迟多少秒发送
                    ];

                    $channel = Factory::getChannel('mail', 'queue', 'send_due_notify');
                    $channel->sendBatchMsgV2($emails, $params);
                }
            } catch (\Exception $e) {
                $logger->error('due notify error: ' . $e->getMessage(), $e->getTrace());
            }

            $logger->info("due notify timer end");
        });
    }

}