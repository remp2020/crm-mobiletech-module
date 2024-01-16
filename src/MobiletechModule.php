<?php

namespace Crm\MobiletechModule;

use Crm\ApiModule\Models\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Models\Authorization\BearerTokenAuthorization;
use Crm\ApiModule\Models\Authorization\NoAuthorization;
use Crm\ApiModule\Models\Router\ApiIdentifier;
use Crm\ApiModule\Models\Router\ApiRoute;
use Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface;
use Crm\ApplicationModule\Commands\CommandsContainerInterface;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\DataProvider\DataProviderManager;
use Crm\ApplicationModule\Event\LazyEventEmitter;
use Crm\ApplicationModule\SeederManager;
use Crm\MobiletechModule\Api\MobiletechServerProxyApiHandler;
use Crm\MobiletechModule\Api\MobiletechWebhookApiHandler;
use Crm\MobiletechModule\Authenticator\MobiletechAuthenticator;
use Crm\MobiletechModule\Commands\TestNotificationCommand;
use Crm\MobiletechModule\DataProviders\SubscriptionTypeFormProvider;
use Crm\MobiletechModule\Events\ConfirmPaymentHandler;
use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use Crm\MobiletechModule\Events\NotificationHandler;
use Crm\MobiletechModule\Events\OutboundMessageStatusUpdatedEvent;
use Crm\MobiletechModule\Hermes\PendingChargeTimeoutHandler;
use Crm\MobiletechModule\Hermes\SendHandler;
use Crm\MobiletechModule\Hermes\UserConfirmationInboundHandler;
use Crm\MobiletechModule\Seeders\ConfigsSeeder;
use Crm\MobiletechModule\Seeders\PaymentGatewaysSeeder;
use Crm\UsersModule\Events\NotificationEvent;
use Tomaj\Hermes\Dispatcher;

class MobiletechModule extends CrmModule
{
    public function registerAuthenticators(AuthenticatorManagerInterface $authenticatorManager)
    {
        $authenticatorManager->registerAuthenticator(
            $this->getInstance(MobiletechAuthenticator::class),
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

    public function registerLazyEventHandlers(LazyEventEmitter $emitter)
    {
        $emitter->addListener(
            NotificationEvent::class,
            NotificationHandler::class
        );
        $emitter->addListener(
            MobiletechNotificationEvent::class,
            NotificationHandler::class
        );
        $emitter->addListener(
            OutboundMessageStatusUpdatedEvent::class,
            ConfirmPaymentHandler::class
        );
    }

    public function registerHermesHandlers(Dispatcher $dispatcher)
    {
        $dispatcher->registerHandler(
            'mobiletech-send',
            $this->getInstance(SendHandler::class)
        );
        $dispatcher->registerHandler(
            'mobiletech-pending-charge-timeout',
            $this->getInstance(PendingChargeTimeoutHandler::class)
        );
        $dispatcher->registerHandler(
            'mobiletech-inbound',
            $this->getInstance(UserConfirmationInboundHandler::class)
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
            $this->getInstance(SubscriptionTypeFormProvider::class)
        );
    }
}
