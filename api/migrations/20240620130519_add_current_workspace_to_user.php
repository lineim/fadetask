<?php

use Phpmig\Migration\Migration;

class AddCurrentWorkspaceToUser extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `user` add column `current_workspace_id` int unsigned NOT NULL default 0 after `reg_type`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table `user` drop column `current_workspace_id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
