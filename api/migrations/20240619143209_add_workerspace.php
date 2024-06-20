<?php

use Phpmig\Migration\Migration;

class AddWorkerspace extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `workspace` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
            `name` varchar(128) NOT NULL,
            `user_id` int NOT NULL,
            `member_count` int unsigned NOT NULL DEFAULT '0',
            `pay_plan` int NOT NULL DEFAULT '0' COMMENT '0: free',
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
