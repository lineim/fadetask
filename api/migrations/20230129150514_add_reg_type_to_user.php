<?php

use Phpmig\Migration\Migration;

class AddRegTypeToUser extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE `user` ADD COLUMN `reg_type` VARCHAR(32) NOT NULL DEFAULT 'email' COMMENT '注册来源: email, 邮箱注册；mobile, 手机号注册；其他表示对应第三方平台' AFTER `mobile`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = " ALTER TABLE `user` DROP COLUMN `reg_type`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
