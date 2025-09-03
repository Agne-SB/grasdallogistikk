<?php

namespace App\Http\Controllers;

use App\Models\Deviation;
use App\Models\Project;
use Illuminate\Http\Request;

class DeviationController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $open = \App\Models\Deviation::with('project')
            ->where(function ($q) {
                $q->where('status', 'open')
                ->orWhereNull('status');
            })
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('avvik.index', compact('open'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'project_id'   => ['required','exists:projects,id'],
            'source'       => ['required','in:henting,montering'],
            'type'         => ['required','string'],
            'note'         => ['required','string'],
            'qty_expected' => ['nullable','integer'],
            'qty_received' => ['nullable','integer'],
        ]);

        $dev = \App\Models\Deviation::create($data + [
            'project_id' => $data['project_id'],
            'source'     => $data['source'],
            'type'       => $data['type'],
            'note'       => $data['note'],
            'status'    => 'open',
            'opened_at' => now(),
        ]);

        return redirect()
            ->route('avvik.index', ['new' => $dev->id])
            ->with('status', 'Avvik registrert.');
    }

    public function resolve(Deviation $deviation)
    {
        $deviation->update(['status'=>'resolved','resolved_at'=>now()]);
        return back()->with('status','Avvik markert som løst.');
    }

    // app/Http/Controllers/DeviationController.php

    public function resolveRoute(\Illuminate\Http\Request $request, \App\Models\Deviation $deviation)
    {
        $data = $request->validate([
            'goods_note'    => ['nullable','string'],
            'delivery_date' => ['required','date'],
            'destination'   => ['required', \Illuminate\Validation\Rule::in(['henting','montering'])],
            'resolution_note' => ['nullable','string'],
        ]);

        $project = $deviation->project;

        // Reset pipeline → “Venter på levering” in selected bucket
        $project->fill([
            'goods_note'         => $data['goods_note'] ?? $project->goods_note,
            'delivery_date'      => $data['delivery_date'],
            'bucket'             => $data['destination'], // 'henting' or 'montering'
            'delivered_at'       => null,
            'staged_location'    => null,
            'ready_at'           => null,
            'notified_at'        => null,
            'pickup_time_from'   => null,
            'pickup_time_to'     => null,
            'pickup_collected_at'=> null,
            'mount_started_at'   => null,
            'mount_completed_at' => null,
        ])->save();

        // Close the deviation
        $deviation->update([
            'status'          => 'resolved',
            'resolved_at'     => now(),
            'resolution_note' => $data['resolution_note'] ?? null,
            'resolved_by'     => auth()->check() ? (auth()->user()->name ?? 'bruker') : 'system',
        ]);

        return redirect()
            ->route('avvik.index')
            ->with('status', 'Avvik løst. Saken er flyttet til '.strtoupper($data['destination']).' → Venter på levering.');
    }

}

