<?php

use Phpmig\Migration\Migration;

class AlterProjectRenameEnterpriseIdWorkspaceId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE project RENAME COLUMN enterprise_id TO workspace_id;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "ALTER TABLE project RENAME COLUMN workspace_id TO enterprise_id;";
        $container = $this->getContainer();
        $connection = $container['db'];
        $connection->exec($sql);
    }
}
