<?php
namespace app\command;

use app\common\toolkit\ModuleTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReCalTaskMemberCountCommand extends Command
{
    use ModuleTrait;

    protected static $defaultName = 'task:re_cal_member_count';
    protected static $defaultDescription = '重新计算所有卡片的成员数量';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kanbans = $this->getKanbanModule()->list(['id', 'name']);

        foreach ($kanbans as $k) {
            $kanbanTasks = $this->getKanbanTaskModule()->getKanbanTasks($k->id, ['id', 'title']);
            foreach ($kanbanTasks as $t) {
                $mcount = $this->getTaskMemberModule()->countTaskMembers($t->id);
                $output->writeln(sprintf('看板 %s 卡片 %s 的成员数为 %d', $k->name, $t->title, $mcount));
                $this->getKanbanTaskModule()->setTaskMemberCount($t->id, $mcount);
            }
        }

        $output->writeln("done");

        return 1;
    }

}
