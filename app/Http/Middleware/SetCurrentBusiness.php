<?php

namespace App\Http\Middleware;

use App\Support\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBusiness
{
    public function __construct(private CurrentBusiness $currentBusiness) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            $this->currentBusiness->set($user->business_id);
        }

        return $next($request);
    }
}
