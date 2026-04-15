<?php

// Simulate exactly what auth/me does for mashaka
$user = \Modules\Platform\Models\User::find('01KNHQ0ZGP36TE8KG4WGJMV5KJ');
echo "User found: {$user->email}\n";

// Call orgMemberships exactly as auth/me does
$membership = $user->orgMemberships()
    ->with(['orgRole.permissions'])
    ->where('status', 'active')
    ->orderByDesc('level')
    ->first();

echo "Membership found: " . ($membership ? 'YES' : 'NO') . "\n";

if ($membership) {
    echo "Org ID: {$membership->org_id}\n";
    echo "OrgRole: " . ($membership->orgRole?->name ?? 'null') . "\n";
    echo "OrgRole slug: " . ($membership->orgRole?->slug ?? 'null') . "\n";
} else {
    // Check the raw query being used
    echo "\nRaw orgMemberships query:\n";
    $query = $user->orgMemberships()->where('status', 'active')->toSql();
    echo $query . "\n";
    echo "\nBindings: " . json_encode($user->orgMemberships()->where('status', 'active')->getBindings()) . "\n";
}