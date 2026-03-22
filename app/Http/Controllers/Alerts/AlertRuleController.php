<?php

namespace App\Http\Controllers\Alerts;

use App\Actions\Alerts\CreateAlertRule;
use App\Actions\Alerts\DeleteAlertRule;
use App\Actions\Alerts\ToggleAlertRule;
use App\Actions\Alerts\UpdateAlertRule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alerts\CreateAlertRuleRequest;
use App\Http\Requests\Alerts\DeleteAlertRuleRequest;
use App\Http\Requests\Alerts\UpdateAlertRuleRequest;
use App\Models\AlertRule;
use App\Models\Environment;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AlertRuleController extends Controller
{
    /**
     * Display a listing of alert rules for the environment.
     */
    public function index(Organization $organization, Project $project, Environment $environment): Response
    {
        $this->authorize('view', $organization);

        $rules = AlertRule::query()
            ->where('organization_id', $organization->id)
            ->where('project_id', $project->id)
            ->where('environment_id', $environment->id)
            ->with(['recipients.user'])
            ->latest()
            ->get();

        return Inertia::render('alerts/index', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
            'alertRules' => $rules,
        ]);
    }

    /**
     * Show the form for creating a new alert rule.
     */
    public function create(Organization $organization, Project $project, Environment $environment): Response
    {
        $this->authorize('create', new AlertRule(['organization_id' => $organization->id]));

        $members = $organization->members()->with('user', 'role')->get();

        return Inertia::render('alerts/create', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
            'members' => $members,
        ]);
    }

    /**
     * Store a newly created alert rule.
     */
    public function store(
        CreateAlertRuleRequest $request,
        Organization $organization,
        Project $project,
        Environment $environment,
        CreateAlertRule $action,
    ): RedirectResponse {
        $this->authorize('create', new AlertRule(['organization_id' => $organization->id]));

        $action->handle($organization, $project, $environment, $request->validated());

        return to_route('organizations.alert-rules.index', [$organization, $project, $environment]);
    }

    /**
     * Show the form for editing an existing alert rule.
     */
    public function edit(Organization $organization, Project $project, Environment $environment, AlertRule $alertRule): Response
    {
        $this->authorize('update', $alertRule);

        $members = $organization->members()->with('user', 'role')->get();

        return Inertia::render('alerts/edit', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
            'alertRule' => $alertRule->load('recipients.user'),
            'members' => $members,
        ]);
    }

    /**
     * Update the specified alert rule.
     */
    public function update(
        UpdateAlertRuleRequest $request,
        Organization $organization,
        Project $project,
        Environment $environment,
        AlertRule $alertRule,
        UpdateAlertRule $action,
    ): RedirectResponse {
        $this->authorize('update', $alertRule);

        $action->handle($organization, $alertRule, $request->validated());

        return to_route('organizations.alert-rules.index', [$organization, $project, $environment]);
    }

    /**
     * Remove the specified alert rule.
     */
    public function destroy(
        DeleteAlertRuleRequest $request,
        Organization $organization,
        Project $project,
        Environment $environment,
        AlertRule $alertRule,
        DeleteAlertRule $action,
    ): RedirectResponse {
        $this->authorize('delete', $alertRule);

        $action->handle($alertRule, $request->validated()['confirmation']);

        return to_route('organizations.alert-rules.index', [$organization, $project, $environment]);
    }

    /**
     * Toggle the enabled state of the specified alert rule.
     */
    public function toggle(
        Organization $organization,
        Project $project,
        Environment $environment,
        AlertRule $alertRule,
        ToggleAlertRule $action,
    ): RedirectResponse {
        $this->authorize('update', $alertRule);

        $action->handle($alertRule);

        return to_route('organizations.alert-rules.index', [$organization, $project, $environment]);
    }
}
