<?php

namespace Crm\MobiletechModule;

use Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface;
use Crm\ApiModule\Api\ApiRoutersContainerInterface;
use Crm\ApiModule\Authorization\NoAuthorization;
use Crm\ApiModule\Router\ApiIdentifier;
use Crm\ApiModule\Router\ApiRoute;
use Crm\ApplicationModule\CrmModule;
use Crm\MobiletechModule\Api\MobiletechWebhookApiHandler;

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
    }
}
