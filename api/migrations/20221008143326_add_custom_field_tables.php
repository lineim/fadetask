<?php

use Phpmig\Migration\Migration;

class AddCustomFieldTables extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `kanban_custom_field` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kanban_id` int unsigned NOT NULL,
  `name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '字段名称',
  `type` varchar(16) NOT NULL COMMENT '类型: Checkbox, Datetime, Dropdown/Select, Number, Text',
  `show_on_card_front` tinyint DEFAULT '1' COMMENT '是否在看板详情页中的卡片上展示',
  `sort` int unsigned NOT NULL DEFAULT '0' COMMENT '自定义字段顺序，数字越小，排在前面',
  `user_id` int unsigned NOT NULL COMMENT '创建者',
  `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '0',
  `created_time` int unsigned NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_uq_kanban_id_name_type` (`kanban_id`,`name`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='自定义字段表';

        CREATE TABLE `kanban_custom_field_option` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int unsigned NOT NULL,
  `val` varchar(32) NOT NULL,
  `color` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `user_id` int unsigned NOT NULL COMMENT '创建者',
  `updated_time` int unsigned NOT NULL,
  `created_time` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uq_field_id` (`field_id`)
) ENGINE=InnoDB AUTO_INCREMENT=100 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='自定义字段的选项值';

        CREATE TABLE `kanban_task_custom_field_val` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `kanban_id` int unsigned NOT NULL,
  `task_id` int unsigned NOT NULL,
  `field_id` int unsigned NOT NULL,
  `val` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `updated_time` int unsigned NOT NULL,
  `created_time` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uq_field_id` (`field_id`),
  KEY `idx_task_id` (`task_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ";

        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "DROP TABLE IF EXISTS kanban_custom_field; DROP TABLE IF EXISTS kanban_custom_field_option; DROP TABLE IF EXISTS kanban_task_custom_field_val;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
