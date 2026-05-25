<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Won = 'won';
    case Lost = 'lost';

    /**
     * Whether this status represents a closed (terminal) lead.
     */
    public function isTerminal(): bool
    {
        return collect([self::Won, self::Lost])->contains($this);
    }
}
