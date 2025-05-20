<?php

namespace Crm\MobiletechModule\Hermes;

use Crm\MobiletechModule\Repositories\MobiletechOutboundMessagesRepository;
use Crm\PaymentsModule\Models\RecurrentPayment\RecurrentPaymentStateEnum;
use Crm\PaymentsModule\Models\RecurrentPaymentsProcessor;
use Crm\PaymentsModule\Repositories\PaymentsRepository;
use Crm\PaymentsModule\Repositories\RecurrentPaymentsRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class PendingChargeTimeoutHandler implements HandlerInterface
{
    private $mobiletechOutboundMessagesRepository;

    private $paymentsRepository;

    private $recurrentPaymentsRepository;

    private $recurrentPaymentsProcessor;

    public function __construct(
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository,
        PaymentsRepository $paymentsRepository,
        RecurrentPaymentsRepository $recurrentPaymentsRepository,
        RecurrentPaymentsProcessor $recurrentPaymentsProcessor,
    ) {
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
        $this->paymentsRepository = $paymentsRepository;
        $this->recurrentPaymentsRepository = $recurrentPaymentsRepository;
        $this->recurrentPaymentsProcessor = $recurrentPaymentsProcessor;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!$payload['payment_id']) {
            Debugger::log('Unable to handle event, missing payment_id in payload', ILogger::ERROR);
            return false;
        }

        $payment = $this->paymentsRepository->find($payload['payment_id']);
        if (!$payment) {
            Debugger::log("Unable to handle event, referenced payment_id doesn't exist: " . $payload['payment_id'], ILogger::ERROR);
            return false;
        }

        $outboundMessage = $this->mobiletechOutboundMessagesRepository->findByPayment($payment);
        if (!$outboundMessage) {
            Debugger::log("Unable to handle event, referenced payment is not linked to outbound message: " . $payment->id, ILogger::ERROR);
            return false;
        }

        $recurrentPayment = $this->recurrentPaymentsRepository->findByPayment($payment);
        if ($recurrentPayment->status !== RecurrentPaymentStateEnum::Pending->value) {
            // pending state has already been resolved
            return true;
        }

        $this->recurrentPaymentsProcessor->processFailedRecurrent(
            $recurrentPayment,
            $outboundMessage->status,
            $outboundMessage->status,
        );

        return true;
    }
}
