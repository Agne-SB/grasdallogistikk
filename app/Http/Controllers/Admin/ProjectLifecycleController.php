<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectLifecycleController extends Controller
{
    
    public function complete(Request $request, Project $project)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['utført', 'utlevert'])],
        ]);

        if ($project->vendor_status === 'closed') {
            return back()->with('ok', "Prosjekt {$project->external_number} er allerede lukket.");
        }

        $project->vendor_status    = 'closed';
        $project->vendor_closed_at = $project->vendor_closed_at ?? now();
        $project->completion_type = $data['type'];

        $project->save();

        return back()->with('ok', "Prosjekt {$project->external_number} satt til «{$data['type']}» og lukket.");
    }
}
