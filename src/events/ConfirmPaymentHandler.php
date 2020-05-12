<?php

namespace Crm\MobiletechModule\Events;

use Crm\PaymentsModule\PaymentProcessor;
use League\Event\AbstractListener;
use League\Event\EventInterface;

class ConfirmPaymentHandler extends AbstractListener
{
    private $paymentProcessor;

    public function __construct(PaymentProcessor $paymentProcessor)
    {
        $this->paymentProcessor = $paymentProcessor;
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

        $this->paymentProcessor->complete($outboundMessage->payment, function () {
            // no need to do anything...
        });
    }
}
