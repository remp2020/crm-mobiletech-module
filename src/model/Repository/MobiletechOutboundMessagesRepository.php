<?php

namespace Crm\MobiletechModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\IRow;

class MobiletechOutboundMessagesRepository extends Repository
{
    protected $tableName = 'mobiletech_outbound_messages';

    public function add(
        IRow $user,
        string $mobiletechId,
        IRow $mobiletechTemplate,
        string $servId,
        string $projectId,
        string $from,
        string $to,
        string $billKey,
        string $content,
        string $contentCoding,
        string $dcs,
        string $esm,
        string $operatorType
    ) {
        return $this->insert([
            'user_id' => $user->id,
            'mobiletech_id' => $mobiletechId,
            'mobiletech_template_id' => $mobiletechTemplate->id,
            'serv_id' => $servId,
            'project_id' => $projectId,
            'from' => $from,
            'to' => $to,
            'billkey' => $billKey,
            'content' => $content,
            'content_coding' => $contentCoding,
            'dcs' => $dcs,
            'esm' => $esm,
            'operator_type' => $operatorType,
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime()
        ]);
    }

    public function findByMobiletechId($mobiletechId)
    {
        return $this->findBy('mobiletech_id', $mobiletechId);
    }

    public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }
}
