<?php

namespace Crm\MobiletechModule\Seeders;

use Crm\ApplicationModule\Builder\ConfigBuilder;
use Crm\ApplicationModule\Models\Config\ApplicationConfig;
use Crm\ApplicationModule\Repositories\ConfigCategoriesRepository;
use Crm\ApplicationModule\Repositories\ConfigsRepository;
use Crm\ApplicationModule\Seeders\ConfigsTrait;
use Crm\ApplicationModule\Seeders\ISeeder;
use Crm\MobiletechModule\Models\Config;
use Nette\Database\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigsSeeder implements ISeeder
{
    use ConfigsTrait;

    private $configCategoriesRepository;

    private $configsRepository;

    private $configBuilder;

    private $database;

    public function __construct(
        ConfigCategoriesRepository $configCategoriesRepository,
        ConfigsRepository $configsRepository,
        ConfigBuilder $configBuilder,
        Connection $database,
    ) {
        $this->configCategoriesRepository = $configCategoriesRepository;
        $this->configsRepository = $configsRepository;
        $this->configBuilder = $configBuilder;
        $this->database = $database;
    }

    public function seed(OutputInterface $output)
    {
        $categoryName = 'payments.config.category';
        $category = $this->configCategoriesRepository->loadByName($categoryName);
        if (!$category) {
            $category = $this->configCategoriesRepository->add($categoryName, 'fa fa-credit-card', 300);
            $output->writeln('  <comment>* config category <info>Platby</info> created</comment>');
        } else {
            $output->writeln('  * config category <info>Platby</info> exists');
        }

        $this->addConfig(
            $output,
            $category,
            Config::GATEWAY_URL_PRODUCTION,
            ApplicationConfig::TYPE_STRING,
            'mobiletech.config.' . Config::GATEWAY_URL_PRODUCTION . '.name',
            'mobiletech.config.' . Config::GATEWAY_URL_PRODUCTION . '.description',
            '',
            2500,
        );

        $this->addConfig(
            $output,
            $category,
            Config::GATEWAY_URL_TEST,
            ApplicationConfig::TYPE_STRING,
            'mobiletech.config.' . Config::GATEWAY_URL_TEST . '.name',
            'mobiletech.config.' . Config::GATEWAY_URL_TEST . '.description',
            '',
            2501,
        );

        $this->addConfig(
            $output,
            $category,
            Config::BILLKEY_FREE,
            ApplicationConfig::TYPE_STRING,
            'mobiletech.config.' . Config::BILLKEY_FREE . '.name',
            'mobiletech.config.' . Config::BILLKEY_FREE . '.description',
            '',
            2501,
        );
    }
}
