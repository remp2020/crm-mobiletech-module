<?php

namespace Crm\MobiletechModule\Models;

use Crm\MobiletechModule\Repository\MobiletechOutboundMessagesRepository;
use Nette\Database\Table\IRow;
use Ramsey\Uuid\Uuid;

class MockApiClient implements ApiClientInterface
{
    private const CONTENT_CODING = 'text'; // "text" or "base64"

    private const ESM = 'mock';

    private const DCS = 'mock';

    private $mobiletechOutboundMessagesRepository;

    public function __construct(
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository
    ) {
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
    }

    public function send(
        ?IRow $user,
        IRow $mobiletechTemplate,
        string $servId,
        string $projectId,
        string $rcvMsgId,
        string $from,
        string $to,
        string $billKey,
        string $content,
        string $operatorType
    ) {
        $outboundMessage = $this->mobiletechOutboundMessagesRepository->add(
            $user,
            Uuid::uuid4(),
            $mobiletechTemplate,
            $servId,
            $projectId,
            $from,
            $to,
            $billKey,
            $content,
            self::CONTENT_CODING,
            self::DCS,
            self::ESM,
            $operatorType
        );

        $this->mobiletechOutboundMessagesRepository->update($outboundMessage, [
            'status' => 'D1' // fake instant delivery,
        ]);

        return $outboundMessage;
    }
}
