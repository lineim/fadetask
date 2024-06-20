<?php
namespace app\common\toolkit;

use app\module\Attachment;
use app\module\Auth;
use \app\module\Sprint as SprintModule;
use \app\module\Project as ProjectModule;
use app\module\Stat\KanbanStat;
use \app\module\Workflow as WorkflowModule;
use \app\module\Task as TaskModule;
use \app\module\User as UserModule;
use \app\module\Kanban as KanbanModule;
use \app\module\KanbanTask as KanbanTaskModule;
use app\module\KanbanLabel as KanbanLabelModule;
use app\module\KanbanMember;
use app\module\TaskMember;
use app\module\TaskCheckList;
use app\module\LoginSetting;
use app\module\DataSync\DingtalkSync;
use app\module\DataSync\WeworkSync;
use app\module\KanbanTaskComment;
use app\module\TaskLog;
use app\module\Stat\TaskStat;
use app\module\Notification;
use app\module\CustomField\CustomField;
use app\module\CustomField\TaskCustomField;
use app\module\Stat\ProjectStat;
use app\module\Workspace\Workspace;

trait ModuleTrait
{

    /**
     * @return TaskMember
     */
    protected function getTaskMemberModule()
    {
        return TaskMember::inst();
    }

    /**
     * @return TaskModule
     */
    protected function getTaskModule()
    {
        return TaskModule::inst();
    }

    /**
     * @return TaskLog
     */
    protected function getTaskLogModule()
    {
        return TaskLog::inst();
    }
    
    /**
     * @return SprintModule
     */
    protected function getSprintModule()
    {
        return SprintModule::inst();
    }

    /**
     * @return ProjectModule
     */
    protected function getProjectModule()
    {
        return ProjectModule::inst();
    }

    /**
     * @return WorkflowModule
     */
    protected function getWorkflowModule()
    {
        return WorkflowModule::inst();
    }

    /**
     * @return KanbanModule
     */
    protected function getKanbanModule()
    {
        return KanbanModule::inst();
    }

    /**
     * @return UserModule
     */
    protected function getUserModule()
    {
        return UserModule::inst();
    }

    /**
     * @return KanbanTaskModule
     */
    protected function getKanbanTaskModule()
    {
        return KanbanTaskModule::inst();
    }

    /**
     * @return KanbanLabelModule
     */
    protected function getKanbanLabelModule()
    {
        return KanbanLabelModule::inst();
    }

    /**
     * @return TaskCheckList
     */
    protected function getTaskCheckListModule()
    {
        return TaskCheckList::inst();
    }

    /**
     * @return Attachment
     */
    protected function getAttachmentModule()
    {
        return Attachment::inst();
    }

    /**
     * @return LoginSetting
     */
    protected function getSettingModule()
    {
        return LoginSetting::inst();
    }

    /**
     * @return DingtalkSync
     */
    protected function getDingtalkModule()
    {
        return DingtalkSync::inst();
    }

    /**
     * @return WeworkSync
     */
    protected function getWeworkModule()
    {
        return WeworkSync::inst();
    }

    /**
     * @return KanbanMember
     */
    protected function getKanbanMember()
    {
        return KanbanMember::inst();
    }

    /**
     * @return KanbanMember
     */
    protected function getKanbanMemberModule()
    {
        return KanbanMember::inst();
    }

    /**
     * @return KanbanTaskComment
     */
    protected function getTaskCommentModule()
    {
        return KanbanTaskComment::inst();
    }

    /**
     * @return TaskStat
     */
    protected function getTaskStat()
    {
        return TaskStat::inst();
    }

    /**
     * @return Notification
     */
    protected function getNotificationModule()
    {
        return Notification::inst();
    }

    /**
     * @return CustomField
     */
    protected function getCustomFieldModule()
    {
        return CustomField::inst();
    }

    /**
     * @return TaskCustomField
     */
    protected function getTaskCustomeFieldModule() : TaskCustomField
    {
        return TaskCustomField::inst();
    }

    /**
     * @return KanbanStat
     */
    protected function getKanbanStatModule() : KanbanStat
    {
        return KanbanStat::inst();
    }

    /**
     * @return Auth
     */
    protected function getAuthModule() : Auth
    {
        return Auth::inst();
    }

    /**
     * @return ProjectStat
     */
    protected function getProjectStatModule(): ProjectStat
    {
        return ProjectStat::inst();
    }

    /**
     * @return Workspace
     */
    protected function getWorkspaceModule(): Workspace
    {
        return Workspace::inst();
    }

}
