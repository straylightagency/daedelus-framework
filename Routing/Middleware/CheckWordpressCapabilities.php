<?php

namespace Daedelus\Framework\Routing\Middleware;

use Closure;
use Daedelus\Cogent\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckWordpressCapabilities
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param mixed ...$capabilities
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$capabilities): mixed
    {
        $is_authorized = false;

        if ( $user_id = get_current_user_id() ) {
            /** @var User $user */
            $user = Auth::guard('wordpress')->loginUsingId( $user_id );

            $is_authorized = $user->hasCapabilities( $capabilities );
        }

        abort_unless( $is_authorized, 403, 'Sorry, you are not authorized to view this page.');

        return $next( $request );
    }
}