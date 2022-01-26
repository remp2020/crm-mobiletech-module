<?php

namespace Crm\MobiletechModule\Tests;

use Crm\ApplicationModule\Tests\DatabaseTestCase;
use Crm\MobiletechModule\Repository\MobiletechAlreadyExistsException;
use Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository;
use Crm\UsersModule\Repository\UsersRepository;
use Nette\Database\Table\ActiveRow;

class MobiletechPhoneNumbersRepositoryTest extends DatabaseTestCase
{
    /** @var MobiletechPhoneNumbersRepository */
    private $mobiletechPhoneNumbersRepository;

    /** @var UsersRepository */
    private $usersRepository;

    /** @var ActiveRow */
    private $user = null;

    const EMAIL = 'sms@sms.sk';
    const PASSWORD = 'password';
    const PHONE_NUMBER = '0900123456';
    const PHONE_NUMBER_MISSING = '404';

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

        $this->mobiletechPhoneNumbersRepository = $this->inject(MobiletechPhoneNumbersRepository::class);
        $this->usersRepository = $this->inject(UsersRepository::class);

        $this->user = $this->loadUser(
            self::EMAIL,
            self::PASSWORD
        );
    }

    public function testAdd()
    {
        $mobiletechPhone = $this->mobiletechPhoneNumbersRepository->add(self::PHONE_NUMBER, $this->user);

        $this->assertEquals(self::PHONE_NUMBER, $mobiletechPhone->phone_number);
        $this->assertEquals($this->user->id, $mobiletechPhone->user_id);
    }

    public function testAddAlreadyAdded()
    {
        // add phone number; tested in previous test; should be safe to use
        $this->mobiletechPhoneNumbersRepository->add(self::PHONE_NUMBER, $this->user);

        // try to add second time
        $this->expectException(MobiletechAlreadyExistsException::class);
        $this->mobiletechPhoneNumbersRepository->add(self::PHONE_NUMBER, $this->user);
    }

    public function testFindByMobilePhoneNumberMissing()
    {
        $mobiletechPhone = $this->mobiletechPhoneNumbersRepository->findByMobilePhoneNumber(self::PHONE_NUMBER_MISSING);
        $this->assertNull($mobiletechPhone);
    }

    public function testFindByMobilePhoneNumberAdded()
    {
        // add phone number; tested in previous test; should be safe to use
        $this->mobiletechPhoneNumbersRepository->add(self::PHONE_NUMBER, $this->user);

        $mobiletechPhone = $this->mobiletechPhoneNumbersRepository->findByMobilePhoneNumber(self::PHONE_NUMBER);
        $this->assertEquals(self::PHONE_NUMBER, $mobiletechPhone->phone_number);
        $this->assertEquals($this->user->id, $mobiletechPhone->user_id);
    }

    private function loadUser(string $email, string $password, $role = UsersRepository::ROLE_USER, $active = true): ActiveRow
    {
        $user = $this->usersRepository->getByEmail($email);
        if (!$user) {
            $user = $this->usersRepository->add($email, $password, $role, (int)$active);
        }

        return $user;
    }
}
