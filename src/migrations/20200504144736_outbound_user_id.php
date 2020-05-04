<?php

use Phinx\Migration\AbstractMigration;

class OutboundUserId extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_outbound_messages')
            ->addColumn('user_id', 'integer', ['null' => false, 'after' => 'id'])
            ->addForeignKey('user_id', 'users')
            ->update();
    }
}
