<?php

use Phpmig\Migration\Migration;

class AddSiteNotification extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `notification` (
            `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '通知ID',
            `user_id` int unsigned NOT NULL COMMENT '被通知的用户ID',
            `type` varchar(64) NOT NULL DEFAULT 'default' COMMENT '通知类型',
            `template` text COMMENT '通知模板',
            `params` text COMMENT '通知参数',
            `created_time` int unsigned NOT NULL COMMENT '通知时间',
            `readed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读',
            PRIMARY KEY (`id`),
            KEY `idx_userid_type` (`user_id`,`type`)
          ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS notification;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
