<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ClosedProjectController extends Controller
{
    // Accept both casings when checking, but WRITE canonical lowercase values
    private const CLOSED_VALUES = ['closed','Closed'];
    private const OPEN_VALUES   = ['new','New'];
    private const CLOSED        = 'closed';
    private const OPEN          = 'new';

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $projects = Project::query()
            ->whereIn('vendor_status', self::CLOSED_VALUES)
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('external_number', 'like', "%{$q}%")
                        ->orWhere('title', 'like', "%{$q}%")
                        ->orWhere('supervisor_name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('vendor_closed_at')
            ->orderBy('external_number')
            ->paginate(20)
            ->withQueryString();

        return view('admin.closed.index', compact('projects', 'q'));
    }

    public function reopen(Project $project)
    {
        // must be closed to reopen (was inverted before)
        if (! in_array($project->vendor_status, self::CLOSED_VALUES, true)) {
            return back()->with('err', 'Prosjektet er ikke lukket.');
        }

        // flip to open + clear closed timestamp
        $project->vendor_status    = self::OPEN;        // 'new'
        $project->vendor_closed_at = null;

        // reset to behave like a brand new project (guard each field)
        $cols  = Schema::getColumnListing($project->getTable());
        $reset = [
            'goods_note'    => null,
            'delivery_date' => null,
            'delivery_time' => null,
            'bucket'        => 'prosjekter',
        ];
        foreach ($reset as $column => $value) {
            if (in_array($column, $cols, true)) {
                $project->{$column} = $value;
            }
        }

        $project->save();

        // send to your main projects page (fix double /admin)
        return redirect('/admin/projects')->with('ok', "Prosjekt {$project->external_number} er gjenåpnet som nytt.");
    }

    public function destroy(Project $project)
    {
        // can only delete CLOSED items (was inverted before)
        if (! in_array($project->vendor_status, self::CLOSED_VALUES, true)) {
            return back()->with('err', 'Kan bare slette lukkede prosjekter.');
        }

        $project->delete(); // hard delete
        return back()->with('ok', "Slettet prosjekt {$project->external_number}.");
    }

    public function close(Project $project)
    {
        // already closed? just acknowledge
        if (in_array($project->vendor_status, self::CLOSED_VALUES, true)) {
            return back()->with('ok', "Prosjekt {$project->external_number} er allerede lukket.");
        }

        // close properly (you were assigning the ARRAY before)
        $project->vendor_status    = self::CLOSED;      // 'closed'
        $project->vendor_closed_at = $project->vendor_closed_at ?? now();
        $project->save();

        return back()->with('ok', "Prosjekt {$project->external_number} er lukket.");
    }
}
