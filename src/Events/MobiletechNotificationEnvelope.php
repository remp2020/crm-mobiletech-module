<?php

namespace Crm\MobiletechModule\Events;

use Nette\Database\Table\ActiveRow;

class MobiletechNotificationEnvelope
{
    private $mobiletechInboundMessage;

    private $projectId;

    private $rcvMsgId;

    private $servId;

    private $fromShortNumber;

    private $toPhoneNumber;

    private $billKey;

    public function __construct(
        ?ActiveRow $mobiletechInboundMessage,
        ?string $billKey = null,
        ?string $servId = null,
        ?string $fromShortNumber = null,
        ?string $toPhoneNumber = null,
    ) {
        if ($servId) {
            $this->servId = $servId;
        } elseif ($mobiletechInboundMessage) {
            $this->servId = $mobiletechInboundMessage->serv_id;
        } else {
            throw new \Exception('Unable to create Mobiletech Notification Envelope, neither inbound message nor explicit servId parameter was provided');
        }

        if ($fromShortNumber) {
            $this->fromShortNumber = $fromShortNumber;
        } elseif ($mobiletechInboundMessage) {
            $this->fromShortNumber = $mobiletechInboundMessage->to;
        } else {
            throw new \Exception('Unable to create Mobiletech Notification Envelope, neither inbound message nor explicit fromShortNumber parameter was provided');
        }

        if ($toPhoneNumber) {
            $this->toPhoneNumber = $toPhoneNumber;
        } elseif ($mobiletechInboundMessage) {
            $this->toPhoneNumber = $mobiletechInboundMessage->from;
        } else {
            throw new \Exception('Unable to create Mobiletech Notification Envelope, neither inbound message nor explicit toPhoneNumber parameter was provided');
        }

        $this->billKey = $billKey;
        $this->mobiletechInboundMessage = $mobiletechInboundMessage;
        if ($mobiletechInboundMessage) {
            $this->rcvMsgId = $mobiletechInboundMessage->mobiletech_id;
            $this->projectId = $mobiletechInboundMessage->project_id;
        }
    }

    public function getMobiletechInboundMessage(): ?ActiveRow
    {
        return $this->mobiletechInboundMessage;
    }

    public function getServId(): string
    {
        return $this->servId;
    }

    public function getFromShortNumber(): string
    {
        return $this->fromShortNumber;
    }

    public function getToPhoneNumber(): string
    {
        return $this->toPhoneNumber;
    }

    public function getBillKey(): ?string
    {
        return $this->billKey;
    }

    public function getRcvMsgId(): ?string
    {
        return $this->rcvMsgId;
    }

    public function getProjectId(): ?string
    {
        return $this->projectId;
    }
}
