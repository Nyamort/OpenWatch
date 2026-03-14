<?php

namespace App\Actions\Projects;

use App\Models\Project;

class ArchiveProject
{
    /**
     * Archive the given project.
     */
    public function handle(Project $project): void
    {
        $project->update(['archived_at' => now()]);
    }
}
