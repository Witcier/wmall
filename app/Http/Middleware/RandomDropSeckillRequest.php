<?php

namespace App\Http\Middleware;

use App\Exceptions\InvalidRequestException;
use Closure;
use Illuminate\Http\Request;

class RandomDropSeckillRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $percent)
    {
        if (random_int(1, 100) < $percent) {
            throw new InvalidRequestException('参与的用户过多, 请稍后再试', 403);
        }

        return $next($request);
    }
}
