<?php

namespace Crm\MobiletechModule\Repositories;

use Crm\ApplicationModule\Repository;
use Crm\MobiletechModule\Models\DeliveryStatus;
use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;

class MobiletechInboundMessagesRepository extends Repository
{
    protected $tableName = 'mobiletech_inbound_messages';

    private $deliveryStatus;

    public function __construct(Explorer $database, DeliveryStatus $deliveryStatus, Storage $cacheStorage = null)
    {
        parent::__construct($database, $cacheStorage);
        $this->deliveryStatus = $deliveryStatus;
    }

    final public function add(
        ?ActiveRow $user,
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
            'user_id' => $user->id ?? null,
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

    final public function findByMobiletechId($mobiletechId)
    {
        return $this->findBy('mobiletech_id', $mobiletechId);
    }

    final public function findLastSuccessfulByPhoneNumber($phoneNumber)
    {
        return $this->getTable()
            ->where([
                'to' => $phoneNumber,
            ])
            ->order('created_at DESC')
            ->limit(1)
            ->fetch();
    }

    final public function update(ActiveRow &$row, $data)
    {
        $data['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }
}
