<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityMiddleware
 *
 * Enhances application security by enforcing HTTPS redirection in production,
 * removing fingerprinting headers, and injecting restrictive security headers
 * along with a strict Content Security Policy (CSP).
 *
 * @package App\Http\Middleware
 */
class SecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Enforce HTTPS redirection in production environments before processing the request (Before Middleware)
        if (config('app.env') === 'production' && !$request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }

        // 2. Pass the request to the next boundary and capture the generated Response
        $response = $next($request);

        // 3. Remove sensitive application headers to prevent server-side profiling (Fingerprinting)
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        $response->headers->remove('x-turbo-charged-by');

        // 4. Set essential security-hardening HTTP headers
        $response->headers->set('X-Frame-Options', 'deny');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');
        $response->headers->set('X-Download-Options', 'noopen');

        // 5. Enforce a strict Content Security Policy (CSP)
        // Scramble docs rely on external CDN assets, so allow those only for the docs endpoints.
        if ($request->is('docs/*') || $request->path() === 'docs/api' || $request->path() === 'docs/api.json') {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self' https://unpkg.com; " .
                    "style-src 'self' 'unsafe-inline' https://unpkg.com; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com; " .
                    "img-src 'self' data: https://unpkg.com; " .
                    "connect-src 'self' https://unpkg.com; " .
                    "font-src 'self' https://unpkg.com data:; " .
                    "frame-ancestors 'none'; " .
                    "form-action 'self'; " .
                    "base-uri 'self'; " .
                    "object-src 'none'; " .
                    "upgrade-insecure-requests"
            );

            return $response;
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'none'; " .
                "style-src 'self' 'unsafe-inline'; " .
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
                "img-src 'self' data:; " .
                "connect-src 'self'; " .
                "font-src 'self'; " .
                "frame-ancestors 'none'; " .
                "form-action 'self'; " .
                "base-uri 'self'; " .
                "object-src 'none'; " .
                "upgrade-insecure-requests"
        );

        return $response;
    }
}
