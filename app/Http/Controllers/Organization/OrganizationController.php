<?php

namespace App\Http\Controllers\Organization;

use App\Actions\Organization\CreateOrganization;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    /**
     * Store a newly created organization.
     */
    public function store(Request $request, CreateOrganization $action): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);
            $data['slug'] = Str::slug($data['name']);
            $organization = $action->handle($request->user(), $data);

            return response()->json([
                'organization' => ['id' => $organization->id, 'name' => $organization->name, 'slug' => $organization->slug],
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash'],
            'timezone' => ['nullable', 'string', 'max:64'],
        ]);

        $organization = $action->handle($request->user(), $data);

        return to_route('dashboard');
    }
}
