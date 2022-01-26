<?php

namespace Crm\MobiletechModule\Models;

use Nette\Database\Table\IRow;

interface ApiClientInterface
{
    public function send(
        IRow $mobiletechOutboundMessage,
        string $content
    );
}
