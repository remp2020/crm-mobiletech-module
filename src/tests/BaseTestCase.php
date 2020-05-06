<?php

namespace Crm\MobiletechModule\Tests;

use Crm\ApplicationModule\Tests\DatabaseTestCase;
use Crm\MobiletechModule\Api\MobiletechWebhookApiHandler;
use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\MobiletechModule\MobiletechModule;
use Crm\MobiletechModule\Repository\MobiletechInboundMessagesRepository;
use Crm\MobiletechModule\Repository\MobiletechOutboundMessagesRepository;
use Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Kdyby\Translation\Translator;
use League\Event\Emitter;
use Nette\Utils\DateTime;
use Tomaj\Hermes\Dispatcher;

abstract class BaseTestCase extends DatabaseTestCase
{
    /** @var Dispatcher */
    protected $dispatcher;

    /** @var Emitter */
    protected $emitter;

    /** @var MobiletechPhoneNumbersRepository */
    protected $mobiletechPhoneNumbersRepository;

    /** @var UsersRepository */
    protected $usersRepository;

    /** @var MobiletechWebhookApiHandler */
    protected $mobiletechWebhookApiHandler;

    /** @var TestMobiletechNotificationHandler */
    protected $testNotificationHandler;

    protected function requiredRepositories(): array
    {
        return [
            UsersRepository::class,
            MobiletechPhoneNumbersRepository::class,
            MobiletechInboundMessagesRepository::class,
            MobiletechOutboundMessagesRepository::class,
        ];
    }

    protected function requiredSeeders(): array
    {
        return [];
    }

    protected function setUp(): void
    {
        $this->refreshContainer();
        parent::setUp();

        $this->dispatcher = $this->inject(Dispatcher::class);
        $this->emitter = $this->inject(Emitter::class);
        $mobiletechModule = new MobiletechModule($this->container, $this->inject(Translator::class));
        $mobiletechModule->registerHermesHandlers($this->dispatcher);

        $this->mobiletechPhoneNumbersRepository = $this->getRepository(MobiletechPhoneNumbersRepository::class);
        $this->usersRepository = $this->getRepository(UsersRepository::class);
        $this->mobiletechWebhookApiHandler = $this->inject(MobiletechWebhookApiHandler::class);

        $this->testNotificationHandler = new TestMobiletechNotificationHandler();
        // Mobiletech notification is going to be handled by test handler
        $this->emitter->addListener(MobiletechNotificationEvent::class, $this->testNotificationHandler);
    }

    protected function getResponsesTo($number): array
    {
        return $this->testNotificationHandler->notificationsSentTo($number);
    }


    protected function generateSms($from, $to, $content, $id = null)
    {
        if (!$id) {
            $id = uniqid('', true);
        }

        $receiveDate = (new DateTime())->format('ymdHis');

        $sms = <<<SMS
<?xml version="1.0" encoding="UTF-8" ?>
<message command="receive">
    <id>{$id}</id>
    <serv_id>1703</serv_id>
    <project_id>76</project_id>
    <from>{$from}</from>
    <to>{$to}</to>
    <content>{$content}</content>
    <content_coding>text</content_coding>
    <dcs>0</dcs>
    <esm>0</esm>
    <operator_type>O</operator_type>
    <receive_date>{$receiveDate}</receive_date>
</message>
SMS;
        return $sms;
    }
}
