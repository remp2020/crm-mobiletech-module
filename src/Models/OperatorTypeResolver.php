<?php

namespace Crm\MobiletechModule\Models;

use Crm\MobiletechModule\Authenticator\MobiletechAuthenticator;
use Crm\MobiletechModule\Repositories\MobiletechInboundMessagesRepository;
use Crm\MobiletechModule\Repositories\MobiletechOutboundMessagesRepository;
use Nette\Utils\Strings;

class OperatorTypeResolver
{
    public const OPERATOR_O2 = '2';
    public const OPERATOR_ORANGE = 'O';
    public const OPERATOR_TELEKOM = 'T';
    public const OPERATOR_STVORKA = 'S';

    private $mobiletechInboundMessagesRepository;

    private $mobiletechOutboundMessagesRepository;

    public function __construct(
        MobiletechInboundMessagesRepository $mobiletechInboundMessagesRepository,
        MobiletechOutboundMessagesRepository $mobiletechOutboundMessagesRepository
    ) {
        $this->mobiletechInboundMessagesRepository = $mobiletechInboundMessagesRepository;
        $this->mobiletechOutboundMessagesRepository = $mobiletechOutboundMessagesRepository;
    }

    public function resolve(string $phoneNumber): ?string
    {
        // if possible, return known operator type based on previously sent message
        $lastSuccessfulInbound = $this->mobiletechInboundMessagesRepository->findLastSuccessfulByPhoneNumber($phoneNumber);
        $lastSuccessfulOutbound = $this->mobiletechOutboundMessagesRepository->findLastSuccessfulByPhoneNumber($phoneNumber);

        if ($lastSuccessfulInbound && $lastSuccessfulOutbound) {
            if ($lastSuccessfulInbound->created_at > $lastSuccessfulOutbound->created_at) {
                return $lastSuccessfulInbound->operator_type;
            }
            return $lastSuccessfulOutbound->operator_type;
        }
        if ($lastSuccessfulInbound) {
            return $lastSuccessfulInbound->operator_type;
        }
        if ($lastSuccessfulOutbound) {
            return $lastSuccessfulOutbound->operator_type;
        }

        // if there's no history of successful sent messages, determine operator type based on the number prefix
        $matches = [];
        preg_match('/^\+\d{3}(?<prefix>\d{3})\d{6}/', $phoneNumber, $matches);

        switch ($matches['prefix']) {
            case '901':
            case '902':
            case '903':
            case '904':
            case '909':
            case '910':
            case '911':
            case '912':
            case '914':
                return self::OPERATOR_TELEKOM;
            case '905':
            case '906':
            case '907':
            case '908':
            case '915':
            case '916':
            case '917':
            case '918':
            case '919':
            case '945':
                return self::OPERATOR_ORANGE;
            case '940':
            case '944':
            case '947':
            case '948':
            case '949':
                return self::OPERATOR_O2;
            case '950':
            case '951':
                return self::OPERATOR_STVORKA;
            default:
                return null;
        }
    }

    /**
     * @param string $number
     *
     * @return string
     * @throws NotSlovakPhoneNumberException
     */
    public static function convertInternationalSlovakPhoneNumberToLocal(string $number): string
    {
        // it's directly slovak number in usable format
        if (MobiletechAuthenticator::sanitizeSlovakMobilePhoneNumber($number) !== null) {
            return $number;
        }

        if (Strings::startsWith($number, '00421')) {
            return '0' . substr($number, 5);
        }
        if (Strings::startsWith($number, '+421')) {
            return '0' . substr($number, 4);
        }

        throw new NotSlovakPhoneNumberException("Number {$number} is not a slovak number");
    }
}
