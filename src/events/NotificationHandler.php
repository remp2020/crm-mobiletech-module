<?php

namespace Crm\MobiletechModule\Events;

use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository;
use Crm\MobiletechModule\Repository\MobiletechTemplatesRepository;
use Crm\UsersModule\Events\NotificationEvent;
use League\Event\AbstractListener;
use League\Event\EventInterface;
use Tomaj\Hermes\Emitter;

class NotificationHandler extends AbstractListener
{
    private $hermesEmitter;

    private $mobiletechPhoneNumbersRepository;

    private $mobiletechTemplatesRepository;

    public function __construct(
        Emitter $hermesEmitter,
        MobiletechPhoneNumbersRepository $mobiletechPhoneNumbersRepository,
        MobiletechTemplatesRepository $mobiletechTemplatesRepository
    ) {
        $this->hermesEmitter = $hermesEmitter;
        $this->mobiletechPhoneNumbersRepository = $mobiletechPhoneNumbersRepository;
        $this->mobiletechTemplatesRepository = $mobiletechTemplatesRepository;
    }

    public function handle(EventInterface $event)
    {
        if (!($event instanceof NotificationEvent)) {
            throw new \Exception("Unable to handle event, expected NotificationEvent");
        }

        $userId = $event->getUser()->id ?? null;
        if (!$userId) {
            // return if event's user is instance of DataRow with incomplete set of attributes
            return;
        }

        $mobiletechTemplate = $this->mobiletechTemplatesRepository->findByCode($event->getTemplateCode());
        if (!$mobiletechTemplate) {
            // return if there's no template to send
            return;
        }

        $payload = [
            'user_id' => $userId,
            'template_code' => $event->getTemplateCode(),
            'params' => $event->getParams(),
            'context' => $event->getContext(),
        ];

        if ($event instanceof MobiletechNotificationEvent) {
            // if there is a triggering message, respond to sender's phone number and link inbound message
            $payload['mobiletech_inbound_message_id'] = $event->getMobiletechInboundMessage()->id;
            $payload['phone_number'] = $event->getMobiletechInboundMessage()->from;
            $payload['bill_key'] = $event->getBillKey();
        } else {
            // if there isn't a triggering message, find phone number based on the user settings
            $mobiletechPhoneNumber = $this->mobiletechPhoneNumbersRepository->findByUserId($userId);
            if (!$mobiletechPhoneNumber) {
                // we don't have a phone number for this user, nothing will be sent
                return;
            }
            $payload['phone_number'] = $mobiletechPhoneNumber->phone_number;
        }

        // making sure the number is in the right format
        if (strpos($payload['phone_number'], '+') === false) {
            $payload['phone_number'] = '+421' . substr($payload['phone_number'], 1);
        }

        $scheduleAt = null;
        if ($event->getScheduleAt()) {
            $scheduleAt = $event->getScheduleAt()->getTimestamp();
        }

        $this->hermesEmitter->emit(new HermesMessage('mobiletech-send', $payload, null, null, $scheduleAt));
    }
}
