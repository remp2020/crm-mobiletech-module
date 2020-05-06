<?php

namespace Crm\MobiletechModule\Tests;

use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\UsersModule\Auth\UserManager;
use Nette\Database\Table\ActiveRow;
use Nette\Http\Response;
use Nette\Security\Passwords;

class ChangePasswordTest extends BaseTestCase
{
    /** @var ActiveRow */
    private $user = null;

    /** @var UserManager */
    private $userManager;

    const EMAIL = 'sms@sms.sk';
    const PASSWORD = 'password';
    const PHONE_NUMBER = '0900123456';

    const ENDPOINT_NUMBER = '8787';

    public function setUp(): void
    {
        parent::setUp();
        $this->userManager = $this->inject(UserManager::class);
    }

    public function testChangePasswordForAuthenticatedUser()
    {
        $this->user = $this->userManager->addNewUser(self::EMAIL, false);
        $this->mobiletechPhoneNumbersRepository->add(self::PHONE_NUMBER, $this->user);

        $sms = $this->generateSms(self::PHONE_NUMBER, self::ENDPOINT_NUMBER, 'HESLO');
        $this->mobiletechWebhookApiHandler->mockRawPayload($sms);
        $response = $this->mobiletechWebhookApiHandler->handleInTest();
        $this->assertEquals(Response::S200_OK, $response->getHttpCode());

        $this->dispatcher->handle(); // Handle 'mobiletech-inbound' by Hermes

        $notifications = $this->getResponsesTo(self::PHONE_NUMBER);
        $this->assertCount(1, $notifications);

        /** @var MobiletechNotificationEvent $notification */
        $notification = $notifications[0];
        $this->assertEquals('mobiletech_reset_password_for_authenticated_user', $notification->getTemplateCode());
        $newPassword = $notification->getParams()['password'];
        Passwords::verify($newPassword, $this->usersRepository->getByEmail(self::EMAIL)->password);
    }

    public function testChangePasswordForUnauthenticatedUser()
    {
        $sms = $this->generateSms(self::PHONE_NUMBER, self::ENDPOINT_NUMBER, 'HESLO');
        $this->mobiletechWebhookApiHandler->mockRawPayload($sms);
        $response = $this->mobiletechWebhookApiHandler->handleInTest();
        $this->assertEquals(Response::S200_OK, $response->getHttpCode());

        $this->dispatcher->handle(); // Handle 'mobiletech-inbound' by Hermes

        $notifications = $this->getResponsesTo(self::PHONE_NUMBER);
        $this->assertCount(1, $notifications);

        /** @var MobiletechNotificationEvent $notification */
        $notification = $notifications[0];
        $this->assertEquals('mobiletech_unregistered_number', $notification->getTemplateCode());
    }
}
