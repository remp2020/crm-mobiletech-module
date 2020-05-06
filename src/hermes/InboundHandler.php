<?php

namespace Crm\MobiletechModule\Hermes;

use Crm\MobiletechModule\Model\InboundSmsProcessor;
use Crm\MobiletechModule\Repository\MobiletechInboundMessagesRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class InboundHandler implements HandlerInterface
{
    private $mobiletechInboundMessagesRepository;

    private $inboundSmsProcessor;

    public function __construct(
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        InboundSmsProcessor $inboundSmsProcessor
    ) {
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->inboundSmsProcessor = $inboundSmsProcessor;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mobiletech_inbound_message_id'])) {
            return false;
        }
        $inboundMessage = $this->mobiletechInboundMessagesRepository->find($payload['mobiletech_inbound_message_id']);
        $this->inboundSmsProcessor->process($inboundMessage);
        return true;
    }
}
