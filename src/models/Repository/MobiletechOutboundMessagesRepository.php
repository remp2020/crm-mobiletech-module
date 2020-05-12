<?php

namespace Crm\MobiletechModule\Repository;

use Crm\ApplicationModule\Repository;
use Crm\MobiletechModule\Events\OutboundMessageStatusUpdatedEvent;
use League\Event\Emitter;
use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\IRow;

class MobiletechOutboundMessagesRepository extends Repository
{
    protected $tableName = 'mobiletech_outbound_messages';

    private $emitter;

    public function __construct(
        Context $database,
        IStorage $cacheStorage,
        Emitter $emitter
    ) {
        parent::__construct($database, $cacheStorage);
        $this->emitter = $emitter;
    }

    public function add(
        ?IRow $user,
        ?string $mobiletechId,
        IRow $mobiletechTemplate,
        string $servId,
        string $projectId,
        string $rcvMsgId,
        string $from,
        string $to,
        string $billKey,
        string $contentLength,
        ?string $dcs,
        ?string $esm,
        string $operatorType
    ) {
        return $this->insert([
            'user_id' => $user->id ?? null,
            'mobiletech_id' => $mobiletechId,
            'mobiletech_template_id' => $mobiletechTemplate->id,
            'serv_id' => $servId,
            'project_id' => $projectId,
            'rcv_msg_id' => $rcvMsgId,
            'from' => $from,
            'to' => $to,
            'billkey' => $billKey,
            'content_length' => $contentLength,
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

    public function findByPayment(IRow $payment)
    {
        return $payment->related('mobiletech_outbound_messages')
            ->order('created_at DESC')
            ->limit(1)
            ->fetch();
    }

    public function updateStatus(IRow $row, string $status)
    {
        $this->update($row, [
            'status' => $status,
        ]);
        $this->emitter->emit(new OutboundMessageStatusUpdatedEvent($row));
    }

    public function update(IRow &$row, $data)
    {
        $data['updated_at'] = new \DateTime();
        return parent::update($row, $data);
    }
}
