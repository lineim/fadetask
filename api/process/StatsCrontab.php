<?php
namespace process;

use app\common\toolkit\ModuleTrait;
use Workerman\Crontab\Crontab;
use support\bootstrap\Log;

class StatsCrontab
{
    use ModuleTrait;

   public function onWorkerStart()
   {
        if (config('unit_test')) {
           return;
        }
        // 每日看板 CFD 统计
        new Crontab('0 0 2 * * *', function() {
            $logger = Log::channel('crontab');
            $kanbans = $this->getKanbanModule()->getAllKanbans('', ['id', 'name']);
            $logger->info(json_encode($kanbans));
            foreach ($kanbans as $kanban) {
                $logger->info(sprintf('kanban %d %s cfd stat start', $kanban->id, $kanban->name));
                try {
                    $this->getKanbanStatModule()->cfdStats($kanban->id);
                    $logger->info(sprintf('kanban %d %s cfd stat end', $kanban->id, $kanban->name));
                } catch (\Exception $e) {
                    $logger->error((sprintf('kanban %d %s cfd stat error: %s', $kanban->id, $kanban->name, $e->getMessage())));
                }
            }
        }, 'kanban-cfd');
   }
}
