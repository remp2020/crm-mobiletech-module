<?php

namespace Crm\MobiletechModule\Repositories;

use Nette\Database\UniqueConstraintViolationException;

class MobiletechAlreadyExistsException extends UniqueConstraintViolationException
{

}
