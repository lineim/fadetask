<?php

use Phpmig\Migration\Migration;

class AddKanbanCfdTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `kanban_cfd_data` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `kanban_id` int unsigned NOT NULL,
            `list_id` int unsigned NOT NULL,
            `daytime` int unsigned NOT NULL COMMENT '日期：当天0点整',
            `task_count` int unsigned DEFAULT '0',
            `created_time` int unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_kanban_time_list` (`kanban_id`,`daytime`,`list_id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS kanban_cfd_data;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
