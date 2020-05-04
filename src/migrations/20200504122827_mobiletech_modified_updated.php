<?php

use Phinx\Migration\AbstractMigration;

class MobiletechModifiedUpdated extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_phone_numbers')
            ->renameColumn('modified_at', 'updated_at')
            ->update();
    }
}
