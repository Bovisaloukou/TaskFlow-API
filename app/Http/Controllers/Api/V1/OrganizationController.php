<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Organization
 *
 * APIs for managing the current organization
 */
class OrganizationController extends Controller
{
    /**
     * Show Organization
     *
     * Get the current user's organization details.
     */
    public function show(Request $request): OrganizationResource
    {
        return new OrganizationResource(
            $request->user()->organization->load('owner')
        );
    }

    /**
     * Update Organization
     *
     * @bodyParam name string The organization name. Example: Acme Corp
     */
    public function update(Request $request): OrganizationResource
    {
        $this->authorize('organization.update');

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        $organization = $request->user()->organization;
        $organization->update($validated);

        return new OrganizationResource($organization->fresh()->load('owner'));
    }
}
