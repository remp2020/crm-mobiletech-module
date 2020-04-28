<?php

namespace Crm\MobiletechModule;

use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Authorization\BearerTokenAuthorization;
use Crm\ApiModule\Authorization\NoAuthorization;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface;
use Crm\ApplicationModule\CrmModule;
use Crm\ApplicationModule\SeederManager;
use Crm\MobiletechModule\Api\MobiletechServerProxyApiHandler;
use Crm\MobiletechModule\Api\MobiletechWebhookApiHandler;
use Crm\MobiletechModule\Seeders\ConfigsSeeder;

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
    }
}
