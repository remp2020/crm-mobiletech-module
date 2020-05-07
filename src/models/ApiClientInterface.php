<?php

namespace Crm\MobiletechModule\Models;

use Nette\Database\Table\IRow;

interface ApiClientInterface
{
    public function send(
        IRow $user,
        IRow $mobiletechTemplate,
        string $servId,
        string $projectid,
        string $rcvMsgId,
        string $from,
        string $to,
        string $billKey,
        string $content,
        string $operatorType
    );
}
