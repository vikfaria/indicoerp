<?php

namespace Tests\Unit;

use App\Support\MozambiqueTaxNumber;
use PHPUnit\Framework\TestCase;

class MozambiqueTaxNumberTest extends TestCase
{
    public function test_is_mozambique_country_accepts_country_code_and_names(): void
    {
        $this->assertTrue(MozambiqueTaxNumber::isMozambiqueCountry('MZ'));
        $this->assertTrue(MozambiqueTaxNumber::isMozambiqueCountry('Mozambique'));
        $this->assertTrue(MozambiqueTaxNumber::isMozambiqueCountry('Moçambique'));
        $this->assertTrue(MozambiqueTaxNumber::isMozambiqueCountry('Republic of Mozambique'));
        $this->assertFalse(MozambiqueTaxNumber::isMozambiqueCountry('Portugal'));
    }
}
