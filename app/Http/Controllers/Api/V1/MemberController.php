<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateMemberRoleRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Members
 *
 * APIs for managing organization members
 */
class MemberController extends Controller
{
    /**
     * List Members
     *
     * Get all members of the current organization.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $members = User::with('roles')
            ->where('organization_id', $request->user()->organization_id)
            ->paginate(15);

        return UserResource::collection($members);
    }

    /**
     * Remove Member
     *
     * Remove a member from the organization.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('members.remove');

        if ($user->organization_id !== $request->user()->organization_id) {
            abort(404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot remove yourself.'], 422);
        }

        if ($user->id === $user->organization->owner_id) {
            return response()->json(['message' => 'Cannot remove the organization owner.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Member removed successfully.']);
    }

    /**
     * Update Member Role
     *
     * @bodyParam role string required The new role. Example: manager
     */
    public function updateRole(UpdateMemberRoleRequest $request, User $user): JsonResponse
    {
        if ($user->organization_id !== $request->user()->organization_id) {
            abort(404);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => new UserResource($user->load('roles')),
        ]);
    }
}
