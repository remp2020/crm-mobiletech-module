<?php

namespace Crm\MobiletechModule\Gateways;

use Crm\MobiletechModule\Events\MobiletechNotificationEnvelope;
use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\MobiletechModule\Models\DeliveryStatus;
use Crm\MobiletechModule\Models\SubscriptionTypeShortName;
use Crm\MobiletechModule\Repositories\MobiletechInboundMessagesRepository;
use Crm\MobiletechModule\Repositories\MobiletechOutboundMessagesRepository;
use Crm\PaymentsModule\Models\GatewayFail;
use Crm\PaymentsModule\Models\Gateways\PaymentInterface;
use Crm\PaymentsModule\Repositories\PaymentMetaRepository;
use League\Event\Emitter;
use Nette\Utils\Json;

class Mobiletech implements PaymentInterface
{
    public const GATEWAY_CODE = 'mobiletech';

    public const PAYMENT_META_BILLKEY = 'mobiletech_billkey';

    public const PAYMENT_META_INBOUND_MESSAGE_ID = 'mobiletech_inbound_message_id';

    public const PAYMENT_META_TEMPLATE_CODE = 'mobiletech_template_code';

    public const PAYMENT_META_TEMPLATE_PARAMS = 'mobiletech_template_params';

    protected $emitter;

    protected $hermesEmitter;

    protected $paymentMetaRepository;

    protected $mobiletechInboundMessagesRepository;

    protected $mobiletechOutboundMessagesRepository;

    protected $deliveryStatus;

    protected $subscriptionTypeShortName;

    protected $inboundMessage;

    protected $billkey;

    protected $templateCode;

    protected $params;

    protected $payment;

    public function __construct(
        Emitter $emitter,
        \Tomaj\Hermes\Emitter $hermesEmitter,
        PaymentMetaRepository $paymentMetaRepository,
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository,
        DeliveryStatus $deliveryStatus,
        SubscriptionTypeShortName $subscriptionTypeShortName,
    ) {
        $this->emitter = $emitter;
        $this->hermesEmitter = $hermesEmitter;
        $this->paymentMetaRepository = $paymentMetaRepository;
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
        $this->deliveryStatus = $deliveryStatus;
        $this->subscriptionTypeShortName = $subscriptionTypeShortName;
    }

    public function begin($payment)
    {
        $metakeys = [
            self::PAYMENT_META_BILLKEY,
            self::PAYMENT_META_INBOUND_MESSAGE_ID,
            self::PAYMENT_META_TEMPLATE_CODE,
            self::PAYMENT_META_TEMPLATE_PARAMS,
        ];
        $meta = $this->paymentMetaRepository->values($payment, ...$metakeys)->fetchPairs('key', 'value');

        if (!($meta[self::PAYMENT_META_BILLKEY] ?? false)) {
            throw new \Exception('Unable to begin Mobiletech payment, billkey missing: ' . self::PAYMENT_META_BILLKEY);
        }
        if (!($meta[self::PAYMENT_META_INBOUND_MESSAGE_ID] ?? false)) {
            throw new \Exception('Unable to begin Mobiletech payment, inbound message reference missing: ' . self::PAYMENT_META_INBOUND_MESSAGE_ID);
        }
        if (!($meta[self::PAYMENT_META_TEMPLATE_CODE] ?? false)) {
            throw new \Exception('Unable to begin Mobiletech payment, template reference missing: ' . self::PAYMENT_META_TEMPLATE_CODE);
        }

        if ($meta[self::PAYMENT_META_TEMPLATE_PARAMS] ?? false) {
            $this->params = Json::decode($meta[self::PAYMENT_META_TEMPLATE_PARAMS], Json::FORCE_ARRAY);
        } else {
            $this->params = [];
        }

        $this->billkey = $meta[self::PAYMENT_META_BILLKEY];
        $this->inboundMessage = $this->mobiletechInboundMessagesRepository->find($meta[self::PAYMENT_META_INBOUND_MESSAGE_ID]);
        $this->templateCode = $meta[self::PAYMENT_META_TEMPLATE_CODE];
        $this->payment = $payment;
    }

    public function process($allowRedirect = true)
    {
        $event = new MobiletechNotificationEvent(
            $this->emitter,
            new MobiletechNotificationEnvelope($this->inboundMessage, $this->billkey),
            $this->payment->user,
            $this->templateCode,
            $this->params,
            $this->getContext($this->payment),
        );
        $event = $this->emitter->emit($event);

        $outboundMessage = $event->getMobiletechOutboundMessage();
        if (!$outboundMessage) {
            throw new GatewayFail('Mobiletech message to charge user was not sent');
        }

        $this->mobiletechOutboundMessagesRepository->update($outboundMessage, [
            'payment_id' => $this->payment->id,
        ]);
    }

    public function isSuccessful(): bool
    {
        return true;
    }

    public function getResponseData()
    {
        return [];
    }

    public function complete($payment): ?bool
    {
        $this->payment = $payment;

        $outboundMessage = $this->mobiletechOutboundMessagesRepository->findByPayment($payment);
        return $this->checkChargeStatus($outboundMessage);
    }

    public function checkChargeStatus($outboundMessage)
    {
        $deliveryStatus = $this->deliveryStatus->getStatusCode($outboundMessage->status);

        return in_array($deliveryStatus, [
            DeliveryStatus::BILLED,
            DeliveryStatus::DELIVERED,
        ], true);
    }

    protected function getContext($payment)
    {
        return 'payment.charge.' . $payment->id;
    }
}
