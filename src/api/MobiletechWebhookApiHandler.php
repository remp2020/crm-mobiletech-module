<?php

namespace Crm\MobiletechModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\XmlResponse;
use Crm\ApiModule\Authorization\ApiAuthorizationInterface;
use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\MobiletechModule\Repository\MobiletechInboundMessagesRepository;
use Nette\Http\Response;
use Nette\Utils\DateTime;
use Tomaj\Hermes\Emitter;

/**
 * Mobiletech incoming message handler.
 *
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <message command="receive">
 *     <id>25481979</id>
 *     <serv_id>1703</serv_id>
 *     <project_id>76</project_id>
 *     <from>+421908630549</from>
 *     <to>8877</to>
 *     <content>M9PFLF</content>
 *     <content_coding>text</content_coding>
 *     <dcs>0</dcs>
 *     <esm>0</esm>
 *     <operator_type>O</operator_type>
 *     <receive_date>1108191645251</receive_date>
 * </message>
 *
 * Mobiletech expects synchronous response confirming acceptation of message.
 *
 * <?xml version="1.0" encoding="UTF-8"?>
 * <message command="rcv_rsp">
 *     <id>25481979</id>
 * </message>
 */
class MobiletechWebhookApiHandler extends ApiHandler
{
    private $mobiletechInboundMessagesRepository;

    private $emitter;

    public function __construct(
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        Emitter $emitter
    ) {
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->emitter = $emitter;
    }

    public function params()
    {
        return [];
    }

    public function handle(ApiAuthorizationInterface $authorization)
    {
        $rawPayload = file_get_contents('php://input');
        $payload = new \SimpleXMLElement($rawPayload);

        $inboundMessage = $this->mobiletechInboundMessagesRepository->findByMobiletechId($payload->id);
        if (!$inboundMessage) {
            $receiveDate = DateTime::createFromFormat('ymdHisv', $payload->receive_date . '00');
            $inboundMessage = $this->mobiletechInboundMessagesRepository->add(
                $payload->id,
                $payload->serv_id,
                $payload->project_id,
                $payload->from,
                $payload->to,
                $payload->content,
                $payload->content_encoding,
                $payload->dcs,
                $payload->esm,
                $payload->operator_type,
                $receiveDate
            );
        }

        $this->emitter->emit(new HermesMessage('mobiletech-inbound', [
            'inbound_message_id' => $inboundMessage->id,
        ]));

        $result = [
            'id' => $inboundMessage->mobiletech_id,
        ];

        $response = new XmlResponse($result, 'message', [
            'command' => 'rcv_rsp',
        ]);
        $response->setHttpCode(Response::S200_OK);
        return $response;
    }
}
