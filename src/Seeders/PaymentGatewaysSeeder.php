<?php

namespace Crm\MobiletechModule\Seeders;

use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\MobiletechModule\Gateways\Mobiletech;
use Crm\MobiletechModule\Gateways\MobiletechRecurrent;
use Crm\PaymentsModule\Repositories\PaymentGatewaysRepository;
use Symfony\Component\Console\Output\OutputInterface;

class PaymentGatewaysSeeder implements ISeeder
{
    private $paymentGatewaysRepository;

    public function __construct(PaymentGatewaysRepository $paymentGatewaysRepository)
    {
        $this->paymentGatewaysRepository = $paymentGatewaysRepository;
    }

    public function seed(OutputInterface $output)
    {
        $code = Mobiletech::GATEWAY_CODE;
        if (!$this->paymentGatewaysRepository->exists($code)) {
            $this->paymentGatewaysRepository->add(
                'Mobiletech',
                $code,
                1200,
                true,
                false
            );
            $output->writeln("  <comment>* payment gateway <info>{$code}</info> created</comment>");
        } else {
            $output->writeln("  * payment gateway <info>{$code}</info> exists");
        }

        $code = MobiletechRecurrent::GATEWAY_CODE;
        if (!$this->paymentGatewaysRepository->exists($code)) {
            $this->paymentGatewaysRepository->add(
                'Mobiletech Recurrent',
                $code,
                1201,
                true,
                true
            );
            $output->writeln("  <comment>* payment gateway <info>{$code}</info> created</comment>");
        } else {
            $output->writeln("  * payment gateway <info>{$code}</info> exists");
        }
    }
}
