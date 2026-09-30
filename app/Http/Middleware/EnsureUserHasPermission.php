<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->session()->get('login_user', []);
        if ($this->isHeadSecretary($user['tungkulin'] ?? '')) {
            return $next($request);
        }

        if (! in_array($permission, [
            'dashboard_allow',
            'form_generator_allow',
            'Delistment_allow',
            'Inquire_Allow',
        ], true) || ! ($user[$permission] ?? false)) {
            abort(403, 'Your account does not have permission to access this feature.');
        }

        return $next($request);
    }

    private function isHeadSecretary(string $role): bool
    {
        $role = mb_strtolower(trim(preg_replace('/\s+/', ' ', $role) ?? $role));

        return in_array($role, ['head secretary', 'secretary head'], true);
    }
}
