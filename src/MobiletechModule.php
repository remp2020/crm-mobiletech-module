<?php

namespace Crm\MobiletechModule;

use Crm\ApplicationModule\Authenticator\AuthenticatorManagerInterface;
use Crm\ApplicationModule\CrmModule;

class MobiletechModule extends CrmModule
{
    public function registerAuthenticators(AuthenticatorManagerInterface $authenticatorManager)
    {
        $authenticatorManager->registerAuthenticator(
            $this->getInstance(\Crm\MobiletechModule\Authenticator\MobiletechAuthenticator::class),
            200
        );
    }
}
