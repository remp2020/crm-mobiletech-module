<?php

use Phinx\Migration\AbstractMigration;

class SmsInboundOutbound extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_inbound_messages')
            ->addColumn('mobiletech_id', 'string', ['null' => false])
            ->addColumn('serv_id', 'string', ['null' => false])
            ->addColumn('project_id', 'string', ['null' => false])
            ->addColumn('from', 'string', ['null' => false])
            ->addColumn('to', 'string', ['null' => false])
            ->addColumn('content', 'string', ['null' => false])
            ->addColumn('content_coding', 'string', ['null' => false])
            ->addColumn('dcs', 'string', ['null' => false])
            ->addColumn('esm', 'string', ['null' => false])
            ->addColumn('operator_type', 'string', ['null' => false])
            ->addColumn('receive_date', 'datetime', ['null' => false])
            ->addColumn('created_at', 'datetime', ['null' => false])
            ->addColumn('updated_at', 'datetime', ['null' => false])
            ->create();
    }
}
