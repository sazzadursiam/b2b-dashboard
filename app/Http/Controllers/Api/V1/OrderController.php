<?php

namespace App\Http\Controllers\Api\V1;

use App\DataTransferObjects\OrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\IdempotencyService;
use App\Services\OrderService;
use App\Services\OrderStateService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::latest('id')->cursorPaginate(25);

        return OrderResource::collection($orders->items())->additional([
            'next_cursor' => optional($orders->nextCursor())->encode(),
        ]);
    }

    public function store(StoreOrderRequest $request, OrderService $orders, IdempotencyService $idempotency)
    {
        $key = $request->header('Idempotency-Key');
        $endpoint = 'POST /api/v1/orders';

        if ($key && $hit = $idempotency->find($key, $endpoint)) {
            return response()->json($hit->response_body, $hit->status_code);
        }

        $order = $orders->place(OrderData::fromRequest($request), $request->user());

        $response = response()->json([
            'message' => 'Order created successfully.',
            'order' => new OrderResource($order),
        ], 201);

        if ($key) {
            $idempotency->store($key, $endpoint, $response->getData(true), 201);
        }

        return $response;
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderStateService $service)
    {
        abort_if($order->business_id !== $request->user()->business_id, 404);

        $order = $service->transition(
            order: $order,
            toState: $request->status(),
            user: $request->user(),
            metadata: ['source' => 'api'],
        );

        return response()->json([
            'message' => 'Order status updated.',
            'order' => new OrderResource($order),
        ]);
    }
}
