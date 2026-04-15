<?php

// Check what route handles invitation acceptance
$routes = \Illuminate\Support\Facades\Route::getRoutes();
foreach ($routes as $route) {
    $uri = $route->uri();
    if (str_contains($uri, 'invitation') || str_contains($uri, 'accept')) {
        echo $route->methods()[0] . " /" . $uri . " → " . $route->getActionName() . "\n";
    }
}

echo "\n";

// Check jinasaluhondo's current membership state
$user = \Illuminate\Support\Facades\DB::selectOne("
    SELECT id, email, status FROM platform.users WHERE email = 'jinasaluhondo@gmail.com'
");
echo "User: {$user->email} | ID: {$user->id}\n";

$memberships = \Illuminate\Support\Facades\DB::select("
    SELECT m.id, m.status, o.name as org_name, r.name as role_name
    FROM platform.org_memberships m
    JOIN platform.organizations o ON o.id = m.org_id
    LEFT JOIN platform.org_roles r ON r.id = m.org_role_id
    WHERE m.user_id = ?
", [$user->id]);

echo "Memberships: " . count($memberships) . "\n";
foreach ($memberships as $m) {
    echo "  Org: {$m->org_name} | Role: {$m->role_name} | Status: {$m->status}\n";
}

echo "\n";

// Check the invitation record and what token maps to
$inv = \Illuminate\Support\Facades\DB::selectOne("
    SELECT i.*, o.name as org_name, r.name as role_name
    FROM platform.org_invitations i
    JOIN platform.organizations o ON o.id = i.org_id
    LEFT JOIN platform.org_roles r ON r.id = i.org_role_id
    WHERE i.email = 'jinasaluhondo@gmail.com'
    ORDER BY i.created_at DESC
    LIMIT 1
");

if ($inv) {
    echo "Latest invitation:\n";
    echo "  Token: {$inv->token}\n";
    echo "  Status: {$inv->status}\n";
    echo "  Org: {$inv->org_name}\n";
    echo "  Role: {$inv->role_name}\n";
    echo "  Expires: {$inv->expires_at}\n";
}
