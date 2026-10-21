<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $actorId = $request->user()?->getAuthIdentifier();
        $response = $next($request);

        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $context = [
                'uid' => $actorId,
                'ip' => $request->ip(),
                'occurred_at' => now()->toIso8601String(),
                'action' => $request->method().' '.$request->path(),
                'http_status' => $response->getStatusCode(),
                'request_id' => $request->headers->get('X-Request-ID'),
            ];
            $context['sha256'] = hash('sha256', json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            Log::channel(config('aquarium.audit_log_channel', 'daily'))->info('AQUARIUM action', $context);
        }

        return $response;
    }
}
