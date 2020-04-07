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
     * @throws MobiletechPhoneNumberAlreadyExistsException Thrown if phone number already exists.
     */
    public function add(string $mobilePhoneNumber, ActiveRow $user): IRow
    {
        $now = new DateTime();

        try {
            return $this->insert([
                'phone_number' => $mobilePhoneNumber,
                'user_id' => $user->id,
                'created_at' => $now,
                'modified_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new MobiletechPhoneNumberAlreadyExistsException('Mobiletech phone number already exists: ' . $e->getMessage(), $e->getCode());
        }
    }

    public function findByMobilePhoneNumber(string $mobilePhoneNumber): ?ActiveRow
    {
        $result = $this->findBy('phone_number', $mobilePhoneNumber);
        if (!$result) {
            return null;
        }
        return $result;
    }
}
