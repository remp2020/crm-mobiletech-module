<?php

namespace Crm\MobiletechModule\Events;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\MobiletechModule\Models\Config;
use Crm\MobiletechModule\Repository\MobiletechOutboundMessagesRepository;
use Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository;
use Crm\MobiletechModule\Repository\MobiletechTemplatesRepository;
use Crm\UsersModule\Events\NotificationEvent;
use Crm\UsersModule\Repository\UsersRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Tomaj\Hermes\Emitter;

class NotificationHandler extends AbstractListener
{
    private $hermesEmitter;

    private $mobiletechPhoneNumbersRepository;

    private $mobiletechTemplatesRepository;

    private $mobiletechOutboundMessagesRepository;

    private $usersRepository;

    private $applicationConfig;

    public function __construct(
        Emitter $hermesEmitter,
        MobiletechPhoneNumbersRepository $mobiletechPhoneNumbersRepository,
        MobiletechTemplatesRepository $mobiletechTemplatesRepository,
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository,
        UsersRepository $usersRepository,
        ApplicationConfig $applicationConfig
    ) {
        $this->hermesEmitter = $hermesEmitter;
        $this->mobiletechPhoneNumbersRepository = $mobiletechPhoneNumbersRepository;
        $this->mobiletechTemplatesRepository = $mobiletechTemplatesRepository;
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
        $this->usersRepository = $usersRepository;
        $this->applicationConfig = $applicationConfig;
    }

    public function handle(EventInterface $event)
    {
        if (!($event instanceof NotificationEvent)) {
            throw new \Exception("Unable to handle event, expected NotificationEvent");
        }

        $mobiletechTemplate = $this->mobiletechTemplatesRepository->findByCode($event->getTemplateCode());
        if (!$mobiletechTemplate) {
            // return if there's no template to send
            return;
        }

        $inboundMessage = null;
        $billKey = null;
        $phoneNumber = null;

        if ($event instanceof MobiletechNotificationEvent) {
            // if there is a triggering message, respond to sender's phone number and link inbound message
            $inboundMessage = $event->getMobiletechInboundMessage();
            $phoneNumber = $event->getMobiletechInboundMessage()->from;
            $billKey = $event->getBillKey();
        } elseif ($event->getUser()) {
            // if there isn't a triggering message, find phone number based on the user settings
            $mobiletechPhoneNumber = $this->mobiletechPhoneNumbersRepository->findByUserId($event->getUser()->id);
            if (!$mobiletechPhoneNumber) {
                // we don't have a phone number for this user, nothing will be sent
                return;
            }
            $phoneNumber = $mobiletechPhoneNumber->phone_number;
        }

        if (!$billKey) {
            $billKey = $this->applicationConfig->get(Config::BILLKEY_FREE);
        }
        if (!$billKey) {
            throw new \Exception('Attempt to send Mobiletech message without bill key');
        }

        // making sure the number is in the right format
        if (strpos($phoneNumber, '+') === false) {
            $phoneNumber = '+421' . substr($phoneNumber, 1);
        }

        $content = $this->getContent($mobiletechTemplate->content, $event->getParams());

        $mobiletechOutboundMessage = $this->mobiletechOutboundMessagesRepository->add(
            $event->getUser(),
            null, // we don't have this yet, will be updated once the message is sent
            $mobiletechTemplate,
            $inboundMessage->serv_id,
            $inboundMessage->project_id,
            $inboundMessage->mobiletech_id,
            $inboundMessage->to,
            $phoneNumber,
            $billKey,
            strlen($content),
            null,
            null,
            $inboundMessage->operator_type
        );
        $event->setMobiletechOutboundMessage($mobiletechOutboundMessage);

        $payload = [
            'content' => $content,
            'mobiletech_outbound_message_id' => $mobiletechOutboundMessage->id,
        ];

        $scheduleAt = null;
        if ($event->getScheduleAt()) {
            $scheduleAt = $event->getScheduleAt()->getTimestamp();
        }

        $this->hermesEmitter->emit(new HermesMessage('mobiletech-send', $payload, null, null, $scheduleAt));
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
