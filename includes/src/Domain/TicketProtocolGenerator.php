<?php
declare(strict_types=1);

namespace ReportaBlu\Domain;

final class TicketProtocolGenerator
{
    public function generate(): string
    {
        return 'RB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}
