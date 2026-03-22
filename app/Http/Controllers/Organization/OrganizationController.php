<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganization;
use App\Actions\Organization\DeleteOrganization;
use App\Actions\Organization\UpdateOrganization;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the user's organizations.
     */
    public function index(Request $request): Response
    {
        $organizations = $request->user()
            ->organizationMemberships()
            ->with('organization')
            ->get()
            ->pluck('organization');

        return Inertia::render('organizations/index', [
            'organizations' => $organizations,
        ]);
    }

    /**
     * Show the form for creating a new organization.
     */
    public function create(): Response
    {
        return Inertia::render('organizations/create');
    }

    /**
     * Store a newly created organization.
     */
    public function store(Request $request, CreateOrganization $action): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        $organization = $action->handle($request->user(), $data);

        return to_route('organizations.show', $organization);
    }

    /**
     * Display the specified organization (redirect to dashboard with context).
     */
    public function show(Organization $organization): Response
    {
        return Inertia::render('organizations/show', [
            'organization' => $organization,
        ]);
    }

    /**
     * Show the form for editing the organization.
     */
    public function edit(Organization $organization): Response
    {
        return Inertia::render('organizations/edit', [
            'organization' => $organization,
        ]);
    }

    /**
     * Update the specified organization.
     */
    public function update(Request $request, Organization $organization, UpdateOrganization $action): RedirectResponse
    {
        $this->authorize('update', $organization);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash'],
            'logo_url' => ['nullable', 'string', 'url', 'max:2048'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        $action->handle($organization, $data);

        return to_route('organizations.edit', $organization);
    }

    /**
     * Remove the specified organization.
     */
    public function destroy(Request $request, Organization $organization, DeleteOrganization $action): RedirectResponse
    {
        $action->handle($organization, $request->user());

        return to_route('organizations.index');
    }
}
