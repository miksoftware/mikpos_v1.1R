<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson() || $request->header('X-Livewire')) {
                abort(403, 'No tienes permiso para realizar esta acción.');
            }

            // For normal navigation, redirect the user to a page they DO have
            // access to instead of slamming them with a 403 (e.g. a cook hitting
            // /dashboard right after login).
            $landing = $user->landingRoute();
            if ($landing && $landing !== $request->url() && $landing !== route('login')) {
                return redirect($landing)->with(
                    'flash_warning',
                    'No tienes acceso a esa sección. Te llevamos a tu panel.'
                );
            }

            // Show 403 error page if there's nowhere safe to send them
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
