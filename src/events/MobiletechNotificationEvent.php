<?php

namespace Crm\MobiletechModule\Events;

use Crm\UsersModule\Events\NotificationEvent;
use League\Event\Emitter;
use Nette\Database\IRow;

/**
 * MobiletechNotificationEvent extends NotificationEvent for scenarios, when application needs to send notification
 * as a response to the received message (mobiletech inbound message).
 */
class MobiletechNotificationEvent extends NotificationEvent
{
    private $mobiletechInboundMessage;

    private $billKey;

    public function __construct(
        Emitter $emitter,
        IRow $mobiletechInboundMessage,
        string $billKey,
        IRow $user,
        string $templateCode,
        array $params = [],
        string $context = null,
        \DateTime $scheduleAt = null
    ) {
        $this->mobiletechInboundMessage = $mobiletechInboundMessage;
        $this->billKey = $billKey;
        parent::__construct(
            $emitter,
            $user,
            $templateCode,
            $params,
            $context,
            [],
            $scheduleAt
        );
    }

    public function getMobiletechInboundMessage(): IRow
    {
        return $this->mobiletechInboundMessage;
    }

    public function getBillKey(): string
    {
        return $this->billKey;
    }
}
