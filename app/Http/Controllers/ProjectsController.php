<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use App\Models\Deviation;

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

    public function henting(\Illuminate\Http\Request $request)
    {
        // Base: bucket=henting, not handed out, and NO open avvik
        $base = $this->queryForBucket('henting', $request)
            ->whereNull('pickup_collected_at')
            ->whereDoesntHave('deviations', function ($q) {
                $q->where('status', 'open');
            });

        // A) Waiting for delivery
        $waiting = (clone $base)
            ->whereNull('delivered_at')
            ->paginate(25, ['*'], 'waiting_page')
            ->withQueryString();

        // B) Preparing (delivered but not ready)
        $preparing = (clone $base)
            ->whereNotNull('delivered_at')
            ->whereNull('ready_at')
            ->paginate(25, ['*'], 'preparing_page')
            ->withQueryString();

        // C) Ready & scheduling (ready_at set)
        $ready = (clone $base)
            ->whereNotNull('ready_at')
            ->paginate(25, ['*'], 'ready_page')
            ->withQueryString();

        return view('henting.index', compact('waiting','preparing','ready'));
    }

    // Move a project between pages (Prosjekter/Montering/Henting)
    public function moveBucket(Request $request, Project $project)
    {
        $data = $request->validate([
            'bucket' => ['required', \Illuminate\Validation\Rule::in(['prosjekter','montering','henting'])],
        ]);

        if (in_array($data['bucket'], ['montering','henting'], true)) {
            if (!filled($project->goods_note) || is_null($project->delivery_date)) {
                return back()
                    ->withErrors(['bucket' => 'Fyll inn varenotat og leveringsdato før du flytter.'])
                    ->with('status', 'Kan ikke flytte – mangler varenotat/leveringsdato.');
            }
        }

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
            'supplier_eta'    => ['nullable','date'],
            'staged_location' => ['nullable','string'],
        ]);

        $project->fill($data)->save();

        return back()
            ->with('status', 'Lagret.')
            ->with('saved_project_id', $project->id);
    }

    // A) Waiting -> mark delivered
    public function markDelivered(\App\Models\Project $project)
    {
        $project->update(['delivered_at' => now()]);
        return back()->with('status', 'Registrert som levert.');
    }

    // B) Preparing -> mark ready (requires staged_location; and appointment if flagged)
    public function markReady(\Illuminate\Http\Request $request, \App\Models\Project $project)
    {
        // require placement
        $data = $request->validate([
            'staged_location' => ['required','string','max:255'],
        ]);

        // optional rule: windows need appointment before ready
        if ($project->requires_appointment && !$project->pickup_time_from) {
            return back()->withErrors(['pickup_time_from' => 'Avtale om henting mangler.']);
        }

        $project->fill([
            'staged_location' => $data['staged_location'],
            'ready_at'        => now(),
        ])->save();

        return back()->with('status', 'Klargjort for henting.');
    }


    // C) Schedule pickup
    public function schedulePickup(\Illuminate\Http\Request $request, \App\Models\Project $project)
    {
        $data = $request->validate([
            'pickup_date'       => ['required','date'],
            'appointment_notes' => ['nullable','string'],
        ]);

        $date = \Illuminate\Support\Carbon::parse($data['pickup_date'])->startOfDay();

        $project->pickup_time_from = $date;  
        $project->pickup_time_to   = null;   
        $project->appointment_notes = $data['appointment_notes'] ?? null;
        $project->save();

        return back()->with('status', 'Henting avtalt.');
    }

    // D) Handed out to customer
    public function markCollected(\App\Models\Project $project)
    {
        $project->update(['pickup_collected_at' => now()]);
        return back()->with('status', 'Utlevert ✓');
    }

}