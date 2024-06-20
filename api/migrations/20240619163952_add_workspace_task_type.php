<?php

use Phpmig\Migration\Migration;

class AddWorkspaceTaskType extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `workspace_task_type` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `workspace_id` int unsigned NOT NULL,
            `code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
            `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
            `icon` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
            `color` varchar(128) NOT NULL,
            `is_default` tinyint NOT NULL DEFAULT '0',
            `creator_id` int unsigned NOT NULL,
            `created_time` int unsigned NOT NULL,
            PRIMARY KEY (`id`)
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

    }
}
