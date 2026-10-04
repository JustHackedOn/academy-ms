<?php

namespace Tests\Unit;

use App\Services\PhoneFormatter;
use PHPUnit\Framework\TestCase;

class PhoneFormatterTest extends TestCase
{
    public function test_local_format_with_leading_zero_is_normalized(): void
    {
        $this->assertSame('923001234567', PhoneFormatter::normalize('0300-1234567'));
        $this->assertSame('923001234567', PhoneFormatter::normalize('0300 1234567'));
        $this->assertSame('923001234567', PhoneFormatter::normalize('0300.1234567'));
    }

    public function test_format_without_country_code_is_normalized(): void
    {
        $this->assertSame('923001234567', PhoneFormatter::normalize('3001234567'));
    }

    public function test_already_normalized_number_passes_through(): void
    {
        $this->assertSame('923001234567', PhoneFormatter::normalize('923001234567'));
        $this->assertSame('923001234567', PhoneFormatter::normalize('+92 300 1234567'));
        $this->assertSame('923001234567', PhoneFormatter::normalize('0092 300 1234567'));
    }

    public function test_display_renders_the_pakistani_style(): void
    {
        $this->assertSame('0300-1234567', PhoneFormatter::display('923001234567'));
        $this->assertSame('0300-1234567', PhoneFormatter::display('0300-1234567'));
    }

    public function test_empty_values_are_handled_gracefully(): void
    {
        $this->assertSame('', PhoneFormatter::normalize(null));
        $this->assertSame('', PhoneFormatter::normalize(''));
        $this->assertSame('', PhoneFormatter::display(null));
    }
}
