<?php
namespace app\command;

use app\common\toolkit\ModuleTrait;
use app\model\Kanban;
use app\model\KanbanTaskAttachment;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateAttachmentUuidCommand extends Command
{
    use ModuleTrait;

    protected static $defaultName = 'task:generate_attachment_uuid';
    protected static $defaultDescription = '针对没有UUID的附件，给其生成UUID';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $attachments = KanbanTaskAttachment::get();

        foreach ($attachments as $k) {
            if (empty($k->uuid)) {
                KanbanTaskAttachment::where('id', $k->id)->update(['uuid' => Uuid::uuid4()->toString()]);
            }
        }

        $output->writeln("done");
        return 1;
    }

}
