<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => null,
            'status' => 'pending',
            'total_amount' => fake()->randomFloat(2, 10, 1000),
            'idempotency_key' => null,
        ];
    }
}
