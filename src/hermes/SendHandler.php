<?php

namespace Crm\MobiletechModule\Hermes;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\MobiletechModule\Config;
use Crm\MobiletechModule\Model\ApiClientInterface;
use Crm\MobiletechModule\Repository\MobiletechInboundMessagesRepository;
use Crm\MobiletechModule\Repository\MobiletechTemplatesRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class SendHandler implements HandlerInterface
{
    private $mobiletechInboundMessagesRepository;

    private $mobiletechTemplatesRepository;

    private $mobiletechApiClient;

    private $applicationConfig;

    private $usersRepository;

    public function __construct(
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        MobiletechTemplatesRepository $mobiletechTemplatesRepository,
        ApiClientInterface $mobiletechApiClient,
        ApplicationConfig $applicationConfig,
        UsersRepository $usersRepository
    ) {
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->mobiletechTemplatesRepository = $mobiletechTemplatesRepository;
        $this->mobiletechApiClient = $mobiletechApiClient;
        $this->applicationConfig = $applicationConfig;
        $this->usersRepository = $usersRepository;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mobiletech_inbound_message_id'])) {
            // TODO: handle push messages without originating message (recurring payments)
            return false;
        }

        $userId = $payload['user_id'] ?? null;
        if (!$userId) {
            Debugger::log('Attempt to send Mobiletech message without user ID', ILogger::ERROR);
            return false;
        }
        $user = $this->usersRepository->find($userId);
        if (!$user) {
            Debugger::log('Attempt to send Mobiletech message referencing user that does not exist: ' . $userId, ILogger::ERROR);
            return false;
        }

        $phoneNumber = $payload['phone_number'] ?? null;
        if (!$phoneNumber) {
            Debugger::log('Attempt to send Mobiletech message without phone number', ILogger::ERROR);
            return false;
        }

        $billKey = $payload['bill_key'] ?? null;
        if (!$billKey) {
            $billKey = $this->applicationConfig->get(Config::BILLKEY_FREE);
        }
        if (!$billKey) {
            Debugger::log('Attempt to send Mobiletech message without bill key', ILogger::ERROR);
            return false;
        }

        if (!isset($payload['template_code'])) {
            Debugger::log('Attempt to send Mobiletech message, without template_code', ILogger::ERROR);
            return false;
        }
        $mobiletechTemplate = $this->mobiletechTemplatesRepository->findByCode($payload['template_code']);
        if (!$mobiletechTemplate) {
            Debugger::log('Attempt to send Mobiletech message referencing template that does not exist: '. $payload['template_code'], ILogger::ERROR);
            return false;
        }

        $inboundMessage = $this->mobiletechInboundMessagesRepository->find($payload['mobiletech_inbound_message_id']);
        if (!$inboundMessage) {
            Debugger::log('Attempt to send Mobiletech message referencing inbound ID that does not exist: ' . $payload['mobiletech_inbound_message_id'], ILogger::ERROR);
            return false;
        }

        $servId = $inboundMessage->serv_id;
        $projectId = $inboundMessage->project_id;
        $rcvMsgId = $inboundMessage->mobiletech_id;
        $from = $inboundMessage->to; // intentional swap
        $to = $phoneNumber;
        $content = $this->getContent($mobiletechTemplate->content, $payload['params']);
        $operatorType = $inboundMessage->operator_type;

        $mobiletechOutbound = $this->mobiletechApiClient->send(
            $user,
            $mobiletechTemplate,
            $servId,
            $projectId,
            $rcvMsgId,
            $from,
            $to,
            $billKey,
            $content,
            $operatorType
        );

        return true;
    }

    private function getContent($templateContent, $params)
    {
        $loader = new \Twig\Loader\ArrayLoader([
            'template' => $templateContent,
        ]);
        $twig = new \Twig\Environment($loader);
        return $twig->render('template', $params);
    }
}
