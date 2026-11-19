<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Batasi akses halaman berdasarkan role pengguna yang login.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = $user !== null
            && $user->is_active
            && $user->role instanceof Role
            && in_array($user->role->value, $roles, true);

        abort_unless($allowed, 403, 'Akun Anda tidak memiliki akses ke halaman ini.');

        return $next($request);
    }
}
