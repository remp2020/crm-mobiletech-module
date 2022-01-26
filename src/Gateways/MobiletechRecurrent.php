<?php

namespace Crm\MobiletechModule\Gateways;

use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\MobiletechModule\Events\MobiletechNotificationEnvelope;
use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\PaymentsModule\GatewayFail;
use Crm\PaymentsModule\Gateways\RecurrentPaymentInterface;
use Nette\Utils\DateTime;
use Omnipay\Common\Exception\InvalidRequestException;

class MobiletechRecurrent extends Mobiletech implements RecurrentPaymentInterface
{
    public const GATEWAY_CODE = 'mobiletech_recurrent';

    protected $outboundMessage;

    public function charge($payment, $token): string
    {
        $originalInboundMessage = $this->mobiletechInboundMessagesRepository->findByMobiletechId($token);
        if (!$originalInboundMessage) {
            throw new MobiletechGatewayException('unable to charge mobiletech recurrent payment, invalid token: ' . $token);
        }
        $originalOutboundMessage = $this->mobiletechOutboundMessagesRepository->getTable()
            ->where('rcv_msg_id = ?', $token)
            ->where('payment_id IS NOT NULL')
            ->fetch();

        if (!$originalOutboundMessage) {
            throw new MobiletechGatewayException('unable to charge mobiletech recurrent payment, missing outbound billing message to initiating inbound message: ' . $token);
        }

        $event = new MobiletechNotificationEvent(
            $this->emitter,
            new MobiletechNotificationEnvelope(
                null,
                $originalOutboundMessage->billkey,
                $originalInboundMessage->serv_id,
                $originalInboundMessage->to,
                $originalInboundMessage->from
            ),
            $payment->user,
            $originalOutboundMessage->mobiletech_template->code,
            [
                'subscription_name' => $this->subscriptionTypeShortName->getShortName($payment->subscription_type),
                'price' => (int) $payment->subscription_type->price,
            ],
            $this->getContext($payment)
        );
        $event = $this->emitter->emit($event);

        $outboundMessage = $event->getMobiletechOutboundMessage();
        if (!$outboundMessage) {
            throw new GatewayFail('Mobiletech message to charge user was not sent');
        }

        $this->mobiletechOutboundMessagesRepository->update($outboundMessage, [
            'payment_id' => $payment->id,
        ]);
        $this->payment = $payment;

        $this->hermesEmitter->emit(new HermesMessage('mobiletech-pending-charge-timeout', [
            'payment_id' => $payment->id,
        ], null, null, DateTime::from('+15 minutes')->getTimestamp()), HermesMessage::PRIORITY_DEFAULT);

        return self::CHARGE_PENDING;
    }

    public function checkValid($token)
    {
        throw new InvalidRequestException("mobiletech recurrent gateway doesn't support checking if token is still valid");
    }

    public function checkExpire($recurrentPayments)
    {
        throw new InvalidRequestException("mobiletech recurrent gateway doesn't support checking if token is still valid");
    }

    public function hasRecurrentToken(): bool
    {
        $outboundMessage = $this->getOutboundMessage();
        if (!$outboundMessage) {
            return false;
        }
        return $outboundMessage->rcv_msg_id !== null;
    }

    public function getRecurrentToken()
    {
        $outboundMessage = $this->getOutboundMessage();
        if (!$outboundMessage) {
            throw new MobiletechGatewayException('Unable to get recurrent token, outbound message reference missing. Did you call hasRecurrentToken() method first?');
        }
        return $outboundMessage->rcv_msg_id;
    }

    private function getOutboundMessage()
    {
        if (!$this->payment) {
            return null;
        }
        if (!$this->outboundMessage) {
            $this->outboundMessage = $this->mobiletechOutboundMessagesRepository->findByPayment($this->payment);
        }
        return $this->outboundMessage;
    }

    public function getResultCode()
    {
        // message is sent asynchronously
        return self::CHARGE_PENDING;
    }

    public function getResultMessage()
    {
        // message is sent asynchronously
        return self::CHARGE_PENDING;
    }
}
