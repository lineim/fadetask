<?php

use Phpmig\Migration\Migration;

class AddStatusStorageToTaskAttachment extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE kanban_task_attachment ADD COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'uploaded' COMMENT '上传状态: OSS 有init和uploaded两个，localstorage 只有uploaded' AFTER `copy_from_id`, 
            ADD COLUMN `storage` VARCHAR(32) NOT NULL DEFAULT 'local' COMMENT '存储类型' AFTER `status`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = " ALTER TABLE `kanban_task_attachment` DROP COLUMN `status`, DROP COLUMN `storage`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
