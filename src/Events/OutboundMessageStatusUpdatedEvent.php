<?php

namespace Crm\MobiletechModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\IRow;

class OutboundMessageStatusUpdatedEvent extends AbstractEvent
{
    private $mobiletechOutboundMessage;

    public function __construct(IRow $mobiletechOutboundMessage)
    {
        $this->mobiletechOutboundMessage = $mobiletechOutboundMessage;
    }

    public function getMobiletechOutboundMessage(): IRow
    {
        return $this->mobiletechOutboundMessage;
    }
}
