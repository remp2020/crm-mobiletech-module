<?php

namespace Crm\MobiletechModule\Api;

use Crm\ApiModule\Models\Api\ApiHandler;
use Crm\ApiModule\Models\Response\ApiResponseInterface;
use Crm\ApiModule\Models\Response\EmptyResponse;
use Crm\ApiModule\Models\Response\XmlResponse;
use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\MobiletechModule\Repository\MobiletechInboundMessagesRepository;
use Crm\MobiletechModule\Repository\MobiletechOutboundMessagesRepository;
use Crm\MobiletechModule\Repository\MobiletechPhoneNumbersRepository;
use Nette\Http\Response;
use Nette\Utils\DateTime;
use Tomaj\Hermes\Emitter;
use Tomaj\NetteApi\Response\ResponseInterface;
use Tracy\Debugger;
use Tracy\ILogger;

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

    private $mobiletechOutboundMessagesRepository;

    private $mobiletechPhoneNumbersRepository;

    private $emitter;

    public function __construct(
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository,
        MobiletechPhoneNumbersRepository $mobiletechPhoneNumbersRepository,
        Emitter $emitter
    ) {
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
        $this->mobiletechPhoneNumbersRepository = $mobiletechPhoneNumbersRepository;
        $this->emitter = $emitter;
    }

    public function params(): array
    {
        return [];
    }

    public function handle(array $params): ResponseInterface
    {
        $rawPayload = $this->rawPayload();
        $payload = new \SimpleXMLElement($rawPayload);

        $command = $payload['command'][0] ?? null;
        switch ($command) {
            case "receive":
                return $this->handleReceive($payload);
            case "status":
                return $this->handleStatus($payload);
            default:
                throw new \Exception('unhandled command: ' . $command);
        }
    }

    private function handleReceive($payload): ApiResponseInterface
    {
        $inboundMessage = $this->mobiletechInboundMessagesRepository->findByMobiletechId($payload->id);
        if (!$inboundMessage) {
            $receiveDate = DateTime::createFromFormat('ymdHisv', $payload->receive_date . '00');
            $phoneNumber = $this->mobiletechPhoneNumbersRepository->findByMobilePhoneNumber($payload->from);

            $inboundMessage = $this->mobiletechInboundMessagesRepository->add(
                $phoneNumber->user ?? null,
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
            'mobiletech_inbound_message_id' => $inboundMessage->id,
        ]), HermesMessage::PRIORITY_DEFAULT);

        $result = [
            'id' => $inboundMessage->mobiletech_id,
        ];

        $response = new XmlResponse($result, 'message', [
            'command' => 'rcv_rsp',
        ]);
        $response->setCode(Response::S200_OK);
        return $response;
    }

    /**
     * handleStatus handles "status" command. It's asynchronously triggered after we send an sms and
     * contains delivery information about sent message.
     */
    private function handleStatus($payload): ApiResponseInterface
    {
        $outbound = $this->mobiletechOutboundMessagesRepository->findByMobiletechId($payload->id);
        if (!$outbound) {
            Debugger::log("Mobiletech status command referencing outbound message that doesn't exist: ". $payload->id, ILogger::WARNING);
            $response = new EmptyResponse();
            $response->setCode(Response::S404_NOT_FOUND);
            return $response;
        }

        $this->mobiletechOutboundMessagesRepository->updateStatus($outbound, $payload->status);

        $result = [
            'id' => $outbound->mobiletech_id,
        ];
        $response = new XmlResponse($result, 'message', [
            'command' => 'status_rsp',
        ]);
        $response->setCode(Response::S200_OK);
        return $response;
    }
}
