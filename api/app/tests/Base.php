<?php
declare(strict_types=1);
namespace app\tests;

use app\common\toolkit\ModuleTrait;
use PHPUnit\Framework\TestCase;
use support\Db;

class Base extends TestCase
{
    use ModuleTrait;

    protected function tearDown(): void
    {
        $this->truncateAllTables();
    }

    protected function truncateTables($tables)
    {
        if (is_string($tables)) {
            Db::affectingStatement('truncate table '. $tables);
        } else {
            foreach ($tables as $table) {
                Db::affectingStatement('truncate table '. $table);
            }
        }
    }

    protected function truncateAllTables()
    {
        $this->truncateTables('user');
        $this->truncateTables('kanban');
        $this->truncateTables('kanban_label');
        $this->truncateTables('kanban_list');
        $this->truncateTables('kanban_member');
        $this->truncateTables('kanban_task');
        $this->truncateTables('kanban_task_check_list');
        $this->truncateTables('kanban_task_attachment');
        $this->truncateTables('kanban_task_label');
        $this->truncateTables('kanban_task_comment');
        $this->truncateTables('kanban_task_log');
        $this->truncateTables('kanban_task_member');
        $this->truncateTables('notification');
        $this->truncateTables('kanban_favorite');
        $this->truncateTables('kanban_custom_field');
        $this->truncateTables('kanban_custom_field_option');
        $this->truncateTables('kanban_task_custom_field_val');
        $this->truncateTables('check_list_member');
    }

}
