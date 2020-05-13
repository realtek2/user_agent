<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class StoreReferralCode
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->has('ref'))
        {
            $refId = filter_var($request->get('ref'), FILTER_VALIDATE_INT, array("options" => array("min_range" => 0, "max_range" => PHP_INT_MAX)));
            if (is_int($refId))
            {
                # 7 days in minutes = 10080
                Cookie::queue('ref', $refId, 10080);
                return redirect($request->url());
            }
        }

        return $response;
    }
}
