<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // App is only reachable through the front proxies (Caddy -> nginx, and the
        // public Node site for /offerte). Trust them so X-Forwarded-For/Proto give
        // the real client IP (used as evidence on quote acceptance) and https scheme.
        $middleware->trustProxies(at: '*');

        // The quote pages are proxied in from the public domain (desnipperaar.nl).
        // The unguessable 64-char quote_token is the authenticator, so the accept
        // POST is CSRF-exempt (same stance as the token-gated /api/* endpoints)
        // and never depends on a session cookie surviving the proxy.
        $middleware->validateCsrfTokens(except: ['offerte/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
