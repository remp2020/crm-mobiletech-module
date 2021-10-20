<?php

namespace Crm\MobiletechModule\Events;

use League\Event\AbstractEvent;
use Nette\Database\Table\ActiveRow;

class OutboundMessageStatusUpdatedEvent extends AbstractEvent
{
    private $mobiletechOutboundMessage;

    public function __construct(ActiveRow $mobiletechOutboundMessage)
    {
        $this->mobiletechOutboundMessage = $mobiletechOutboundMessage;
    }

    public function getMobiletechOutboundMessage(): ActiveRow
    {
        return $this->mobiletechOutboundMessage;
    }
}
