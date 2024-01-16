<?php

namespace Crm\MobiletechModule\Hermes;

use Crm\MobiletechModule\Models\ApiClientInterface;
use Crm\MobiletechModule\Repositories\MobiletechOutboundMessagesRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class SendHandler implements HandlerInterface
{
    private $mobiletechApiClient;

    private $mobiletechOutboundMessagesRepository;

    public function __construct(
        ApiClientInterface $mobiletechApiClient,
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository
    ) {
        $this->mobiletechApiClient = $mobiletechApiClient;
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();

        if (!isset($payload['mobiletech_outbound_message_id'])) {
            // there always need to be outbound message ready
            Debugger::log('Attempt to send mobiletech message without outbound message.', ILogger::ERROR);
            return false;
        }
        $outboundMessage = $this->mobiletechOutboundMessagesRepository->find($payload['mobiletech_outbound_message_id']);
        if (!$outboundMessage) {
            Debugger::log('Attempt to send mobiletech message with invalid reference to outbound message: ' . $payload['mobiletech_outbound_message_id'], ILogger::ERROR);
            return false;
        }

        if (!isset($payload['content'])) {
            Debugger::log('Attempt to send Mobiletech message, without content', ILogger::ERROR);
            return false;
        }

        $content = $payload['content'];
        if (mb_strlen($content) > 160) {
            Debugger::log("Mobiletech message longer than 160 characters, stripping. Message: '$content'", ILogger::ERROR);
            $content = mb_substr($content, 0, 160);
        }

        $this->mobiletechApiClient->send(
            $outboundMessage,
            $content
        );

        return true;
    }
}
