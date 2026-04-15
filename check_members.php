<?php

// ── Memberships ───────────────────────────────────────────────
echo "=== MEMBERSHIPS ===\n";
$rows = \Illuminate\Support\Facades\DB::select("
    SELECT
        u.email,
        u.status as user_status,
        o.name as org_name,
        o.type as org_type,
        r.name as role_name,
        r.slug as role_slug,
        m.status as membership_status,
        m.level,
        m.created_at
    FROM platform.org_memberships m
    JOIN platform.users u ON u.id = m.user_id
    JOIN platform.organizations o ON o.id = m.org_id
    LEFT JOIN platform.org_roles r ON r.id = m.org_role_id
    ORDER BY m.created_at DESC
");
echo "Total: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "Email: {$r->email} | Org: {$r->org_name} ({$r->org_type}) | Role: " . ($r->role_name ?? 'none') . " [{$r->role_slug}] | MemStatus: {$r->membership_status}\n";
}

echo "\n";

// ── Invitations ───────────────────────────────────────────────
echo "=== INVITATIONS ===\n";
$rows = \Illuminate\Support\Facades\DB::select("
    SELECT
        i.email,
        i.token,
        i.status,
        i.expires_at,
        o.name as org_name,
        o.type as org_type,
        r.name as role_name
    FROM platform.org_invitations i
    JOIN platform.organizations o ON o.id = i.org_id
    LEFT JOIN platform.org_roles r ON r.id = i.org_role_id
    ORDER BY i.created_at DESC
");
echo "Total: " . count($rows) . "\n";
foreach ($rows as $r) {
    $expired = ($r->expires_at && strtotime($r->expires_at) < time()) ? " [EXPIRED]" : "";
    echo "Email: {$r->email} | Org: {$r->org_name} ({$r->org_type}) | Role: " . ($r->role_name ?? 'none') . " | Status: {$r->status}{$expired}\n";
}

echo "\n";

// ── Users with no membership ──────────────────────────────────
echo "=== USERS WITH NO MEMBERSHIP ===\n";
$rows = \Illuminate\Support\Facades\DB::select("
    SELECT u.email, u.status
    FROM platform.users u
    LEFT JOIN platform.org_memberships m ON m.user_id = u.id
    WHERE m.id IS NULL
");
echo "Total: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "Email: {$r->email} | Status: {$r->status}\n";
}