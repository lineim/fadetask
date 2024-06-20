<?php
namespace app\command;

use app\common\toolkit\ModuleTrait;
use app\model\Kanban;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateWorkspaceCommand extends Command
{
    use ModuleTrait;

    protected static $defaultName = 'workspace:generate_default';
    protected static $defaultDescription = '针对没用工作空间的用户，生成工作空间';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $kanbans = $this->getKanbanModule()->list(['id', 'name', 'uuid']);

        foreach ($kanbans as $k) {
            if (empty($k->uuid)) {
                Kanban::where('id', $k->id)->update(['uuid' => Uuid::uuid4()->toString()]);
            }
        }

        $output->writeln("done");

        return 1;
    }

}