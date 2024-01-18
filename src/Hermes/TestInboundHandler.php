<?php

namespace Crm\MobiletechModule\Hermes;

use Crm\MobiletechModule\Events\MobiletechNotificationEnvelope;
use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\MobiletechModule\Repositories\MobiletechInboundMessagesRepository;
use Crm\MobiletechModule\Repositories\MobiletechTemplatesRepository;
use Crm\PaymentsModule\Models\PaymentProcessor;
use Crm\PaymentsModule\Repositories\PaymentsRepository;
use League\Event\Emitter;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

/**
 * TestInboundHandler serves only for the purpose of testing response to live message receiving.
 *
 * Before using registering this handler in the module, make sure you configure the behavior in your application
 * configuration. Otherwise the handler will throw an exception.
 */
class TestInboundHandler implements HandlerInterface
{
    private $mobiletechTemplatesRepository;

    private $mobiletechInboundMessagesRepository;

    private $emitter;

    private $billKey;

    private $templateCode;

    private $paymentsRepository;

    private $paymentProcessor;

    public function __construct(
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        MobiletechTemplatesRepository $mobiletechTemplatesRepository,
        Emitter $emitter,
        PaymentsRepository $paymentsRepository,
        PaymentProcessor $paymentProcessor
    ) {
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->mobiletechTemplatesRepository = $mobiletechTemplatesRepository;
        $this->emitter = $emitter;
        $this->paymentsRepository = $paymentsRepository;
        $this->paymentProcessor = $paymentProcessor;
    }

    public function setBillKey(string $billKey)
    {
        $this->billKey = $billKey;
    }

    public function setTemplateCode(string $templateCode)
    {
        $this->templateCode = $templateCode;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mobiletech_inbound_message_id'])) {
            return false;
        }
        $inboundMessage = $this->mobiletechInboundMessagesRepository->find($payload['mobiletech_inbound_message_id']);

        if (!$this->billKey) {
            throw new \Exception('unable to use TestInboundHandler, billKey was not configured.');
        }
        if (!$this->templateCode) {
            throw new \Exception('unable to use TestInboundHandler, templateCode was not configured.');
        }

        $template = $this->mobiletechTemplatesRepository->findByCode($this->templateCode);
        if (!$template) {
            throw new \Exception(("unable to use TestInboundHandler, configured templateCode doesn't exist: " . $this->templateCode));
        }

        $this->emitter->emit(new MobiletechNotificationEvent(
            $this->emitter,
            new MobiletechNotificationEnvelope($inboundMessage, $this->billKey),
            $inboundMessage->user,
            $template->code,
            [
                'original_message' => $inboundMessage->content,
            ]
        ));

        return true;
    }
}
