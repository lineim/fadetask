<?php

use Phpmig\Migration\Migration;

class AddProjectKanban extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `project_kanban` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `project_id` int unsigned NOT NULL,
            `kanban_id` int unsigned NOT NULL,
            `user_id` int unsigned NOT NULL,
            `created_time` int unsigned NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS project_kanban;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
