<?php

namespace Crm\MobiletechModule\Tests;

use Crm\MobiletechModule\Models\NotSlovakPhoneNumberException;
use Crm\MobiletechModule\Models\OperatorTypeResolver;
use PHPUnit\Framework\TestCase;

class CheckSlovakNumbersTest extends TestCase
{
    public function testNumbers()
    {
        $n = '0902333444';
        $this->assertEquals($n, OperatorTypeResolver::convertInternationalSlovakPhoneNumberToLocal('+421902333444'));
        $this->assertEquals($n, OperatorTypeResolver::convertInternationalSlovakPhoneNumberToLocal('00421902333444'));
        $this->assertEquals($n, OperatorTypeResolver::convertInternationalSlovakPhoneNumberToLocal('0902333444'));

        $this->expectException(NotSlovakPhoneNumberException::class);
        $this->assertEquals($n, OperatorTypeResolver::convertInternationalSlovakPhoneNumberToLocal('00420902333444'));

        $this->expectException(NotSlovakPhoneNumberException::class);
        $this->assertEquals($n, OperatorTypeResolver::convertInternationalSlovakPhoneNumberToLocal('somegibberish'));
    }
}
