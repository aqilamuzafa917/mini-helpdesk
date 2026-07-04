<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketNumberService
{
    /**
     * Generate the next sequential ticket number in TKT-NNNNN format.
     */
    public function generate(): string
    {
        $max = DB::transaction(function () {
            // Lock the tickets table to prevent concurrent reads that lead to duplicate numbers
            $last = Ticket::lockForUpdate()->max('ticket_number');

            if ($last && preg_match('/^TKT-(\d+)$/', $last, $matches)) {
                return ((int) $matches[1]) + 1;
            }

            return 1;
        });

        return sprintf('TKT-%05d', $max);
    }
}
