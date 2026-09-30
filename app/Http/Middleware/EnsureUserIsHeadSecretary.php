<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsHeadSecretary
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = mb_strtolower(trim(preg_replace(
            '/\s+/',
            ' ',
            (string) $request->session()->get('login_user.tungkulin', '')
        ) ?? ''));

        abort_unless(in_array($role, ['head secretary', 'secretary head'], true), 403, 'Only the Head Secretary can access this portal.');

        return $next($request);
    }
}
