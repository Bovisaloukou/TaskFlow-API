<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invitation\AcceptInvitationRequest;
use App\Http\Requests\Invitation\SendInvitationRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\UserResource;
use App\Models\OrganizationInvitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Invitations
 *
 * APIs for managing organization invitations
 */
class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitationService)
    {
    }

    /**
     * List Invitations
     *
     * Get all invitations for the current organization.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('invitations.view');

        $invitations = OrganizationInvitation::with('inviter')
            ->where('organization_id', $request->user()->organization_id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return InvitationResource::collection($invitations);
    }

    /**
     * Send Invitation
     *
     * @bodyParam email string required The email to invite. Example: jane@example.com
     * @bodyParam role string The role to assign (admin, manager, user). Example: user
     */
    public function store(SendInvitationRequest $request): JsonResponse
    {
        $invitation = $this->invitationService->create([
            'organization_id' => $request->user()->organization_id,
            'email' => $request->email,
            'role' => $request->role ?? 'user',
            'invited_by' => $request->user()->id,
        ]);

        return (new InvitationResource($invitation->load('inviter')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete Invitation
     */
    public function destroy(OrganizationInvitation $invitation): JsonResponse
    {
        $this->authorize('invitations.delete');

        $this->invitationService->delete($invitation);

        return response()->json(['message' => 'Invitation deleted successfully.']);
    }

    /**
     * Accept Invitation
     *
     * Accept an invitation using the token. This is a public endpoint.
     *
     * @bodyParam name string required The user's name. Example: Jane Smith
     * @bodyParam password string required Min 8 characters. Example: password123
     * @bodyParam password_confirmation string required Must match password. Example: password123
     *
     * @unauthenticated
     */
    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $invitation = OrganizationInvitation::withoutGlobalScopes()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return response()->json(['message' => 'This invitation has expired.'], 422);
        }

        $user = $this->invitationService->accept($invitation, $request->validated());

        $authToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Invitation accepted successfully.',
            'data' => [
                'user' => new UserResource($user->load('roles')),
                'token' => $authToken,
            ],
        ], 201);
    }
}
