<?php

namespace Crm\MobiletechModule\Repository;

use Nette\Database\UniqueConstraintViolationException;

class MobiletechAlreadyExistsException extends UniqueConstraintViolationException
{

}
