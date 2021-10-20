<?php

namespace Crm\MobiletechModule\Models;

use Crm\ApiModule\Token\InternalToken;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\MobiletechModule\Repository\MobiletechOutboundMessagesRepository;
use GuzzleHttp\Client;
use Nette\Database\Table\ActiveRow;
use Spatie\ArrayToXml\ArrayToXml;

class MobiletechApiClient implements ApiClientInterface
{
    private const CONTENT_CODING = 'text'; // "text" or "base64"

    private const ESM = 0; // constant for common text, see SMPP v3.4 protocol for details

    private const DCS = 0; // constant for common text, see SMPP v3.4 protocol for details

    private $applicationConfig;

    private $mobiletechOutboundMessagesRepository;

    public function __construct(
        ApplicationConfig $applicationConfig,
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository
    ) {
        $this->applicationConfig = $applicationConfig;
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
    }

    public function send(
        ActiveRow $mobiletechOutboundMessage,
        string $content
    ) {

        $message = [
            'serv_id' => $mobiletechOutboundMessage->serv_id,
            'project_id' => $mobiletechOutboundMessage->project_id,
            'rcv_msg_id' => $mobiletechOutboundMessage->rcv_msg_id,
            'from' => $mobiletechOutboundMessage->from,
            'to' => $mobiletechOutboundMessage->to,
            'billkey' => $mobiletechOutboundMessage->billkey,
            'content' => $content,
            'content_coding' => self::CONTENT_CODING,
            'esm' => self::ESM,
            'dcs' => self::DCS,
            'expiration' => $mobiletechOutboundMessage->expiration,
            'operator_type' => $mobiletechOutboundMessage->operator_type,
        ];

        $xml = ArrayToXml::convert($message, [
            'rootElementName' => 'message',
            '_attributes' => [
                'command' => 'send'
            ],
        ], true, 'UTF-8');

        $url = $this->applicationConfig->get(Config::GATEWAY_URL_PRODUCTION);
        $internalApiToken = $this->applicationConfig->get(InternalToken::CONFIG_NAME);
        $client = new Client([
            'timeout' => 5,
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF8',
                'Authorization' => 'Bearer ' . $internalApiToken,
            ],
        ]);

        $response = $client->post($url, [
            'body' => $xml,
        ]);
        $responseXml = new \SimpleXMLElement($response->getBody()->getContents());

        $mobiletechOutboundMessage = $this->mobiletechOutboundMessagesRepository->update($mobiletechOutboundMessage, [
                'mobiletech_id' => $responseXml->id,
                'dcs' => self::DCS,
                'esm' => self::ESM,
        ]);

        return $mobiletechOutboundMessage;
    }
}
