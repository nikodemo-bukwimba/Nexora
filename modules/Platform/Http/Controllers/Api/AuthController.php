<?php

namespace Modules\Platform\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Contracts\Services\AuthServiceInterface;
use Modules\Platform\Http\Requests\Auth\ApiLoginRequest;
use Modules\Platform\Http\Requests\Auth\ApiRegisterRequest;
use Modules\Platform\Models\User;

class AuthController extends Controller
{
    public function __construct(
        protected AuthServiceInterface $auth
    ) {}

    /**
     * POST /api/v1/auth/register
     */
    public function register(ApiRegisterRequest $request): JsonResponse
    {
        $user  = $this->auth->register($request->validated());
        $token = $user->createToken($request->device_name ?? 'api')->plainTextToken;

        $this->auth->recordLogin($user, $request->ip());

        return response()->json([
            'user'  => [
                'id'       => $user->id,
                'username' => $user->username,
                'email'    => $user->email,
                'status'   => $user->status,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(ApiLoginRequest $request): JsonResponse
    {
        $token = $this->auth->loginWithToken(
            $request->email,
            $request->password,
            $request->device_name ?? 'api'
        );

        if (! $token) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = User::where('email', $request->email)->first();
        $this->auth->recordLogin($user, $request->ip());

        return response()->json(['token' => $token]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->auth->revokeToken($request->user());

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('actor');

        return response()->json([
            'id'           => $user->id,
            'username'     => $user->username,
            'email'        => $user->email,
            'display_name' => $user->actor?->display_name,
            'status'       => $user->status,
        ]);
    }
}
