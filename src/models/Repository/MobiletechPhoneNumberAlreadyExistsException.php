<?php

namespace Crm\MobiletechModule\Repository;

use Nette\Database\UniqueConstraintViolationException;

class MobiletechPhoneNumberAlreadyExistsException extends UniqueConstraintViolationException
{

}
