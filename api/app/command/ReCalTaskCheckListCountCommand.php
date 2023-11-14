<?php
namespace app\command;

use app\common\toolkit\ModuleTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReCalTaskCheckListCountCommand extends Command
{
    use ModuleTrait;

    protected static $defaultName = 'task:re_cal_checklist_count';
    protected static $defaultDescription = '重新计算所有卡片的检查项数量';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kanbans = $this->getKanbanModule()->list(['id', 'name']);

        foreach ($kanbans as $k) {
            $kanbanTasks = $this->getKanbanTaskModule()->getKanbanTasks($k->id, ['id', 'title']);
            foreach ($kanbanTasks as $t) {
                list('total' => $total, 'finished' => $finished) = $this->getTaskCheckListModule()->statCheckListNum($t->id);
                if ($total == 0) {
                    continue;
                }
                $output->writeln(sprintf('看板 %s 卡片 %s 的检查项总数为 %d，已完成的检查项数量为 %d', $k->name, $t->title, $total, $finished));
                $this->getKanbanTaskModule()->updateTaskCheckListNum($t->id, $total, $finished);
            }
        }

        $output->writeln("done");

        return 1;
    }

}
