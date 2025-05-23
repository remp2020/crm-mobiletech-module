<?php

namespace Crm\MobiletechModule\Events;

use Crm\MobiletechModule\Gateways\MobiletechRecurrent;
use Crm\PaymentsModule\Models\Payment\PaymentStatusEnum;
use Crm\PaymentsModule\Models\PaymentProcessor;
use Crm\PaymentsModule\Models\RecurrentPaymentsProcessor;
use Crm\PaymentsModule\Repositories\RecurrentPaymentsRepository;
use League\Event\AbstractListener;
use League\Event\EventInterface;

class ConfirmPaymentHandler extends AbstractListener
{
    private $paymentProcessor;

    private $recurrentPaymentsProcessor;

    private $recurrentPaymentsRepository;

    private $mobiletechRecurrent;

    public function __construct(
        PaymentProcessor $paymentProcessor,
        RecurrentPaymentsProcessor $recurrentPaymentsProcessor,
        RecurrentPaymentsRepository $recurrentPaymentsRepository,
        MobiletechRecurrent $mobiletechRecurrent,
    ) {
        $this->paymentProcessor = $paymentProcessor;
        $this->recurrentPaymentsProcessor = $recurrentPaymentsProcessor;
        $this->recurrentPaymentsRepository = $recurrentPaymentsRepository;
        $this->mobiletechRecurrent = $mobiletechRecurrent;
    }

    public function handle(EventInterface $event)
    {
        if (!($event instanceof OutboundMessageStatusUpdatedEvent)) {
            throw new \Exception("Unable to handle event, expected OutboundMessageStatusUpdatedEvent: " . get_class($event));
        }

        $outboundMessage = $event->getMobiletechOutboundMessage();
        if (!$outboundMessage->payment) {
            return;
        }

        if ($outboundMessage->payment->recurrent_charge) {
            $recurrentPayment = $this->recurrentPaymentsRepository->findByPayment($outboundMessage->payment);
            $success = $this->mobiletechRecurrent->checkChargeStatus($outboundMessage);

            if ($success) {
                $this->recurrentPaymentsProcessor->processChargedRecurrent(
                    $recurrentPayment,
                    PaymentStatusEnum::Paid->value,
                    $outboundMessage->status,
                    $outboundMessage->status,
                );
            } else {
                $this->recurrentPaymentsProcessor->processFailedRecurrent(
                    $recurrentPayment,
                    $outboundMessage->status,
                    $outboundMessage->status,
                );
            }
        } else {
            $this->paymentProcessor->complete($outboundMessage->payment, function () {
                // no need to do anything...
            });
        }
    }
}
