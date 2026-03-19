<?php

namespace App\Models;

/*
|--------------------------------------------------------------------------
| App\Models\User — thin alias
|--------------------------------------------------------------------------
| The canonical User model lives in Modules\Platform\Models\User.
| This class exists only so Laravel's default references (auth config,
| Fortify, Sanctum, etc.) resolve correctly without any config changes.
|
| Do NOT add business logic here — everything goes in the Platform model.
*/

class User extends \Modules\Platform\Models\User
{
    //
}
