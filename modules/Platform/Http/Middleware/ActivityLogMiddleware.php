<?php

namespace Modules\Platform\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Platform\Models\ActivityLog;

class ActivityLogMiddleware
{
    private static array $skipPaths = [
        'auth/login', 'auth/logout', 'auth/me',
        'activity-logs', 'basket',
    ];

    private static array $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log mutating requests that succeeded
        if (!in_array($request->method(), self::$mutatingMethods)) {
            return $response;
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return $response;
        }

        // Skip noisy or internal paths
        $path = $request->path();
        foreach (self::$skipPaths as $skip) {
            if (str_contains($path, $skip)) return $response;
        }

        try {
            $user    = $request->user();
            $orgId   = $this->resolveOrgId($request);
            $action  = $this->resolveAction($request);
            $entity  = $this->resolveEntity($request);

            if ($orgId && $action) {
                ActivityLog::create([
                    'org_id'          => $orgId,
                    'actor_id'        => $user?->actor_id,
                    'actor_name'      => $user?->name ?? $user?->username,
                    'actor_role'      => $user?->primaryRole?->name ?? 'admin',
                    'action'          => $action,
                    'entity_type'     => $entity['type'] ?? 'unknown',
                    'entity_id'       => $entity['id'] ?? null,
                    'entity_snapshot' => $this->snapshot($request, $response),
                    'ip_address'      => $request->ip(),
                    'user_agent'      => $request->userAgent(),
                    'occurred_at'     => now(),
                ]);
            }
        } catch (\Throwable $e) {
            // Non-fatal — never let logging break the request
            \Log::warning('ActivityLog middleware error: ' . $e->getMessage());
        }

        return $response;
    }

    private function resolveOrgId(Request $request): ?string
    {
        // Try route parameter first
        $orgId = $request->route('orgId');
        if ($orgId) return $orgId;

        // Try request body
        return $request->input('seller_org_id')
            ?? $request->input('org_id')
            ?? null;
    }

    private function resolveAction(Request $request): string
    {
        $path = $request->path();
        return match (true) {
            str_contains($path, '/confirm')    => 'confirmed',
            str_contains($path, '/ship')       => 'shipped',
            str_contains($path, '/deliver')    => 'delivered',
            str_contains($path, '/cancel')     => 'cancelled',
            str_contains($path, '/archive')    => 'archived',
            str_contains($path, '/publish')    => 'published',
            str_contains($path, '/assign')     => 'assigned',
            str_contains($path, '/admin')      => 'created',
            $request->method() === 'POST'      => 'created',
            $request->method() === 'PATCH'     => 'updated',
            $request->method() === 'PUT'       => 'updated',
            $request->method() === 'DELETE'    => 'deleted',
            default                            => 'modified',
        };
    }

    private function resolveEntity(Request $request): array
    {
        $path     = $request->path();
        $segments = explode('/', $path);

        // Map path segments to entity types
        $entityMap = [
            'orders'       => 'order',
            'products'     => 'product',
            'customers'    => 'customer',
            'officers'     => 'officer',
            'promotions'   => 'promotion',
            'visits'       => 'visit',
            'weekly-plans' => 'weekly_plan',
            'payments'     => 'payment',
        ];

        $type = 'unknown';
        $id   = null;

        foreach ($segments as $i => $segment) {
            if (isset($entityMap[$segment])) {
                $type = $entityMap[$segment];
                // The segment after the entity type is usually the ID
                if (isset($segments[$i + 1]) && strlen($segments[$i + 1]) === 26) {
                    $id = $segments[$i + 1];
                }
                break;
            }
        }

        return ['type' => $type, 'id' => $id];
    }

    private function snapshot(Request $request, $response): ?array
    {
        $data = [];

        // Capture safe request fields (exclude passwords/tokens)
        $skip = ['password', 'token', 'secret', 'key', 'authorization'];
        foreach ($request->except($skip) as $k => $v) {
            if (is_scalar($v)) $data['input'][$k] = $v;
        }

        // Capture response ID if available
        try {
            $body = json_decode($response->getContent(), true);
            if (isset($body['order']['id']))    $data['entity_id'] = $body['order']['id'];
            if (isset($body['product']['id']))  $data['entity_id'] = $body['product']['id'];
            if (isset($body['customer']['id'])) $data['entity_id'] = $body['customer']['id'];
        } catch (\Throwable $_) {}

        return empty($data) ? null : $data;
    }
}