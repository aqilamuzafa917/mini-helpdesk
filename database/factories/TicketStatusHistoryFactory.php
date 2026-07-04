<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatusHistory>
 */
class TicketStatusHistoryFactory extends Factory
{
    protected $model = TicketStatusHistory::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'old_status' => null,
            'new_status' => 'open',
            'notes' => fake()->sentence(),
            'changed_by' => User::factory(),
            'changed_at' => now(),
        ];
    }
}
