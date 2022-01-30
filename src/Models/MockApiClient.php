<?php

namespace Crm\MobiletechModule\Models;

use Crm\MobiletechModule\Repository\MobiletechOutboundMessagesRepository;
use Nette\Database\Table\ActiveRow;
use Ramsey\Uuid\Uuid;

class MockApiClient implements ApiClientInterface
{
    private const ESM = 'mock';

    private const DCS = 'mock';

    private $mobiletechOutboundMessagesRepository;

    public function __construct(MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository)
    {
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
    }

    public function send(ActiveRow $mobiletechOutboundMessage, string $content)
    {
        $this->mobiletechOutboundMessagesRepository->update($mobiletechOutboundMessage, [
            'mobiletech_id' => Uuid::uuid4()->toString(),
            'dcs' => self::DCS,
            'esm' => self::ESM,
        ]);

        $this->mobiletechOutboundMessagesRepository->updateStatus($mobiletechOutboundMessage, 'D1'); // fake instant delivery,
        return $mobiletechOutboundMessage;
    }
}
