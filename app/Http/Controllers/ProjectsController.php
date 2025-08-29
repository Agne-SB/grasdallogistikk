<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectsController extends Controller
{
    private const BUCKETS = ['prosjekter','montering','henting'];

    // Prosjekter = unsorted inbox
    public function index(Request $request)
    {
        $projects = $this->queryForBucket('prosjekter', $request)
            ->paginate(50)
            ->withQueryString();

        return view('projects.index', compact('projects'));
    }

    // Montering (MP)
    public function montering(Request $request)
    {
        $projects = $this->queryForBucket('montering', $request)
            ->paginate(50)
            ->withQueryString();

        return view('montering.index', compact('projects'));
    }

    public function henting(Request $request)
    {
        $projects = $this->queryForBucket('henting', $request)
            ->paginate(50)
            ->withQueryString();

        return view('henting.index', compact('projects'));
    }

    // Move a project between pages (Prosjekter/Montering/Henting)
    public function moveBucket(Request $request, Project $project)
    {
        $data = $request->validate([
            'bucket' => ['required', \Illuminate\Validation\Rule::in(['prosjekter','montering','henting'])],
        ]);

        // (Optional) block moving closed vendor items
        // if ($project->vendor_closed_at) return back()->with('status', 'Kan ikke flytte en lukket ordre.');

        $project->update(['bucket' => $data['bucket']]);

        return back()->with('status', 'Flyttet til '.$data['bucket']);
    }

    // ---- helper + filter ----
    private function queryForBucket(string $bucket, \Illuminate\Http\Request $request)
    {
        return \App\Models\Project::query()
            ->where('bucket', $bucket)

            // keep old behaviour: hide closed unless include_closed=1
            ->when(!$request->boolean('include_closed'), fn ($q) => $q->whereNull('vendor_closed_at'))

            // search: now includes OrderKey + Ansvarlig too
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('external_number', 'like', $term)   // OrderKey
                    ->orWhere('title', 'like', $term)
                    ->orWhere('supervisor_name', 'like', $term) // Ansvarlig
                    ->orWhere('address', 'like', $term)
                    ->orWhere('external_id', 'like', $term)
                    ->orWhere('customer_name','like',$term);    // keep old field too
                });
            })

            // optional: date range on "last updated"
            ->when($request->filled('from'), fn ($q) => 
                $q->whereRaw('DATE(COALESCE(vendor_updated_at, updated_at)) >= ?', [$request->input('from')])
            )
            ->when($request->filled('to'), fn ($q) => 
                $q->whereRaw('DATE(COALESCE(vendor_updated_at, updated_at)) <= ?', [$request->input('to')])
            )

            // sort newest first (vendor ts first, then local)
            ->orderByRaw('COALESCE(vendor_updated_at, updated_at) DESC')
            ->orderByDesc('id');
    }


    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'goods_note'    => ['nullable','string'],
            'delivery_date' => ['nullable','date'],
        ]);

        $project->fill($data)->save();

        return back()
            ->with('status', 'Lagret.')
            ->with('saved_project_id', $project->id);
    }

}