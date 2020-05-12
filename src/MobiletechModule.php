<?php

namespace Crm\MobiletechModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Authorization\BearerTokenAuthorization;
use Crm\ApiModule\Authorization\NoAuthorization;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface;
use Crm\ApplicationModule\Commands\CommandsContainerInterface;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\DataProvider\DataProviderManager;
use Crm\ApplicationModule\SeederManager;
use Crm\MobiletechModule\Api\MobiletechServerProxyApiHandler;
use Crm\MobiletechModule\Api\MobiletechWebhookApiHandler;
use Crm\MobiletechModule\Commands\TestNotificationCommand;
use Crm\MobiletechModule\Seeders\ConfigsSeeder;
use Crm\MobiletechModule\Seeders\PaymentGatewaysSeeder;
use League\Event\Emitter;
use Tomaj\Hermes\Dispatcher;

class MobiletechModule extends CrmModule
{
    public function registerAuthenticators(AuthenticatorManagerInterface $authenticatorManager)
    {
        $authenticatorManager->registerAuthenticator(
            $this->getInstance(\Crm\MobiletechModule\Authenticator\MobiletechAuthenticator::class),
            200
        );
    }

    public function registerApiCalls(ApiRoutersContainerInterface $apiRoutersContainer)
    {
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'mobiletech', 'webhook'), MobiletechWebhookApiHandler::class, NoAuthorization::class)
        );
        $apiRoutersContainer->attachRouter(
            new ApiRoute(new ApiIdentifier('1', 'mobiletech', 'server-proxy'), MobiletechServerProxyApiHandler::class, BearerTokenAuthorization::class)
        );
    }

    public function registerSeeders(SeederManager $seederManager)
    {
        $seederManager->addSeeder($this->getInstance(ConfigsSeeder::class));
        $seederManager->addSeeder($this->getInstance(PaymentGatewaysSeeder::class));
    }

    public function registerEventHandlers(Emitter $emitter)
    {
        $emitter->addListener(
            \Crm\UsersModule\Events\NotificationEvent::class,
            $this->getInstance(\Crm\MobiletechModule\Events\NotificationHandler::class)
        );
        $emitter->addListener(
            \Crm\MobiletechModule\Events\MobiletechNotificationEvent::class,
            $this->getInstance(\Crm\MobiletechModule\Events\NotificationHandler::class)
        );
        $emitter->addListener(
            \Crm\MobiletechModule\Events\OutboundMessageStatusUpdatedEvent::class,
            $this->getInstance(\Crm\MobiletechModule\Events\ConfirmPaymentHandler::class)
        );
    }

    public function registerHermesHandlers(Dispatcher $dispatcher)
    {
        $dispatcher->registerHandler(
            'mobiletech-send',
            $this->getInstance(\Crm\MobiletechModule\Hermes\SendHandler::class)
        );
    }

    public function registerCommands(CommandsContainerInterface $commandsContainer)
    {
        $commandsContainer->registerCommand($this->getInstance(TestNotificationCommand::class));
    }

    public function registerDataProviders(DataProviderManager $dataProviderManager)
    {
        $dataProviderManager->registerDataProvider(
            'subscriptions.dataprovider.subscription_type_form',
            $this->getInstance(\Crm\MobiletechModule\DataProvider\SubscriptionTypeFormProvider::class)
        );
    }
}
