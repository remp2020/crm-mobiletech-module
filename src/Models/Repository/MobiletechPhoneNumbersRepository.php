<?php

namespace Crm\MobiletechModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\IRow;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\DateTime;

class MobiletechPhoneNumbersRepository extends Repository
{
    protected $tableName = 'mobiletech_phone_numbers';

    /**
     * @throws MobiletechAlreadyExistsException Thrown if phone number already exists.
     */
    final public function add(string $mobilePhoneNumber, ActiveRow $user): IRow
    {
        $now = new DateTime();

        try {
            return $this->insert([
                'phone_number' => $mobilePhoneNumber,
                'user_id' => $user->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new MobiletechAlreadyExistsException('Mobiletech phone number already exists: ' . $mobilePhoneNumber);
        }
    }

    final public function findByMobilePhoneNumber(string $mobilePhoneNumber): ?ActiveRow
    {
        if (strpos($mobilePhoneNumber, '+') !== false) {
            // convert international to local
            $mobilePhoneNumber = '0' . substr($mobilePhoneNumber, -9);
        }
        return $this->findBy('phone_number', $mobilePhoneNumber) ?: null;
    }

    final public function findByUserId(int $userId): ?ActiveRow
    {
        return $this->findBy('user_id', $userId) ?: null;
    }
}
