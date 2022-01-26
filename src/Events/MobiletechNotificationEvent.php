<?php

namespace Crm\MobiletechModule\Events;

use Crm\UsersModule\Events\NotificationEvent;
use League\Event\Emitter;
use Nette\Database\Table\IRow;

/**
 * MobiletechNotificationEvent extends NotificationEvent for scenarios, when application needs to send notification
 * as a response to the received message (mobiletech inbound message).
 *
 * Any listener actually sending the message is required to call `setMobiletechOutboundMessage` for further processing.
 */
class MobiletechNotificationEvent extends NotificationEvent
{
    private $mobiletechEnvelope;

    private $mobiletechOutboundMessage;

    public function __construct(
        Emitter $emitter,
        MobiletechNotificationEnvelope $mobiletechEnvelope,
        ?IRow $user,
        string $templateCode,
        array $params = [],
        string $context = null,
        \DateTime $scheduleAt = null
    ) {
        $this->mobiletechEnvelope = $mobiletechEnvelope;
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

    public function getMobiletechInboundMessage(): ?IRow
    {
        return $this->mobiletechEnvelope->getMobiletechInboundMessage();
    }

    public function getRcvMsgId(): ?string
    {
        return $this->mobiletechEnvelope->getRcvMsgId();
    }

    public function getBillKey(): ?string
    {
        return $this->mobiletechEnvelope->getBillKey();
    }

    public function getProjectId(): ?string
    {
        return $this->mobiletechEnvelope->getProjectId();
    }

    public function getToPhoneNumber(): string
    {
        return $this->mobiletechEnvelope->getToPhoneNumber();
    }

    public function getServId(): string
    {
        return $this->mobiletechEnvelope->getServId();
    }

    public function getFromShortNumber(): string
    {
        return $this->mobiletechEnvelope->getFromShortNumber();
    }

    public function setMobiletechOutboundMessage(IRow $mobiletechOutboundMessage): void
    {
        $this->mobiletechOutboundMessage = $mobiletechOutboundMessage;
    }

    public function getMobiletechOutboundMessage(): ?IRow
    {
        return $this->mobiletechOutboundMessage;
    }
}
