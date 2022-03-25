<?php

namespace Crm\MobiletechModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Token\InternalToken;
use Crm\ApplicationModule\Config\ApplicationConfig;
use Crm\MobiletechModule\Models\Config;
use GuzzleHttp\Client;
use Tomaj\NetteApi\Response\ResponseInterface;

/**
 * Mobiletech only allows whitelisted set of IPs to make a request to SMS gateway. To circumvent this limitation
 * during development, send your request to this API on production environment. It forwards the request to the
 * testing gateway and returns back the response.
 */
class MobiletechServerProxyApiHandler extends ApiHandler
{
    private $applicationConfig;

    public function __construct(ApplicationConfig $applicationConfig)
    {
        $this->applicationConfig = $applicationConfig;
    }

    public function params(): array
    {
        return [];
    }

    public function handle(array $params): ResponseInterface
    {
        $rawXmlPayload = file_get_contents('php://input');

        $url = $this->applicationConfig->get(Config::GATEWAY_URL_TEST);
        if (!$url) {
            throw new \Exception('cannot use API endpoint, missing configuration option: ' . Config::GATEWAY_URL_TEST);
        }
        $internalApiToken = $this->applicationConfig->get(InternalToken::CONFIG_NAME);
        if (!$internalApiToken) {
            throw new \Exception('cannot use API endpoint, missing configuration option: ' . InternalToken::CONFIG_NAME);
        }

        $client = new Client([
            'timeout' => 5,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'text/xml; charset=UTF8',
                'Authorization' => 'Bearer ' . $internalApiToken,
            ],
        ]);

        $result = $client->request('POST', $url, [
            'body' => $rawXmlPayload,
        ]);

        echo $result->getBody()->getContents();
        exit;
    }
}
