<?php

use Phinx\Migration\AbstractMigration;

class OutboundMessageIndicesAndNullables extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_inbound_messages')
            ->addIndex('mobiletech_id')
            ->update();

        $this->table('mobiletech_outbound_messages')
            ->changeColumn('mobiletech_id', 'string', ['null' => true]) // we get the ID only after we actually send the message
            ->changeColumn('user_id', 'integer', ['null' => true])
            ->changeColumn('dcs', 'string', ['null' => true])
            ->changeColumn('esm', 'string', ['null' => true])
            ->removeColumn('content') // we don't want to store whole content after all
            ->removeColumn('content_coding') // we don't need to know coding, if we don't store the content
            ->addColumn('content_length', 'string', ['null' => false, 'after' => 'billkey']) // but it would be nice to know the actual sms length, if we're not over 160 characters after params injection
            ->addColumn('payment_id', 'integer', ['null' => true, 'after' => 'mobiletech_id']) // context to prevent multiple sent messages
            ->addIndex('mobiletech_id')
            ->addForeignKey('payment_id', 'payments')
            ->addForeignKey('rcv_msg_id', 'mobiletech_inbound_messages', 'mobiletech_id')
            ->update();
    }
}
