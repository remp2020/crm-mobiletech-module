<?php

namespace Crm\MobiletechModule\DataProvider;

use Crm\ApplicationModule\DataProvider\DataProviderException;
use Crm\ApplicationModule\Selection;
use Crm\SubscriptionsModule\DataProvider\SubscriptionTypeFormProviderInterface;
use Crm\SubscriptionsModule\Repository\SubscriptionTypesMetaRepository;
use Crm\SubscriptionsModule\Repository\SubscriptionTypesRepository;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Strings;

class SubscriptionTypeFormProvider implements SubscriptionTypeFormProviderInterface
{
    const SUBSCRIPTION_TYPE_SHORT_NAME = 'short_name';

    private $subscriptionTypesMetaRepository;

    private $subscriptionTypesRepository;

    public function __construct(
        SubscriptionTypesRepository $subscriptionTypesRepository,
        SubscriptionTypesMetaRepository $subscriptionTypesMetaRepository
    ) {
        $this->subscriptionTypesMetaRepository = $subscriptionTypesMetaRepository;
        $this->subscriptionTypesRepository = $subscriptionTypesRepository;
    }

    /**
     * @param array $params
     * @return Selection
     * @throws DataProviderException
     */
    public function provide(array $params): Form
    {
        if (!isset($params['form'])) {
            throw new DataProviderException('missing [form] within data provider params');
        }
        if (!($params['form'] instanceof Form)) {
            throw new DataProviderException('invalid type of provided form: ' . get_class($params['form']));
        }

        $form = $params['form'];
        $container = $form->addContainer('mobiletech');
        $container->addText('short_name', 'mobiletech.dataprovider.short_name.label')
            ->setOption('description', 'mobiletech.dataprovider.short_name.description')
            ->setHtmlAttribute('placeholder', 'mobiletech.dataprovider.short_name.placeholder')
            ->setHtmlAttribute('maxlength', 40)
            ->setRequired(false)
            ->addRule(function (TextInput $control) {
                $value = $control->getValue();
                return $value === Strings::toAscii($value);
            }, 'mobiletech.dataprovider.short_name.error_diacritics');

        if (isset($params['subscriptionType'])) {
            $shortName = $this->subscriptionTypesMetaRepository->getMetaValue($params['subscriptionType'], self::SUBSCRIPTION_TYPE_SHORT_NAME);
            if ($shortName) {
                $container->setDefaults([
                    'short_name' => $shortName,
                ]);
            }
        }
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        if (!isset($values->subscription_type_id)) {
            return [$form, $values];
        }

        $subscriptionType = $this->subscriptionTypesRepository->find($values->subscription_type_id);

        if ($subscriptionType) {
            if ($values->mobiletech->short_name) {
                $this->subscriptionTypesMetaRepository->setMeta($subscriptionType, self::SUBSCRIPTION_TYPE_SHORT_NAME, Strings::toAscii($values->mobiletech->short_name));
            } else {
                $this->subscriptionTypesMetaRepository->removeMeta($subscriptionType->id, self::SUBSCRIPTION_TYPE_SHORT_NAME);
            }
        }

        unset($values->mobiletech);
        return [$form, $values];
    }
}
