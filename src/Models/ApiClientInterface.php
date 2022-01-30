<?php

namespace Crm\MobiletechModule\Models;

use Nette\Database\Table\ActiveRow;

interface ApiClientInterface
{
    public function send(
        ActiveRow $mobiletechOutboundMessage,
        string $content
    );
}
