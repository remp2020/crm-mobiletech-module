<?php

namespace Crm\MobiletechModule\Hermes;

use Crm\MobiletechModule\Repository\MobiletechInboundMessagesRepository;
use Crm\UsersModule\Auth\UserManager;
use Crm\UsersModule\Repository\UsersRepository;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class UserConfirmationInboundHandler implements HandlerInterface
{
    private $mobiletechInboundMessagesRepository;
    
    private $userManager;
    
    private $usersRepository;
    
    public function __construct(
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        UserManager $userManager,
        UsersRepository $usersRepository
    ) {
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->userManager = $userManager;
        $this->usersRepository = $usersRepository;
    }
    
    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['mobiletech_inbound_message_id'])) {
            return false;
        }
        
        $inboundMessage = $this->mobiletechInboundMessagesRepository->find($payload['mobiletech_inbound_message_id']);
        $userId = $inboundMessage->user_id;
        
        if ($userId) {
            $user = $this->usersRepository->find($userId);
            $this->userManager->confirmUser($user);
        }
        
        return true;
    }
}
