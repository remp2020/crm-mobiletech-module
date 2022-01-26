<?php

namespace Crm\MobiletechModule\Models;

use Crm\MobiletechModule\DataProvider\SubscriptionTypeFormProvider;
use Crm\SubscriptionsModule\Repository\SubscriptionTypesMetaRepository;
use Nette\Database\Table\IRow;
use Nette\Utils\Strings;

class SubscriptionTypeShortName
{
    private $subscriptionTypesMetaRepository;

    public function __construct(SubscriptionTypesMetaRepository $subscriptionTypesMetaRepository)
    {
        $this->subscriptionTypesMetaRepository = $subscriptionTypesMetaRepository;
    }

    public function getShortName(IRow $subscriptionType): string
    {
        $shortName = $this->subscriptionTypesMetaRepository->getMetaValue($subscriptionType, SubscriptionTypeFormProvider::SUBSCRIPTION_TYPE_SHORT_NAME);
        if ($shortName) {
            return Strings::toAscii($shortName);
        }
        return Strings::toAscii($subscriptionType->user_label);
    }
}
