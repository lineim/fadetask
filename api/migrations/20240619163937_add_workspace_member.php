<?php

use Phpmig\Migration\Migration;

class AddWorkspaceMember extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `workspace_member` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `workspace_id` int unsigned NOT NULL,
            `member_id` int unsigned NOT NULL,
            `role` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'user',
            `deleted` tinyint unsigned NOT NULL DEFAULT '0',
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
