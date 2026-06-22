<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\OrderStateService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::where('business_id', $request->user()->business_id)
            ->latest('id')
            ->cursorPaginate(25);

        return response()->json([
            'data' => $orders->items(),
            'next_cursor' => optional($orders->nextCursor())->encode(),
        ]);
    }

    public function store(Request $request, OrderService $service)
    {
        $result = $service->createOrder($request);

        return response()->json($result['body'], $result['status']);
    }

    public function updateStatus(Request $request, Order $order, OrderStateService $service)
    {
        abort_if($order->business_id !== $request->user()->business_id, 403);

        $data = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $order = $service->transition(
            order: $order,
            toState: $data['status'],
            user: $request->user(),
            metadata: ['source' => 'api']
        );

        return response()->json([
            'message' => 'Order status updated.',
            'order' => $order,
        ]);
    }
}
