<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\MonthlyReportRemark;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyReportRemark>
 */
class MonthlyReportRemarkFactory extends Factory
{
    protected $model = MonthlyReportRemark::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2025, 2030),
            'remarks' => fake()->paragraph(),
            'created_by' => User::factory(),
        ];
    }
}
