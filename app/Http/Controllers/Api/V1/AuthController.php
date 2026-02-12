<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for managing authentication
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * Create a new organization and admin user.
     *
     * @bodyParam name string required The user's name. Example: John Doe
     * @bodyParam email string required The user's email. Example: john@example.com
     * @bodyParam password string required Min 8 characters. Example: password123
     * @bodyParam password_confirmation string required Must match password. Example: password123
     * @bodyParam organization_name string required The organization name. Example: Acme Corp
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $organization = Organization::create([
                'name' => $request->organization_name,
                'slug' => Str::slug($request->organization_name) . '-' . Str::random(5),
            ]);

            $user = User::withoutGlobalScopes()->create([
                'organization_id' => $organization->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
            ]);

            $organization->update(['owner_id' => $user->id]);

            $user->assignRole('admin');

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Registration successful.',
                'data' => [
                    'user' => new UserResource($user->load('roles')),
                    'token' => $token,
                ],
            ], 201);
        });
    }

    /**
     * Login
     *
     * Authenticate a user and return a token.
     *
     * @bodyParam email string required The user's email. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::withoutGlobalScopes()
            ->where('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user->load('roles')),
                'token' => $token,
            ],
        ]);
    }

    /**
     * Logout
     *
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if (method_exists($token, 'delete')) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Refresh Token
     *
     * Revoke current token and issue a new one.
     */
    public function refresh(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        if (method_exists($currentToken, 'delete')) {
            $currentToken->delete();
        }

        $token = $request->user()->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
            ],
        ]);
    }

    /**
     * Current User
     *
     * Get the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($request->user()->load('roles')),
        ]);
    }
}
