<?php
namespace app\module;

use app\common\exception\AccessDeniedException;
use app\common\exception\InvalidParamsException;
use app\common\exception\ResourceNotFoundException;
use app\common\toolkit\ArrayHelper;
use \app\model\Sprint as SprintModel;
use \app\module\Project;
use Ramsey\Uuid\Uuid;

class Sprint extends BaseModule
{
    const STATUS_NOT_START = 1;
    const STATUS_DOING = 2;
    const STATUS_FINISHED = 3;

    public function getSprintByUuid($uuid, array $fields = ['*'])
    {
        return SprintModel::where('uuid', $uuid)->first($fields);
    }

    public function getProjectSprints($projectIdUuid, array $fields = ["*"])
    {
        return SprintModel::where('project_uuid', $projectIdUuid)->orderBy('id', 'desc')->get($fields);
    }

    public function getProjectSprintsByProjectUuid($uuid, array $fields = ["*"])
    {
        $project = $this->getProjectModule()->getProjectByUuid($uuid, ['id']);
        if (!$project) {
            throw new ResourceNotFoundException('project not found');
        }
        return $this->getProjectSprints($uuid, $fields);
    }

    public function createSprint($projectUuid, $userId, array $sprint)
    {
        if (!$this->getProjectModule()->isManager($projectUuid, $userId)) {
            throw new AccessDeniedException('You have no permission to access this project');
        }
        $this->validateSprint($sprint);

        // todo: 迭代的开始时间不得早于当天凌晨0点，结束时间必须大于开始时间+5天，迭代的状态根据开始时间和当前时间做判断
        $project = $this->getProjectModule()->getProjectByUuid($projectUuid, ['enterprise_id']);
        $uuid = Uuid::uuid4();
        $newSprint = new SprintModel();
        $newSprint->title = $sprint['title'];
        $newSprint->uuid = $uuid->toString();
        $newSprint->start_time = $sprint['start_time'];
        $newSprint->end_time   = $sprint['end_time'];
        $newSprint->project_uuid = $projectUuid;
        $newSprint->enterprise_id = $project->enterprise_id;
        $newSprint->user_id = $userId;
        $newSprint->created_time = time();

        $newSprint->status = self::STATUS_NOT_START;
        if (time() > $newSprint->start_time) {
            $newSprint->status = self::STATUS_DOING;
        }

        if ($newSprint->save()) {
            return $newSprint;
        }
        return false;
    }

    protected function validateSprint(array $sprint) : void
    {
        if (!ArrayHelper::hasKeys($sprint, ['title', 'start_time', 'end_time'])) {
            throw new InvalidParamsException('invalid sprint');
        }
        $beginTimeOfToday = strtotime(date('Y-m-d', time()));
        if ($sprint['start_time'] < $beginTimeOfToday) {
            throw new InvalidParamsException('sprint start time must great than the begin time of today');
        }
        if ($sprint['end_time'] - $sprint['start_time'] < 5 * 86400) {
            throw new InvalidParamsException('sprint\' duration must great than 5 days');
        }
    }

    public function getSprintStories($uuid, $orderBy, $start, $limit)
    {

    }

    public function getSprintTasks($uuid, $orderBy, $start, $limit)
    {

    }

    public function finishSprint($uuid)
    {
        return SprintModel::where('uuid', $uuid)->update(['status' => self::STATUS_FINISHED]);
    }

    /**
     * @return Project
     */
    protected function getProjectModule()
    {
        return Project::inst();
    }

}
