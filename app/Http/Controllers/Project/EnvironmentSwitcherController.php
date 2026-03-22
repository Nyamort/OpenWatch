<?php

namespace App\Http\Controllers\Project;

use App\Actions\Project\SwitchEnvironment;
use App\Http\Controllers\Controller;
use App\Models\Environment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnvironmentSwitcherController extends Controller
{
    public function store(Request $request, SwitchEnvironment $action): RedirectResponse
    {
        $data = $request->validate([
            'environment_id' => ['required', 'integer', 'exists:environments,id'],
        ]);

        $environment = Environment::findOrFail($data['environment_id']);

        $action->handle($request->user(), $environment);

        return redirect()->back();
    }
}
