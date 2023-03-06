<?php

namespace Crm\MobiletechModule\Tests;

use Crm\MobiletechModule\Events\MobiletechNotificationEvent;
use League\Event\AbstractListener;
use League\Event\EventInterface;

class TestMobiletechNotificationHandler extends AbstractListener
{
    /** @var MobiletechNotificationEvent[<string>][]  */
    private $notifications = [];

    public function handle(EventInterface $event)
    {
        if (!($event instanceof MobiletechNotificationEvent)) {
            throw new \Exception('Unable to handle event, expected MobiletechNotificationEvent');
        }

        $number = $event->getMobiletechInboundMessage()->from;

        if (!array_key_exists($number, $this->notifications)) {
            $this->notifications[$number] = [];
        }

        $this->notifications[$number][] = $event;
    }

    /**
     * @param string $number
     *
     * @return MobiletechNotificationEvent[]
     */
    public function notificationsSentTo(string $number): array
    {
        return $this->notifications[$number] ?? [];
    }
}
