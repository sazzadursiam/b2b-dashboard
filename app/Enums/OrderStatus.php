<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Canceled = 'canceled';

    /**
     * States this status is allowed to move directly into.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Canceled],
            self::Paid => [self::Processing, self::Canceled],
            self::Processing => [self::Shipped, self::Canceled],
            self::Shipped => [self::Delivered],
            self::Delivered, self::Canceled => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        // Cancellation is allowed from any non-terminal state.
        if ($to === self::Canceled) {
            return ! $this->isTerminal();
        }

        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Canceled], true);
    }
}
