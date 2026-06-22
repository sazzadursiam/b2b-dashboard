<?php

namespace Tests;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array{0: Business, 1: User}
     */
    protected function makeBusinessWithUser(): array
    {
        $business = Business::factory()->create();
        $user = User::factory()->create(['business_id' => $business->id]);

        return [$business, $user];
    }
}
