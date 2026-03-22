<?php

namespace App\Http\Controllers\Project;

use App\Actions\Project\SwitchProject;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectSwitcherController extends Controller
{
    public function store(Request $request, SwitchProject $action): RedirectResponse
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
        ]);

        $project = Project::findOrFail($data['project_id']);

        $action->handle($request->user(), $project);

        return redirect()->back();
    }
}
