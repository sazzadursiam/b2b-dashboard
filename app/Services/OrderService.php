<?php
namespace App\Services;

use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(Request $request): array
    {
        $user = $request->user();
        $businessId = $user->business_id;
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($idempotencyKey) {
            $existing = IdempotencyKey::where('business_id', $businessId)
                ->where('key', $idempotencyKey)
                ->where('endpoint', 'POST /api/v1/orders')
                ->where('expires_at', '>', now())
                ->first();

            if ($existing) {
                return [
                    'body' => $existing->response_body,
                    'status' => $existing->status_code,
                ];
            }
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        return DB::transaction(function () use ($validated, $user, $businessId, $idempotencyKey) {
            $productIds = collect($validated['items'])->pluck('product_id')->unique();

            $products = Product::where('business_id', $businessId)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                abort(422, 'Invalid product selected.');
            }

            $totalAmount = 0;

            foreach ($validated['items'] as $item) {
                $product = $products[$item['product_id']];
                $totalAmount += $product->price * $item['quantity'];
            }

            $order = Order::create([
                'business_id' => $businessId,
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($validated['items'] as $item) {
                $product = $products[$item['product_id']];

                OrderItem::create([
                    'business_id' => $businessId,
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $item['quantity'],
                ]);
            }

            $order->load('items');

            $response = [
                'message' => 'Order created successfully.',
                'order' => $order,
            ];

            if ($idempotencyKey) {
                IdempotencyKey::create([
                    'business_id' => $businessId,
                    'key' => $idempotencyKey,
                    'endpoint' => 'POST /api/v1/orders',
                    'response_body' => $response,
                    'status_code' => 201,
                    'expires_at' => now()->addDay(),
                ]);
            }

            return [
                'body' => $response,
                'status' => 201,
            ];
        });
    }
}
