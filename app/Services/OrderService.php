<?php

namespace App\Services;

use App\DataTransferObjects\OrderData;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function place(OrderData $data, User $user): Order
    {
        return DB::transaction(function () use ($data, $user) {
            $productIds = collect($data->items)->pluck('product_id')->unique();

            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                abort(422, 'Invalid product selected.');
            }

            $order = Order::create([
                'user_id' => $user->id,
                'status' => OrderStatus::Pending,
                'total_amount' => 0,
            ]);

            $total = 0;

            foreach ($data->items as $item) {
                $product = $products[$item['product_id']];
                $lineTotal = $product->price * $item['quantity'];
                $total += $lineTotal;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                ]);
            }

            $order->update(['total_amount' => $total]);

            return $order->load('items');
        });
    }
}
