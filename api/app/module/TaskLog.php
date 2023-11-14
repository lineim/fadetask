<?php
namespace app\module;

use app\model\KanbanTaskLog;
use app\common\exception\AccessDeniedException;
use app\common\exception\BusinessException;
use app\module\LogFormat\BaseLogFormat;

class TaskLog extends KanbanTask
{

    public function getActivity($id, $userId, $offset = 0, $limit = 20)
    {
        if (!$this->getKanbanModule()->isKanbanMemberByTaskId($userId, $id)) {
            throw new AccessDeniedException('Access Denied!');
        }
        $logs = KanbanTaskLog::where('task_id', $id)
            ->where('is_delete', 0)
            ->orderBy('id', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        
        $actionLogsMap = [];
        foreach ($logs as $log) {
            if (!in_array($log->action, $this->getTaskLogActions())) {
                continue;
            }
            if (!isset($actionLogsMap[$log->action])) {
                $actionLogsMap[$log->action] = [];
            }
            $actionLogsMap[$log->action][] = $log;
        }
        $processedLogs = [];
        foreach ($actionLogsMap as $action => $logs) {
            try {
                $formater = $this->getFormater($action);
                $logs = $formater->process($logs);
                foreach ($logs as $log) {
                    $processedLogs[] = $log;
                }
            } catch (BusinessException $e) {
                $logger = $this->getLogger();
                $logger->error(sprintf('process log error: %s', $e->getMessage()), $e->getTrace());
                continue;
            } catch (\Exception $e) {
                $logger = $this->getLogger();
                $logger->error(sprintf('An exception occured when process log: %s', $e->getMessage()), $e->getTrace());
                throw new \Exception('system error');
            }    
        }
        return $this->sortLogsById($processedLogs);
    }

    /**
     * @return BaseLogFormat
     */
    protected function getFormater($type)
    {
        switch ($type) {
            case self::TASK_LOG_ACTION_CREATE:
                $class = "app\\module\\LogFormat\\CreateFormat";
                break;
            case self::TASK_LOG_ACTION_CHANGE_LIST:
                $class = "app\\module\\LogFormat\\ChangeListFormat";
                break;
            case self::TASK_LOG_ACTION_ADD_CHECKLIST:
                $class = "app\\module\\LogFormat\\AddCheckListFormat";
                break;
            case self::TASK_LOG_ACTION_DONE_CHECKLIST:
                $class = "app\\module\\LogFormat\\DoneCheckListFormat";
                break;
            case self::TASK_LOG_ACTION_UNDONE_CHECKLIST:
                $class = "app\\module\\LogFormat\\UnDoneCheckListFormat";
                break;
            case self::TASK_LOG_ACTION_ADD_ATTACHMENT:
                $class = "app\\module\\LogFormat\\AddAttachmentFormat";
                break;
            case self::TASK_LOG_ACTION_DEL_ATTACHMENT:
                $class = "app\\module\\LogFormat\\DelAttachmentFormat";
                break;
            case self::TASK_LOG_ACTION_CHANGE_TITLE:
                $class = "app\\module\\LogFormat\\ChangeTitleFormat";
                break;
            case self::TASK_LOG_ACTION_CHANGE_DESC:
                $class = "app\\module\\LogFormat\\ChangeDescFormat";
                break;
            case self::TASK_LOG_ACTION_SET_DUEDATE:
                $class = "app\\module\\LogFormat\\SetDueDateFormat";
                break;
            case self::TASK_LOG_ACTION_ADD_MEMBER:
                $class = "app\\module\\LogFormat\\AddMembersFormat";
                break;
            case self::TASK_LOG_ACTION_REMOVE_MEMBER:
                $class = "app\\module\\LogFormat\\RemMembersFormat";
                break;
            case self::TASK_LOG_ACTION_DONE:
                $class = "app\\module\\LogFormat\\DoneFormat";
                break;
            case self::TASK_LOG_ACTION_DONE_AUTO:
                $class = "app\\module\\LogFormat\\DoneAutoFormat";
                break;
            case self::TASK_LOG_ACTION_UNDONE:
                $class = "app\\module\\LogFormat\\UndoneFormat";
                break;
            case self::TASK_LOG_ACTION_SET_PRIORITY:
                $class = "app\\module\\LogFormat\\SetPriorityFormat";
                break;
            case self::TASK_LOG_ACTION_ADD_COMMENT:
                $class = "app\\module\\LogFormat\\AddCommentFormat";
                break;
            case self::TASK_LOG_ACTION_EDIT_COMMENT:
                $class = "app\\module\\LogFormat\\EditCommentFormat";
                break;
            case self::TASK_LOG_ACTION_DEL_COMMENT:
                $class = "app\\module\\LogFormat\\DelCommentFormat";
                break;
            case self::TASK_LOG_ACTION_MOVE_TO_OTHER:
                $class = "app\\module\\LogFormat\\MoveToOtherFormat";
                break;
            default:
                throw new BusinessException(sprintf('log %s not support', $type));
                    
        }
        return new $class;
    }

    protected function sortLogsById(array $logs) {
        $logs = array_values($logs);
        $len = count($logs);
        for ($i = 0; $i < $len; $i ++) {
            for ($j = 0; $j < $len - 1 - $i; $j ++) {
                $preLog = $logs[$j];
                $nextLog = $logs[$j + 1];
                if ($preLog->id < $nextLog->id) {
                    $logs[$j + 1] = $preLog;
                    $logs[$j] = $nextLog;
                }
            }
        }
        return $logs;
    }

}
