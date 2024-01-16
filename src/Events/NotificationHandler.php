<?php

namespace Crm\MobiletechModule\Events;

use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\MobiletechModule\Models\Config;
use Crm\MobiletechModule\Models\OperatorTypeResolver;
use Crm\MobiletechModule\Repositories\MobiletechOutboundMessagesRepository;
use Crm\MobiletechModule\Repositories\MobiletechPhoneNumbersRepository;
use Crm\MobiletechModule\Repositories\MobiletechTemplatesRepository;
use Crm\UsersModule\Events\NotificationEvent;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Tomaj\Hermes\Emitter;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class NotificationHandler extends AbstractListener
{
    private $hermesEmitter;

    private $mobiletechPhoneNumbersRepository;

    private $mobiletechTemplatesRepository;

    private $mobiletechOutboundMessagesRepository;

    private $applicationConfig;

    private $operatorTypeResolver;

    public function __construct(
        Emitter $hermesEmitter,
        MobiletechPhoneNumbersRepository $mobiletechPhoneNumbersRepository,
        MobiletechTemplatesRepository $mobiletechTemplatesRepository,
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository,
        ApplicationConfig $applicationConfig,
        OperatorTypeResolver $operatorTypeResolver
    ) {
        $this->hermesEmitter = $hermesEmitter;
        $this->mobiletechPhoneNumbersRepository = $mobiletechPhoneNumbersRepository;
        $this->mobiletechTemplatesRepository = $mobiletechTemplatesRepository;
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
        $this->applicationConfig = $applicationConfig;
        $this->operatorTypeResolver = $operatorTypeResolver;
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

        $billKey = null;
        $rcvMsgId = null;
        $projectId = null;
        $toPhoneNumber = null;
        $servId = null;
        $fromShortNumber = null;

        if ($event instanceof MobiletechNotificationEvent) {
            $billKey = $event->getBillKey();
            $rcvMsgId = $event->getRcvMsgId();
            $projectId = $event->getProjectId();
            $toPhoneNumber = $event->getToPhoneNumber();
            $servId = $event->getServId();
            $fromShortNumber = $event->getFromShortNumber();
        }

        if (!$toPhoneNumber && $event->getUser()) {
            // if this isn't mobiletech notification, find phone number based on the user settings
            $mobiletechPhoneNumber = $this->mobiletechPhoneNumbersRepository->findByUserId($event->getUser()->id);
            if (!$mobiletechPhoneNumber) {
                // we don't have a phone number for this user, nothing will be sent
                return;
            }
            $toPhoneNumber = $mobiletechPhoneNumber->phone_number;
        }

        // validate bill key
        if (!$billKey) {
            $billKey = $this->applicationConfig->get(Config::BILLKEY_FREE);
        }
        if (!$billKey) {
            throw new \Exception('Attempt to send Mobiletech message without bill key');
        }

        $lastWithSameBillKey = $this->mobiletechOutboundMessagesRepository->findLastByBillKey($billKey);

        // validate serv id
        if (!$servId && $lastWithSameBillKey) {
            $servId = $lastWithSameBillKey->serv_id;
        }
        if (!$servId) {
            // return if we didn't figure out servId automatically
            return;
        }

        // validate message sender
        if (!$fromShortNumber && $lastWithSameBillKey) {
            $fromShortNumber = $lastWithSameBillKey->from;
        }
        if (!$fromShortNumber) {
            // return if we don't know sender number
            return;
        }

        // making sure the number is in the right format
        if (strpos($toPhoneNumber, '+') === false) {
            $toPhoneNumber = '+421' . substr($toPhoneNumber, 1);
        }

        // resolve operator code
        $operatorType = $this->operatorTypeResolver->resolve($toPhoneNumber);
        if (!$operatorType) {
            // return if we don't know operator type
            return;
        }

        $content = $this->getContent($mobiletechTemplate->content, $event->getParams());
        $expiration = 100;
        if (!$rcvMsgId) {
            $expiration = 600;
        }

        $mobiletechOutboundMessage = $this->mobiletechOutboundMessagesRepository->add(
            $event->getUser(),
            null, // we don't have this yet, will be updated once the message is sent
            $mobiletechTemplate,
            $servId, // can be constant (one service ID per publisher according to Mobiletech)
            $projectId, // should be OK when it's empty (according to Mobiletech)
            $rcvMsgId,
            $fromShortNumber,
            $toPhoneNumber,
            $billKey,
            strlen($content),
            $expiration,
            null,
            null,
            $operatorType
        );
        if ($event instanceof MobiletechNotificationEvent) {
            $event->setMobiletechOutboundMessage($mobiletechOutboundMessage);
        }

        $payload = [
            'content' => $content,
            'mobiletech_outbound_message_id' => $mobiletechOutboundMessage->id,
        ];

        $scheduleAt = null;
        if ($event->getScheduleAt()) {
            $scheduleAt = $event->getScheduleAt()->getTimestamp();
        }

        $this->hermesEmitter->emit(new HermesMessage('mobiletech-send', $payload, null, null, $scheduleAt), HermesMessage::PRIORITY_DEFAULT);
    }

    private function getContent($templateContent, $params)
    {
        $loader = new ArrayLoader([
            'template' => $templateContent,
        ]);
        $twig = new Environment($loader);
        return $twig->render('template', $params);
    }
}
