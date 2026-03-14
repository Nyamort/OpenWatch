<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\OrganizationMember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationMember
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $resolved = $request->route('organization');

        $organization = $resolved instanceof Organization
            ? $resolved
            : Organization::where('slug', $resolved)->firstOrFail();

        $member = OrganizationMember::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($member === null) {
            abort(403, 'Not a member of this organization.');
        }

        $request->attributes->set('organization_member', $member);

        return $next($request);
    }
}
