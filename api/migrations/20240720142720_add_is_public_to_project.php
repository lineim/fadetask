<?php

use Phpmig\Migration\Migration;

class AddIsPublicToProject extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "alter table `project` add column `is_public` tinyint unsigned NOT NULL default 0 after `user_id`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "alter table `project` drop column `is_public`;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
