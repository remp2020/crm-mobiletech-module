<?php

namespace Crm\MobiletechModule\Model;

use Crm\ApiModule\Token\InternalToken;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\MobiletechModule\Config;
use Crm\MobiletechModule\Repository\MobiletechOutboundMessagesRepository;
use GuzzleHttp\Client;
use Nette\Database\Table\IRow;
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
        IRow $user,
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
        $message = [
            'serv_id' => $servId,
            'project_id' => $projectId,
            'rcv_msg_id' => $rcvMsgId,
            'from' => $from,
            'to' => $to, // intentional swap
            'billkey' => $billKey,
            'content' => $content,
            'content_coding' => self::CONTENT_CODING,
            'esm' => self::ESM,
            'dcs' => self::DCS,
            'expiration' => null, // TODO: populate expiration (from global config?)
            'operator_type' => $operatorType,
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

        $outboundMessage = $this->mobiletechOutboundMessagesRepository->add(
            $user,
            $responseXml->id,
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

        return $outboundMessage;
    }
}
