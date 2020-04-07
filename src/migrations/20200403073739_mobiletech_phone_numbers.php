<?php

use Phinx\Migration\AbstractMigration;

class MobiletechPhoneNumbers extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_phone_numbers')
            ->addColumn('phone_number', 'string', ['null' => false, 'comment' => 'Mobile phone number used to purchase subscription via Mobiletech.'])
            ->addColumn('user_id', 'integer', ['null' => false, 'comment' => 'Link to user'])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('modified_at', 'datetime', ['null' => false])
            ->addIndex('phone_number', ['unique' => true])
            ->addIndex('user_id', ['unique' => true])
            ->addForeignKey('user_id', 'users')
            ->create();
    }
}
