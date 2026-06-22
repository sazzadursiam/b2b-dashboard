<?php

namespace App\DataTransferObjects;

use App\Http\Requests\StoreOrderRequest;

class OrderData
{
    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function __construct(
        public readonly array $items,
    ) {}

    public static function fromRequest(StoreOrderRequest $request): self
    {
        return new self(
            items: $request->validated('items'),
        );
    }
}
