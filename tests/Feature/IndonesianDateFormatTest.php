<?php

namespace Tests\Feature;

use Tests\TestCase;

class IndonesianDateFormatTest extends TestCase
{
    public function test_format_jam_id(): void
    {
        $this->assertSame('10.00 WIB', format_jam_id('10:00:00'));
        $this->assertSame('10.00 WIB', format_jam_id('10:00'));
        $this->assertSame('19.30 WIB', format_jam_id('19:30:00'));
        $this->assertSame('10.00', format_jam_id('10:00:00', false));
        $this->assertSame('19.30', format_jam_id('19:30:00', false));
    }

    public function test_format_tanggal_id(): void
    {
        $this->assertSame('Rab, 28 Okt 2026', format_tanggal_id('2026-10-28'));
        $this->assertSame('Min, 1 Nov 2026', format_tanggal_id('2026-11-01'));
    }

    public function test_format_tanggal_pendek_id(): void
    {
        $this->assertSame('28 Okt', format_tanggal_pendek_id('2026-10-28'));
        $this->assertSame('1 Nov', format_tanggal_pendek_id('2026-11-01'));
        $this->assertSame('15 Mar', format_tanggal_pendek_id('2026-03-15'));
        $this->assertSame('20 Agt', format_tanggal_pendek_id('2026-08-20'));
    }

    public function test_format_hari_tanggal_id(): void
    {
        $this->assertSame('Rabu, 28 Oktober 2026', format_hari_tanggal_id('2026-10-28'));
        $this->assertSame('Minggu, 1 November 2026', format_hari_tanggal_id('2026-11-01'));
    }
}
