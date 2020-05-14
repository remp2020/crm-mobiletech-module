<?php

use Phinx\Migration\AbstractMigration;

class NullableOutboundProjectId extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_outbound_messages')
            ->changeColumn('project_id', 'string', ['null' => true])
            ->update();

        $this->table('mobiletech_outbound_messages')
            ->changeColumn('project_id', 'string', ['null' => true, 'comment' => 'Can be NULL for PUSH MO (CRM -> mobiletech) messages, otherwise should be copied from inbound message'])
            ->update();
    }
}
