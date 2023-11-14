<?php
namespace app\command;

use app\common\toolkit\ModuleTrait;
use app\model\Kanban;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateKanbanUuidCommand extends Command
{
    use ModuleTrait;

    protected static $defaultName = 'kanban:generate_kanban_uuid';
    protected static $defaultDescription = '针对没有UUID的看板，给其生成UUID';

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
