<?php

use Phinx\Migration\AbstractMigration;

class MakeUserInOutboundMessagesNullable extends AbstractMigration
{
    public function change()
    {
        $this->table('mobiletech_outbound_messages')
            ->changeColumn('user_id', 'integer', ['null' => true])
            ->update();
    }
}
