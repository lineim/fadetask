<?php

use Phpmig\Migration\Migration;

class Init extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
        /*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
        /*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
        SET NAMES utf8mb4;
        /*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
        /*!40101 SET @OLD_SQL_MODE='NO_AUTO_VALUE_ON_ZERO', SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
        /*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
        
        
        # 转储表 check_list_member
        # ------------------------------------------------------------
        
        CREATE TABLE `check_list_member` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `check_list_id` int unsigned NOT NULL,
          `member_id` int unsigned NOT NULL,
          `creator_id` int unsigned NOT NULL,
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_uq_check_list_id_member_id` (`check_list_id`,`member_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        
        
        # 转储表 id_pull
        # ------------------------------------------------------------
        
        CREATE TABLE `id_pull` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `type` varchar(128) DEFAULT NULL,
          `current` bigint DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_uq_type` (`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
        
        
        
        # 转储表 kanban
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `uuid` char(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '看板唯一id',
          `name` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `desc` varchar(128) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '看板描述',
          `user_id` int unsigned NOT NULL,
          `is_closed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否关闭',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_cfd_data
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_cfd_data` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL,
          `list_id` int unsigned NOT NULL,
          `daytime` int unsigned NOT NULL COMMENT '日期：当天0点整',
          `task_count` int unsigned DEFAULT '0',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_kanban_time_list` (`kanban_id`,`daytime`,`list_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        
        
        # 转储表 kanban_custom_field
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_custom_field` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='自定义字段表';
        
        
        
        # 转储表 kanban_custom_field_option
        # ------------------------------------------------------------
        
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='自定义字段的选项值';
        
        
        
        # 转储表 kanban_favorite
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_favorite` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int unsigned NOT NULL,
          `kanban_id` int unsigned NOT NULL,
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        
        
        # 转储表 kanban_label
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_label` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL,
          `sort` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '排序，数字越大越靠前',
          `name` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
          `color` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `creator_id` int unsigned NOT NULL DEFAULT '0',
          `created_time` int unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          KEY `idx_kanban_id` (`kanban_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_list
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_list` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL,
          `name` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `task_count` int unsigned NOT NULL DEFAULT '0',
          `wip` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'WIP限制，限制卡片数量，0为不限制',
          `sort` int unsigned NOT NULL DEFAULT '0',
          `archived` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已归档',
          `completed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为已完成列(1:是,0:否)',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_kanban_id` (`kanban_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_member
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_member` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL,
          `member_id` int unsigned NOT NULL,
          `role` tinyint NOT NULL COMMENT '0: 创建者; 1 管理员; 2普通成员',
          `leaved` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0: 正常; 1已离开',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_task
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_task` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `uuid` char(36) CHARACTER SET utf8mb3 NOT NULL DEFAULT '' COMMENT '任务的唯一id',
          `title` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `desc` blob COMMENT '描述',
          `priority` tinyint NOT NULL DEFAULT '2' COMMENT '优先级：0紧急，1高，2普通，3低',
          `member_count` int unsigned NOT NULL DEFAULT '0' COMMENT '卡片成员数量',
          `label_count` int unsigned NOT NULL DEFAULT '0' COMMENT '卡片标签数量',
          `start_time` int unsigned NOT NULL DEFAULT '0' COMMENT '预计开始时间',
          `end_time` int unsigned NOT NULL DEFAULT '0' COMMENT '预计结束时间',
          `due_notify_interval` int NOT NULL DEFAULT '0' COMMENT '到期之前多少秒触发通知',
          `due_notify_time` int NOT NULL DEFAULT '0' COMMENT '到期通知触发时间',
          `due_notified` tinyint NOT NULL DEFAULT '0' COMMENT '到期通知是否已触发',
          `kanban_id` int unsigned NOT NULL COMMENT '看板id',
          `list_id` int unsigned NOT NULL COMMENT '列id',
          `list_sort` int unsigned NOT NULL DEFAULT '0' COMMENT '列表中的排序',
          `is_delete` tinyint unsigned NOT NULL DEFAULT '0',
          `is_finished` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '是否已完成，如果flow_id对应的工作流是完成状态，则标记为已完成',
          `create_user` int unsigned NOT NULL COMMENT '创建者id',
          `finished_time` int unsigned NOT NULL DEFAULT '0' COMMENT '完成时间',
          `attachment_num` int unsigned NOT NULL DEFAULT '0' COMMENT '附件数量',
          `check_list_num` int unsigned NOT NULL DEFAULT '0' COMMENT '卡片检查项数量',
          `check_list_finished_num` int unsigned NOT NULL DEFAULT '0' COMMENT '已完成的卡片检查项数量',
          `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
          `archived` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已归档',
          `created_time` int unsigned NOT NULL COMMENT '创建时间',
          `comment_num` int unsigned NOT NULL DEFAULT '0' COMMENT '评论数',
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_uq_task_uuid` (`uuid`),
          KEY `idx_list_id` (`list_id`),
          KEY `idx_kanban_id` (`kanban_id`),
          KEY `idx_due_notify_time` (`due_notify_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_task_attachment
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_task_attachment` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '看板唯一id',
          `kanban_id` int unsigned NOT NULL,
          `task_id` int unsigned NOT NULL,
          `copy_from_id` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '复制来源id',
          `status` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'uploaded' COMMENT '上传状态: OSS 有init和uploaded两个，localstorage 只有uploaded',
          `storage` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'local' COMMENT '存储类型',
          `file_uri` varchar(512) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `size` bigint unsigned NOT NULL COMMENT '文件大小 bytes',
          `mine_type` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL,
          `org_name` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '文件原始名称',
          `extension` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `user_id` int unsigned NOT NULL COMMENT '上传者用户ID',
          `created_time` int unsigned NOT NULL COMMENT '创建时间',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_task_check_list
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_task_check_list` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL,
          `task_id` int unsigned NOT NULL,
          `title` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `deleted` tinyint(1) NOT NULL DEFAULT '0',
          `is_done` tinyint unsigned NOT NULL DEFAULT '0',
          `done_time` int unsigned NOT NULL DEFAULT '0',
          `due_time` int unsigned NOT NULL DEFAULT '0' COMMENT '到期时间',
          `creator` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_task_id` (`task_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_task_comment
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_task_comment` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL,
          `task_id` int unsigned NOT NULL,
          `content` text COLLATE utf8mb4_general_ci NOT NULL,
          `user_id` int unsigned NOT NULL,
          `parent_id` int unsigned NOT NULL DEFAULT '0',
          `update_time` int unsigned NOT NULL DEFAULT '0',
          `is_delete` tinyint unsigned NOT NULL DEFAULT '0',
          `create_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_task_id` (`task_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_task_custom_field_val
        # ------------------------------------------------------------
        
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        
        
        # 转储表 kanban_task_label
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_task_label` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `task_id` int unsigned NOT NULL,
          `label_id` int unsigned NOT NULL,
          `kanban_id` int unsigned NOT NULL,
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_task_log
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_task_log` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL,
          `parent_id` int unsigned NOT NULL DEFAULT '0' COMMENT 'parent id',
          `action` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
          `task_id` int unsigned NOT NULL,
          `user_id` int unsigned NOT NULL,
          `change` blob,
          `is_delete` tinyint unsigned NOT NULL DEFAULT '0',
          `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT 'update time',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_task_id` (`task_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_task_member
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_task_member` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `task_id` int unsigned NOT NULL,
          `member_id` int unsigned NOT NULL,
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 kanban_wip
        # ------------------------------------------------------------
        
        CREATE TABLE `kanban_wip` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `kanban_id` int unsigned NOT NULL COMMENT '看板id',
          `list_id` int unsigned NOT NULL COMMENT '看板列id',
          `wip` tinyint unsigned NOT NULL COMMENT 'WIP限制，限制卡片数量，0为不限制',
          `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
          `created_time` int unsigned NOT NULL COMMENT '创建时间',
          PRIMARY KEY (`id`),
          UNIQUE KEY `unq_idx_kanban_id_list_id` (`kanban_id`,`list_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 login_log
        # ------------------------------------------------------------
        
        CREATE TABLE `login_log` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int unsigned NOT NULL DEFAULT '0',
          `login_ip` varchar(256) DEFAULT NULL,
          `is_success` tinyint unsigned NOT NULL DEFAULT '0',
          `error_type` varchar(32) NOT NULL DEFAULT (_utf8mb4''),
          `params` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        
        
        # 转储表 migrations
        # ------------------------------------------------------------
        
        CREATE TABLE `migrations` (
          `version` varchar(255) COLLATE utf8mb3_unicode_ci NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
        
        
        
        # 转储表 notification
        # ------------------------------------------------------------
        
        CREATE TABLE `notification` (
          `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '通知ID',
          `user_id` int unsigned NOT NULL COMMENT '被通知的用户ID',
          `type` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default' COMMENT '通知类型',
          `template` text COLLATE utf8mb4_general_ci COMMENT '通知模板',
          `params` text COLLATE utf8mb4_general_ci COMMENT '通知参数',
          `created_time` int unsigned NOT NULL COMMENT '通知时间',
          `readed` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否已读',
          PRIMARY KEY (`id`),
          KEY `idx_userid_type` (`user_id`,`type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 organization
        # ------------------------------------------------------------
        
        CREATE TABLE `organization` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `syncCode` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '钉钉ID',
          `enterpriseId` bigint unsigned NOT NULL COMMENT '企业Id',
          `parentId` bigint unsigned NOT NULL DEFAULT '0' COMMENT '父Id',
          `orgName` varchar(256) COLLATE utf8mb4_general_ci NOT NULL COMMENT '部门名称',
          `orgCode` varchar(256) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1.' COMMENT '组织机构内部编码',
          `createdTime` int NOT NULL COMMENT '创建时间',
          `updatedTime` int NOT NULL DEFAULT '0' COMMENT '最后更新时间',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='组织机构表';
        
        
        
        # 转储表 project
        # ------------------------------------------------------------
        
        CREATE TABLE `project` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
          `type` tinyint DEFAULT '1' COMMENT '项目类型: 1看板, 2敏捷',
          `name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT '项目名称',
          `description` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `enterprise_id` int unsigned NOT NULL DEFAULT '0' COMMENT '企业ID',
          `user_id` int unsigned NOT NULL COMMENT '创建者id',
          `is_closed` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '项目是否关闭',
          `is_deleted` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否删除',
          `kanban_num` int unsigned NOT NULL DEFAULT '0' COMMENT '迭代数',
          `member_num` int unsigned NOT NULL DEFAULT '0' COMMENT '故事数量',
          `updated_time` int unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_uniq_uuid` (`uuid`),
          KEY `idx_create_user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 project_kanban
        # ------------------------------------------------------------
        
        CREATE TABLE `project_kanban` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `project_id` int unsigned NOT NULL,
          `kanban_id` int unsigned NOT NULL,
          `user_id` int unsigned NOT NULL,
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_uq_kanban_id` (`kanban_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        
        
        # 转储表 project_member
        # ------------------------------------------------------------
        
        CREATE TABLE `project_member` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `project_id` int unsigned NOT NULL,
          `user_id` int unsigned NOT NULL,
          `role` tinyint unsigned NOT NULL DEFAULT '3' COMMENT '角色: 0 Owner; 1 管理员; 2 项目经理; 3 产品; 4 研发; 5 测试;',
          `join_time` int unsigned NOT NULL,
          `is_delete` tinyint(1) NOT NULL DEFAULT '0',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
        
        
        
        # 转储表 setting
        # ------------------------------------------------------------
        
        CREATE TABLE `setting` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `value` longblob,
          `enterprise_id` bigint NOT NULL DEFAULT '0' COMMENT '企业ID',
          `created_time` int NOT NULL COMMENT '创建时间',
          `updated_time` int NOT NULL DEFAULT '0' COMMENT '最后更新时间',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='配置表';
        
        
        
        # 转储表 sms_send_log
        # ------------------------------------------------------------
        
        CREATE TABLE `sms_send_log` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `mobile` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '手机号',
          `module` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '业务模版',
          `params` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL COMMENT '参数',
          `created_time` int unsigned NOT NULL COMMENT '发送时间',
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        
        
        # 转储表 team
        # ------------------------------------------------------------
        
        CREATE TABLE `team` (
          `Id` bigint NOT NULL AUTO_INCREMENT,
          `name` varchar(256) COLLATE utf8mb4_general_ci NOT NULL COMMENT '企业名称',
          `logo_url` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'logo地址',
          `user_id` int unsigned NOT NULL COMMENT '创建者',
          `created_time` int NOT NULL COMMENT '创建时间',
          `updated_time` int NOT NULL DEFAULT '0' COMMENT '最后更新时间',
          PRIMARY KEY (`Id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 user
        # ------------------------------------------------------------
        
        CREATE TABLE `user` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `uuid` char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `email` varchar(250) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `passhash` varchar(72) COLLATE utf8mb4_general_ci NOT NULL,
          `name` varchar(60) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `mobile` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '',
          `reg_type` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'email' COMMENT '注册来源: email, 邮箱注册；mobile, 手机号注册；其他表示对应第三方平台',
          `hired_time` int unsigned NOT NULL DEFAULT '0' COMMENT '入职时间，单位秒',
          `title` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT '职位',
          `verified` tinyint unsigned NOT NULL DEFAULT '0',
          `avatar` varchar(250) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
          `role` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USER',
          `created_time` int unsigned NOT NULL,
          `updated_time` int unsigned NOT NULL DEFAULT '0',
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_uq_uuid` (`uuid`),
          KEY `idx_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        
        
        # 转储表 user_bind
        # ------------------------------------------------------------
        
        CREATE TABLE `user_bind` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `user_id` int unsigned NOT NULL,
          `out_user_id` varchar(64) NOT NULL COMMENT '第三方平台用户id',
          `union_id` varchar(64) NOT NULL DEFAULT '',
          `type` varchar(16) NOT NULL DEFAULT '' COMMENT '1: Dingtal， 2: Weiwork',
          `created_time` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `type` (`type`,`union_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        
        
        
        
        /*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
        /*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
        /*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
        /*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
        /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
        /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
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

    }
}
