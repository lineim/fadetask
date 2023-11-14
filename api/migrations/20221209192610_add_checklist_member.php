<?php

use Phpmig\Migration\Migration;

class AddChecklistMember extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `check_list_member` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `check_list_id` int unsigned NOT NULL,
            `member_id` int unsigned NOT NULL,
            `creator_id` int unsigned NOT NULL,
            `created_time` int unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_uq_check_list_id_member_id` (`check_list_id`,`member_id`)
          ) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";

        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS check_list_member;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
