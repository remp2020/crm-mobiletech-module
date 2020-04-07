<?php

namespace Crm\MobiletechModule\Tests;

use Crm\ApplicationModule\Tests\DatabaseTestCase;
use Crm\MobiletechModule\Authenticator\MobiletechAuthenticator;
use Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository;
use Crm\UsersModule\Auth\UserAuthenticator;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Database\Table\IRow;
use Nette\Security\AuthenticationException;

class MobiletechAuthenticatorTest extends DatabaseTestCase
{
    /** @var MobiletechAuthenticator */
    private $mobiletechAuthenticator;

    /** @var MobiletechPhoneNumbersRepository */
    private $mobiletechPhoneNumbersRepository;

    /** @var UsersRepository */
    private $usersRepository;

    const EMAIL = 'sms@sms.sk';

    const PASSWORD = 'password';
    const PASSWORD_INCORRECT = 'wrong_password';

    const PHONE_NUMBER = '0900123456';
    const PHONE_NUMBER_INCORRECT = '0900999999';

    public function requiredRepositories(): array
    {
        return [
            UsersRepository::class,
            MobiletechPhoneNumbersRepository::class,
        ];
    }

    public function requiredSeeders(): array
    {
        return [];
    }

    public function setUp(): void
    {
        $this->refreshContainer();
        parent::setUp();

        $this->mobiletechAuthenticator = $this->inject(MobiletechAuthenticator::class);
        $this->mobiletechPhoneNumbersRepository = $this->inject(MobiletechPhoneNumbersRepository::class);
        $this->usersRepository = $this->inject(UsersRepository::class);
    }

    /**
     * @dataProvider validCredentialProvider
     */
    public function testValidCredentialsUserFound($credentials)
    {
        $user = $this->loadUser(
            self::EMAIL,
            self::PASSWORD,
            self::PHONE_NUMBER
        );
        $this->mobiletechAuthenticator->setCredentials($credentials);

        $authenticatedUser = $this->mobiletechAuthenticator->authenticate();
        $this->assertEquals($user->id, $authenticatedUser->id);
    }

    public function validCredentialProvider(): array
    {
        return [
            [[
                'mobile_phone' => self::PHONE_NUMBER,
                'password' => self::PASSWORD,
            ]],
            // for compatibility with existing login forms;
            // username field can be used for phone
            [[
                'username' => self::PHONE_NUMBER,
                'password' => self::PASSWORD,
            ]],
        ];
    }

    public function testUserNotFound()
    {
        // no user seeded
        $credentials = [
            'mobile_phone' => self::PHONE_NUMBER,
            'password' => self::PASSWORD
        ];
        $this->mobiletechAuthenticator->setCredentials($credentials);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode(UserAuthenticator::IDENTITY_NOT_FOUND);
        $user = $this->mobiletechAuthenticator->authenticate();
    }


    /**
     * @dataProvider missingCredentialProvider
     */
    public function testMissingCredentials($credentials)
    {
        $this->mobiletechAuthenticator->setCredentials($credentials);

        $user = $this->mobiletechAuthenticator->authenticate();
        $this->assertFalse($user);
    }

    public function missingCredentialProvider(): array
    {
        return [
            // all missing
            [[]],
            // password missing
            [['mobile_phone' => self::PHONE_NUMBER]],
            // mobile phone / username missing
            [['password' => self::PASSWORD]],
            // password missing; used username field instead of mobile phone
            [['username' => self::PHONE_NUMBER]],
            // only email and correct password; missing phone
            [[
                'username' => self::EMAIL,
                'password' => self::PASSWORD,
            ]],
        ];
    }

    /**
     * @dataProvider invalidCredentialProvider
     */
    public function testInvalidCredentials($credentials, $authExceptionCode)
    {
        $user = $this->loadUser(
            self::EMAIL,
            self::PASSWORD,
            self::PHONE_NUMBER
        );
        $this->mobiletechAuthenticator->setCredentials($credentials);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionCode($authExceptionCode);
        $user = $this->mobiletechAuthenticator->authenticate();
    }

    public function invalidCredentialProvider(): array
    {
        return [
            // invalid only password
            [
                [
                    'mobile_phone' => self::PHONE_NUMBER,
                    'password' => self::PASSWORD_INCORRECT,
                ],
                'auth_exception_code' => UserAuthenticator::INVALID_CREDENTIAL,
            ],
            // invalid only phone number
            [
                [
                    'mobile_phone' => self::PHONE_NUMBER_INCORRECT,
                    'password' => self::PASSWORD
                ],
                'auth_exception_code' => UserAuthenticator::IDENTITY_NOT_FOUND,
            ],
            // invalid both
            [
                [
                    'mobile_phone' => self::PHONE_NUMBER_INCORRECT,
                    'password' => self::PASSWORD_INCORRECT
                ],
                'auth_exception_code' => UserAuthenticator::IDENTITY_NOT_FOUND,
            ],
        ];
    }

    private function loadUser(string $email, string $password, ?string $phoneNumber = null, $role = UsersRepository::ROLE_USER, $active = true): IRow
    {
        $user = $this->usersRepository->getByEmail($email);
        if (!$user) {
            $user = $this->usersRepository->add($email, $password, '', '', $role, (int)$active);
        }

        $mobiletechPhone = $this->mobiletechPhoneNumbersRepository->findByMobilePhoneNumber($phoneNumber);
        if (!$mobiletechPhone) {
            $this->mobiletechPhoneNumbersRepository->add($phoneNumber, $user);
        }

        return $user;
    }
}
