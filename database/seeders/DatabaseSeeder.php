<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     *
     * Login: test@example.com / password
     */
    public function run(): void
    {
        $business = Business::factory()->create(['name' => 'Acme Corp']);

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'business_id' => $business->id,
        ]);

        $business->update(['owner_id' => $user->id]);

        $products = Product::factory()
            ->count(5)
            ->create(['business_id' => $business->id]);

        foreach ($products as $product) {
            Inventory::factory()->create([
                'business_id' => $business->id,
                'product_id' => $product->id,
                'stock' => 500,
            ]);
        }

        foreach (range(1, 40) as $i) {
            $product = $products->random();
            $quantity = random_int(1, 4);

            $order = Order::factory()->create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'status' => 'paid',
                'total_amount' => $product->price * $quantity,
                'created_at' => now()->subMinutes(random_int(0, 60 * 24 * 30)),
            ]);

            OrderItem::factory()->create([
                'business_id' => $business->id,
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->price,
                'total_price' => $product->price * $quantity,
            ]);
        }
    }
}
