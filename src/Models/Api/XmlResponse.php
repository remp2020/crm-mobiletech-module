<?php

namespace Crm\MobiletechModule\Models\Api;

use Crm\ApiModule\Models\Response\ApiResponseInterface;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Spatie\ArrayToXml\ArrayToXml;

class XmlResponse implements ApiResponseInterface
{
    protected int $code;

    protected $rootElement;

    protected $rootElementAttributes;

    protected $payload;

    public function __construct(array $payload, string $rootElement, array $rootElementAttributes = [])
    {
        $this->payload = $payload;
        $this->rootElement = $rootElement;
        $this->rootElementAttributes = $rootElementAttributes;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code)
    {
        $this->code = $code;
    }

    public function send(IRequest $httpRequest, IResponse $httpResponse): void
    {
        $httpResponse->setContentType('application/xml', 'utf-8');
        echo ArrayToXml::convert($this->payload, [
            'rootElementName' => $this->rootElement,
            '_attributes' => $this->rootElementAttributes,
        ], true, 'UTF-8');
    }
}
