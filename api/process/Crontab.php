<?php
namespace process;

use app\common\toolkit\ModuleTrait;
use Workerman\Crontab\Crontab as C;
use app\module\Task;
use app\module\DataSync\DingtalkSync;
use app\module\DataSync\WeworkSync;
use Exception;
use support\bootstrap\Log;
use app\module\Notification\Factory;
class Crontab
{
    use ModuleTrait;

   public function onWorkerStart()
   {
        if (config('unit_test')) {
           return;
        }
        $name = 'third part user sync';
        // 同步钉钉用户
        new C('0 30 */2 * * *', function() {
            $name = 'dingtalk user sync';
            $logger = Log::channel('crontab');
            $logger->info('cron ' . $name . ' start');
            $syncer = DingtalkSync::inst();
            try {
                $syncer->sync();
            } catch (Exception $e) {
                $logger->error('cron ' . $name . ' error: ' . $e->getMessage());
                return;
            }
            $logger->info('cron ' . $name . ' end');
        }, $name);

        // 同步微信用户
        new C('0 50 */2 * * *', function() {
            $name = 'wework user sync';
            $logger = Log::channel('crontab');
            $logger->info('cron ' . $name . ' start');
            try {
                $syncer = WeworkSync::inst();
                $syncer->sync();
            } catch (Exception $e) {
                $logger->error('cron ' . $name . ' error: ' . $e->getMessage());
                return;
            }
            $logger->info('cron ' . $name . ' end');
        }, $name);
        
        // 每日待办通知
        new C('0 0 9 * * *', function() {
            $name = 'daily_work_status_notify';
            $cronName = sprintf('cron:%s:', $name);
            $kanbanUrl = config('app.kanban_url');
            $logger = Log::channel('crontab');
            $logger->info($cronName . " start");

            $week = date('w', time());
            // w: 0 (for Sunday) through 6 (for Saturday)
            if (in_array($week, [0, 6])) { // 周末
                $logger->info($cronName . " end. 周末，不统计!");
                return;
            }

            $statData = [];
            try {
                $page = 1;
                $limit = 100;
                for (;;) {
                    $offset = ($page - 1) * $limit;
                    $data = $this->getTaskStat()->getMemberTasksStat(0, $offset, $limit);
                    if ($data->isEmpty()) {
                        break;
                    }
                    foreach ($data as $item) {
                        if (empty($item->email) || stripos($item->email, '@sys.com') !== false) {
                            continue;
                        }
                        if (!isset($statData[$item->member_id])) {
                            $statData[$item->member_id]['user'] = ['id' => $item->member_id, 'email' => $item->email];
                            $statData[$item->member_id]['stat'] = ['overdue' => [], 'nearly' => [], 'normal' => []];
                        }
                        $task = [
                            'id' => $item->task_id,
                            'kanban_id' => $item->kanban_id,
                            'name' => $item->title,
                            'due_time' => '',
                            'url' => str_replace('{id}', $item->kanban_id, $kanbanUrl) . '?' . http_build_query(['card_id' => $item->task_id])
                        ];
                        if ($item->end_time > 0) {
                            $task['due_date'] = date('Y-m-d', $item->end_time);
                            if ($item->end_time < time()) {
                                $statData[$item->member_id]['stat']['overdue'][] = $task;
                            } elseif ($item->end_time - time() < 86400) {
                                $statData[$item->member_id]['stat']['nearly'][] = $task; 
                            }
                        } else {
                            $statData[$item->member_id]['stat']['normal'][] = $task; 
                        }                        
                    }
                    $page ++;
                }

                $logger->info('cron ' . $name . ' data', $statData);
            } catch (Exception $e) {
                $logger->error($cronName . ' error: ' . $e->getMessage());
                return;
            }

            try {
                foreach ($statData as $userData) {
                    $user = $userData['user'];
                    $overdue = $userData['stat']['overdue'];
                    $nearly = $userData['stat']['nearly'];
                    $normal = $userData['stat']['normal'];
    
                    $total = count($overdue) + count($nearly) + count($normal);
                    if ($total == 0) {
                        continue;
                    }
                    $params = [
                        'total' => $total,
                        'overdue' => $overdue,
                        'nearly' => $nearly,
                        'normal' => $normal
                    ];
                    $statHtmlString = render('notification/daily_todo_table', $params);
                    $channel = Factory::getChannel('mail');
                    $channel->sendSingleMsg(
                        $user['email'], 
                        'daily_todo_notify', 
                        [
                            'subject' => sprintf('您总共有 %d 项待办；其中，已过期 %d 项，即将过期 %d 项', $total, count($nearly), count($normal)),
                            'content' => $statHtmlString
                        ]
                    );
                }
            } catch (Exception $e) {
                $logger->error($cronName . ' error: ' . $e->getMessage());
            }

            // echo json_encode($statData);

            $logger->info('cron ' . $name . ' end');
        }, $name);

   }
}
