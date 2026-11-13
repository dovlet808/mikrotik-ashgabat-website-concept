<?php

namespace Tests\Unit;

use App\Support\Money;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_formats_manat_per_locale(): void
    {
        $this->assertSame("2\u{202F}850\u{00A0}манат", Money::format(2850, 'TMT', 'ru'));
        $this->assertSame("14\u{202F}000\u{00A0}manat", Money::format(14000, 'TMT', 'tk'));
        $this->assertSame("2,850\u{00A0}manat", Money::format(2850, 'TMT', 'en'));
        $this->assertSame("850\u{00A0}манат", Money::format(850, 'TMT', 'ru'));
        $this->assertSame("1\u{202F}234,50\u{00A0}манат", Money::format(1234.5, 'TMT', 'ru'));
    }
}
