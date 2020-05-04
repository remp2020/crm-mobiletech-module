<?php

use Phinx\Migration\AbstractMigration;

class SmsOutbound extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_inbound_messages')
            ->addColumn('user_id', 'integer', ['null' => true])
            ->changeColumn('content', 'text', ['null' => false])
            ->addForeignKey('user_id', 'users')
            ->update();

        $this->table('mobiletech_templates')
            ->addColumn('code', 'string', ['null' => false])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addIndex(['code'], ['unique' => true])
            ->create();

        $this->table('mobiletech_outbound_messages')
            ->addColumn('mobiletech_id', 'string', ['null' => false])
            ->addColumn('mobiletech_template_id', 'integer', ['null' => false])
            ->addColumn('status', 'string', ['null' => true])
            ->addColumn('serv_id', 'string', ['null' => false])
            ->addColumn('project_id', 'string', ['null' => false])
            ->addColumn('rcv_msg_id', 'string', ['null' => true, 'comment' => 'Empty for PUSH messages without inbound message trigger.'])
            ->addColumn('from', 'string', ['null' => false])
            ->addColumn('to', 'string', ['null' => false])
            ->addColumn('billmsisdn', 'string', ['null' => true, 'comment' => 'MSISDN that the message will be charged to (if differs from "to").'])
            ->addColumn('billkey', 'string', ['null' => false])
            ->addColumn('content', 'text', ['null' => false])
            ->addColumn('content_coding', 'string', ['null' => false])
            ->addColumn('dcs', 'string', ['null' => false])
            ->addColumn('esm', 'string', ['null' => false])
            ->addColumn('expiration', 'integer', ['null' => false])
            ->addColumn('operator_type', 'string', ['null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->addForeignKey('mobiletech_template_id', 'mobiletech_templates')
            ->create();
    }
}
