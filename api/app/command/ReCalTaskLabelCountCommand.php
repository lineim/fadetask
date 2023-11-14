<?php
namespace app\command;

use app\common\toolkit\ModuleTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReCalTaskLabelCountCommand extends Command
{
    use ModuleTrait;

    protected static $defaultName = 'task:re_cal_label_count';
    protected static $defaultDescription = '重新计算所有卡片的标签数量';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kanbans = $this->getKanbanModule()->list(['id', 'name']);

        foreach ($kanbans as $k) {
            $kanbanTasks = $this->getKanbanTaskModule()->getKanbanTasks($k->id, ['id', 'title']);
            foreach ($kanbanTasks as $t) {
                $lcount = $this->getKanbanLabelModule()->countTaskLabel($t->id);
                $output->writeln(sprintf('看板 %s 卡片 %s 的标签数为 %d', $k->name, $t->title, $lcount));
                $this->getKanbanTaskModule()->setTaskLabelCount($t->id, $lcount);
            }
        }

        $output->writeln("done");

        return 1;
    }

}
