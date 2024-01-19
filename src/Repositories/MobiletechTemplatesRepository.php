<?php

namespace Crm\MobiletechModule\Repositories;

use Crm\ApplicationModule\Models\Database\Repository;
use Nette\Database\Table\ActiveRow;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\DateTime;

class MobiletechTemplatesRepository extends Repository
{
    protected $tableName = 'mobiletech_templates';

    public function add(string $code, string $content): ActiveRow
    {
        try {
            return $this->insert([
                'code' => $code,
                'content' => $content,
                'created_at' => new DateTime(),
                'updated_at' => new DateTime(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw new MobiletechAlreadyExistsException('Mobiletech template already exists: ' . $code);
        }
    }

    public function update(ActiveRow &$row, $data)
    {
        $data['updated_at'] = new DateTime();
        return parent::update($row, $data);
    }

    public function findByCode(string $code)
    {
        return $this->findBy('code', $code);
    }
}
