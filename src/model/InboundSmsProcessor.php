<?php

namespace Crm\MobiletechModule\Model;

use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\UsersModule\Auth\UserManager;
use League\Event\Emitter;
use Nette\Database\Table\ActiveRow;

class InboundSmsProcessor
{
    private $emitter;

    private $userManager;

    public function __construct(Emitter $emitter, UserManager $userManager)
    {
        $this->emitter = $emitter;
        $this->userManager = $userManager;
    }

    public function process(ActiveRow $inboundMessage)
    {
        $smsContent = mb_strtolower($inboundMessage->content);

        switch ($smsContent) {
            case 'heslo':
                if ($inboundMessage->user) {
                    $this->resetPassword($inboundMessage);
                } else {
                    $this->unauthenticated($inboundMessage);
                }
                break;
        }
    }

    private function unauthenticated(ActiveRow $inboundMessage)
    {
        $this->emitter->emit(new MobiletechNotificationEvent(
            $this->emitter,
            $inboundMessage,
            null,
            $inboundMessage->user,
            'mobiletech_unregistered_number',
            []
        ));
    }

    private function resetPassword(ActiveRow $inboundMessage)
    {
        $password = $this->userManager->resetPassword($inboundMessage->user, null, false);

        $this->emitter->emit(new MobiletechNotificationEvent(
            $this->emitter,
            $inboundMessage,
            null,
            $inboundMessage->user,
            'mobiletech_reset_password_for_authenticated_user',
            [
                'password' => $password,
                'phone_number' => $inboundMessage->from,
            ]
        ));
    }
}
