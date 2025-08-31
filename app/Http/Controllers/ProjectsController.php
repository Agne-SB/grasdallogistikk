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
    public function montering(\Illuminate\Http\Request $request)
    {
        $base = $this->queryForBucket('montering', $request)
            ->whereNull('mount_completed_at')               // hide finished
            ->whereDoesntHave('deviations', fn($q) => $q->where('status','open')); // hide open avvik

        $waiting = (clone $base)
            ->whereNull('delivered_at')
            ->paginate(25, ['*'], 'waiting_page')
            ->withQueryString();

        $preparing = (clone $base)
            ->whereNotNull('delivered_at')->whereNull('ready_at')
            ->paginate(25, ['*'], 'preparing_page')
            ->withQueryString();

        $ready = (clone $base)
            ->whereNotNull('ready_at')
            ->paginate(25, ['*'], 'ready_page')
            ->withQueryString();

        return view('montering.index', compact('waiting','preparing','ready'));
    }

    //Henting(HO)
    public function henting(\Illuminate\Http\Request $request)
    {
        $base = $this->queryForBucket('henting', $request)
            ->whereNull('pickup_collected_at')
            ->whereDoesntHave('deviations', function ($q) {
                $q->where('status', 'open');
            });

        $waiting = (clone $base)
            ->whereNull('delivered_at')
            ->paginate(25, ['*'], 'waiting_page')
            ->withQueryString();

        $preparing = (clone $base)
            ->whereNotNull('delivered_at')
            ->whereNull('ready_at')
            ->paginate(25, ['*'], 'preparing_page')
            ->withQueryString();

        $ready = (clone $base)
            ->whereNotNull('ready_at')
            ->paginate(25, ['*'], 'ready_page')
            ->withQueryString();

        return view('henting.index', compact('waiting','preparing','ready'));
    }

    // Mark montering "in assignment" (freeze row)
    public function markMountStart(\App\Models\Project $project)
    {
        if (is_null($project->ready_at)) {
            return back()->withErrors(['ready_at' => 'Kan ikke sette i oppdrag før klargjøring.']);
        }
        $project->update(['mount_started_at' => now()]);
        return back()->with('status', 'Satt i oppdrag.');
    }

    // Mark montering completed (remove from list)
    public function markMountDone(\App\Models\Project $project)
    {
        $project->update(['mount_completed_at' => now()]);
        return back()->with('status', 'Montering utført.');
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

    // Planing
    public function planlegging(\Illuminate\Http\Request $request)
    {
        // origin (GG) — set these in .env or config if you know them
        $originLat = (float) config('services.map.origin_lat', 0);
        $originLng = (float) config('services.map.origin_lng', 0);

        $tz    = config('app.timezone', 'Europe/Oslo');
        $today = \Illuminate\Support\Carbon::now($tz)->startOfDay();
        $in7   = (clone $today)->addDays(7)->endOfDay();
        $in14  = (clone $today)->addDays(14)->endOfDay();
        $after14Start = (clone $today)->addDays(15)->startOfDay();

        // Base: only montering, not completed, not “i oppdrag”
        $base = $this->queryForBucket('montering', $request)
            ->whereNull('mount_completed_at')
            ->whereNull('mount_started_at'); // exclude "I oppdrag" from map & tables

        // 1) Already delivered to us
        $levert = (clone $base)
            ->whereNotNull('delivered_at')
            ->orderByDesc('delivered_at')
            ->get();

        // 2) Delivering within 7 days (upcoming)
        $leveres7 = (clone $base)
            ->whereNull('delivered_at')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [$today->toDateString(), $in7->toDateString()])
            ->orderBy('delivery_date')
            ->get();

        // 3) Delivering in 8–14 days
        $leveres14 = (clone $base)
            ->whereNull('delivered_at')
            ->whereNotNull('delivery_date')
            ->whereBetween('delivery_date', [ $today->copy()->addDays(8)->toDateString(), $in14->toDateString() ])
            ->orderBy('delivery_date')
            ->get();

        // 4) Delivering >14 days
        $leveres15plus = (clone $base)
            ->whereNull('delivered_at')
            ->whereNotNull('delivery_date')
            ->where('delivery_date', '>=', $after14Start->toDateString())
            ->orderBy('delivery_date')
            ->get();


        //Km in tables
        $originLat = (float) config('services.map.origin_lat', 0);
        $originLng = (float) config('services.map.origin_lng', 0);

        // annotate each table row with distance_km (and optionally avvik flag)
        foreach ([$levert, $leveres7, $leveres14, $leveres15plus] as $set) {
            $set->each(function ($p) use ($originLat, $originLng) {
                $hasOrigin = ($originLat !== 0.0 && $originLng !== 0.0);
                $hasCoords = ($p->geo_lat !== null && $p->geo_lng !== null);

                $p->distance_km = ($hasOrigin && $hasCoords)
                    ? round($this->haversineKm((float)$originLat, (float)$originLng, (float)$p->geo_lat, (float)$p->geo_lng), 1)
                    : null;

                $p->has_open_avvik = $p->deviations()->where('status','open')->exists();
            });
        }

        // Map icons
        $originLat = (float) config('services.map.origin_lat', 0);
        $originLng = (float) config('services.map.origin_lng', 0);

        $tz    = config('app.timezone', 'Europe/Oslo');
        $today = \Illuminate\Support\Carbon::now($tz)->startOfDay();
        $in7   = (clone $today)->addDays(7)->endOfDay();
        $in14  = (clone $today)->addDays(14)->endOfDay();

        // Build map items from one combined query (only rows with coords)
        $forMap = \App\Models\Project::query()
            ->where('bucket', 'montering')
            ->whereNull('mount_completed_at')
            ->whereNull('mount_started_at') // exclude "I oppdrag"
            ->whereNotNull('geo_lat')->whereNotNull('geo_lng')
            ->get();

        $mapItems = $forMap->map(function ($p) use ($today, $in7, $in14) {
            $hasOpenAvvik = $p->deviations()->where('status','open')->exists();

            // color by same rules as the tables
            $color = 'gray';
            if ($p->delivered_at) {
                $color = 'green';
            } elseif ($p->delivery_date) {
                if ($p->delivery_date->between($today, $in7)) {
                    $color = 'orange';
                } elseif ($p->delivery_date->gt($in14)) {
                    $color = 'gray';
                } else {
                    // 8–14 dager
                    $color = 'gray';
                }
            }
            if ($hasOpenAvvik) {
                $color = 'orange-strong';
            }

            return [
                'lat'   => (float) $p->geo_lat,
                'lng'   => (float) $p->geo_lng,
                'label' => trim(($p->external_number ?? '–') . ($p->mann ? ' • '.$p->mann : '')),
                'color' => $color,
            ];
        })->values()->all();

        return view('planlegging.index', compact(
            'levert','leveres7','leveres14','leveres15plus',
            'mapItems','originLat','originLng'
        ));


        // annotate rows: distance and has open avvik
        foreach ([$levert, $leveres7, $leveres14, $leveres15plus] as $set) {
            $set->each(function ($p) use ($originLat, $originLng) {
                $p->has_open_avvik = $p->deviations()->where('status','open')->exists();
                $p->distance_km    = ($p->geo_lat && $p->geo_lng && $originLat && $originLng)
                                    ? $this->haversineKm($originLat, $originLng, $p->geo_lat, $p->geo_lng)
                                    : null;
            });
        }

    }

    // --- helpers ---
    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
             + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) ** 2;
        return round($R * 2 * atan2(sqrt($a), sqrt(1-$a)), 1);
    }

    private function mapPoint($p, string $color): ?array
    {
        if (!$p->geo_lat || !$p->geo_lng) return null;
        // open avvik: force strong orange
        $markerColor = $p->has_open_avvik ? 'orange-strong' : $color;
        $label = trim(($p->external_number ?? '–')
            . ($p->mann ? (' • ' . $p->mann) : ''));

        return [
            'lat'   => $p->geo_lat,
            'lng'   => $p->geo_lng,
            'label' => $label,
            'color' => $markerColor,
        ];
    }


}