<?php

namespace Crm\MobiletechModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\ActiveRow;

class MobiletechPhoneNumbersRepository extends Repository
{
    protected $tableName = 'mobiletech_phone_numbers';

    public function findByMobilePhoneNumber(string $mobilePhoneNumber): ?ActiveRow
    {
        $result = $this->findBy('phone_number', $mobilePhoneNumber);
        if (!$result) {
            return null;
        }
        return $result;
    }
}
