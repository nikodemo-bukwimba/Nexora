<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Routing\Controller;

/**
 * DEPRECATED stub — routes using this controller should be removed.
 * Use Modules\Platform\Http\Controllers\Api\Org\OrgRoleController instead.
 */
class OrgRoleController extends Controller
{
    public function __call($method, $parameters)
    {
        return response()->json([
            'message'    => 'This endpoint has moved.',
            'deprecated' => static::class . '@' . $method,
        ], 410);
    }
}
