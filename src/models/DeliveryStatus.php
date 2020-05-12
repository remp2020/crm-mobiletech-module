<?php

namespace Crm\MobiletechModule\Models;

class DeliveryStatus
{
    private const FLAG_BILLING = 'A';
    private const FLAG_DELIVERY = 'D';

    private const OPERATOR_O2 = '2';
    private const OPERATOR_ORANGE = 'O';
    private const OPERATOR_TELEKOM = 'T';
    private const OPERATOR_STVORKA = 'S';

    public const BILLED = 'billed';
    public const NOT_DELIVERED = 'not_delivered';
    public const DELIVERED = 'delivered';
    public const ERROR = 'error';
    public const OPERATOR_CHANGED = 'operator_changed';

    /**
     * getGeneralStatus parses the delivery code provided by MobileTech (e.g. D1, A0, A5, ...) and returns constant
     * indicating delivery or billing status of sent message.
     */
    public function getStatusCode(string $mobiletechStatus, ?string $operator = null): string
    {
        if ($mobiletechStatus === '_OPERATOR_CHANGE') {
            return self::OPERATOR_CHANGED;
        }

        $matches = [];
        preg_match('/^(?<type>[A-Z])(?<code>\d{1,5})$/', $mobiletechStatus, $matches);

        if (empty($matches)) {
            return self::ERROR;
        }

        $statusType = $matches['type'];
        $code = (int) $matches['code'];

        switch ($statusType) {
            case self::FLAG_BILLING:
                return $this->getBillingStatus($code);
            case self::FLAG_DELIVERY:
                return $this->getDeliveryStatus($code, $operator);
            default:
                return self::ERROR;
        }
    }

    private function getBillingStatus($code): int
    {
        if (0 === $code) {
            return self::BILLED;
        }
        if (0 < $code && $code < 1024) {
            return self::ERROR;
        }
        if (1024 <= $code) {
            return self::NOT_DELIVERED;
        }

        return self::ERROR;
    }

    private function getDeliveryStatus(int $code, ?string $operator = null): string
    {
        if ($code === 0 && $operator === self::OPERATOR_O2) {
            return self::DELIVERED;
        }
        if ($code === 1) {
            return self::DELIVERED;
        }
        if ($code === 0) {
            return self::NOT_DELIVERED;
        }

        return self::ERROR;
    }
}
