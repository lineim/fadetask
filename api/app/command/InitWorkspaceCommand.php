<?php
namespace app\command;

use app\common\toolkit\ModuleTrait;
use app\model\Project;
use app\model\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InitWorkspaceCommand extends Command
{
    use ModuleTrait;

    protected static $defaultName = 'workspace:init_default';
    protected static $defaultDescription = '针对没用工作空间的用户，生成工作空间';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = User::all();
        foreach ($users as $user) {
            $userWorkspaces = $this->getWorkspaceModule()->getUserCreatedWorkspaces($user->id);
            if (count($userWorkspaces) > 0) {
                continue;
            }
            $workspace = $this->getWorkspaceModule()->createWorkspace($user->name . "'s Workspace", $user->id);
            $this->getUserModule()->changeUserCurrentWorkspace($user->id, $workspace->id);

            Project::where('user_id', $user->id)
                ->where('workspace_id', 0)
                ->update(
                    ['workspace_id' => $workspace->id]
                );
        }
        return 1;
    }

}