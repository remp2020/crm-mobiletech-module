<?php

namespace Crm\MobiletechModule\Commands;

use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\MobiletechModule\Repository\MobiletechInboundMessagesRepository;
use Crm\MobiletechModule\Repository\MobiletechTemplatesRepository;
use Crm\UsersModule\Repository\UsersRepository;
use League\Event\Emitter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestNotificationCommand extends Command
{
    private $emitter;

    private $usersRepository;

    private $mobiletechTemplatesRepository;

    private $mobiletechInboundMessagesRepository;

    public function __construct(
        Emitter $emitter,
        UsersRepository $usersRepository,
        MobiletechTemplatesRepository $mobiletechTemplatesRepository,
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository
    ) {
        parent::__construct();
        $this->emitter = $emitter;
        $this->usersRepository = $usersRepository;
        $this->mobiletechTemplatesRepository = $mobiletechTemplatesRepository;
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
    }

    protected function configure()
    {
        $this->setName('mobiletech:test_notification')
            ->setDescription("Fires NotificationEvent with data necessary to send testing message")
            ->addOption(
                'user_id',
                null,
                InputOption::VALUE_REQUIRED,
                'User ID with mobiletech phone number'
            )
            ->addOption(
                'bill_key',
                null,
                InputOption::VALUE_REQUIRED,
                'Bill key used to send message'
            )
            ->addOption(
                'template_code',
                null,
                InputOption::VALUE_REQUIRED,
                'Mobiletech template to be sent'
            )
            ->addOption(
                'inbound_message_id',
                null,
                InputOption::VALUE_REQUIRED,
                "ID of inbound message you're trying respond to"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userId = $input->getOption('user_id');
        if (!$userId) {
            $output->writeln("<error>missing option: --user_id=</error>");
            return Command::FAILURE;
        }
        $user = $this->usersRepository->find($userId);
        if (!$user) {
            $output->writeln("<error>user doesn't exist: {$userId}</error>");
            return Command::FAILURE;
        }

        $billKey = $input->getOption('bill_key');
        if (!$billKey) {
            $output->writeln("missing option: <error>--bill_key=</error>");
            return Command::FAILURE;
        }

        $templateCode = $input->getOption('template_code');
        if (!$templateCode) {
            $output->writeln("missing option: <error>--template_code=</error>");
            return Command::FAILURE;
        }
        $template = $this->mobiletechTemplatesRepository->findByCode($templateCode);
        if (!$template) {
            $output->writeln("<error>template doesn't exist: {$templateCode}</error>");
            return Command::FAILURE;
        }

        $inboundMessage = null;
        if ($inboundId = $input->getOption('inbound_message_id')) {
            $inboundMessage = $this->mobiletechInboundMessagesRepository->find($inboundId);
            if (!$inboundMessage) {
                $output->writeln("<error>inbound message with provided id doesn't exist: {$inboundId}</error>");
                return Command::FAILURE;
            }
        }

        $this->emitter->emit(new MobiletechNotificationEvent(
            $this->emitter,
            $inboundMessage,
            $billKey,
            $user,
            $template->code
        ));

        return Command::SUCCESS;
    }
}
