<?php

namespace App\Http\Middleware;

use App\Models\Environment;
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

        if ($resolved !== null) {
            $organization = $resolved instanceof Organization
                ? $resolved
                : Organization::where('slug', $resolved)->firstOrFail();
        } else {
            $env = $request->route('environment');
            $environment = $env instanceof Environment
                ? $env
                : Environment::with('project.organization')->where('slug', $env)->firstOrFail();
            $organization = $environment->project->organization;
        }

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
