<?php

namespace Crm\MobiletechModule\Repository;

use Crm\ApplicationModule\Repository;
use Nette\Database\Table\IRow;

class MobiletechInboundMessagesRepository extends Repository
{
    protected $tableName = 'mobiletech_inbound_messages';

    public function add(
        string $mobiletechId,
        string $servId,
        string $projectId,
        string $from,
        string $to,
        string $content,
        string $contentCoding,
        string $dcs,
        string $esm,
        string $operatorType,
        \DateTime $receiveDate
    ) {
        return $this->insert([
            'mobiletech_id' => $mobiletechId,
            'serv_id' => $servId,
            'project_id' => $projectId,
            'from' => $from,
            'to' => $to,
            'content' => $content,
            'content_coding' => $contentCoding,
            'dcs' => $dcs,
            'esm' => $esm,
            'operator_type' => $operatorType,
            'receive_date' => $receiveDate,
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
