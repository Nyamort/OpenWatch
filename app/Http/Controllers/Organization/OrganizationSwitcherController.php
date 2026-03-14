<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\SwitchOrganization;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrganizationSwitcherController extends Controller
{
    /**
     * Switch the authenticated user's active organization.
     */
    public function store(Request $request, SwitchOrganization $action): RedirectResponse
    {
        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ]);

        $organization = Organization::findOrFail($data['organization_id']);

        $action->handle($request->user(), $organization);

        return redirect()->back();
    }
}
