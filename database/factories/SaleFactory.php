<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'order_id' => null,
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'total_amount' => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
