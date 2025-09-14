<?php

namespace App\Http\Controllers;

use App\Models\Deviation;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviationController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $open = \App\Models\Deviation::query()
            ->with([
                'project:id,title,external_number',
                'stockItem:id,title,supplier',
            ])
            ->where(function ($q) {
                $q->where('status','open')->orWhereNull('status');
            })
            ->orderByRaw('COALESCE(opened_at, created_at) DESC')
            ->paginate(50)
            ->withQueryString();

        return view('avvik.index', compact('open'));
    }


    public function store(\Illuminate\Http\Request $request)
    {
        $data = $request->validate([
            'project_id'    => ['nullable','required_without:stock_item_id','integer','exists:projects,id','prohibits:stock_item_id'],
            'stock_item_id' => ['nullable','required_without:project_id','integer','exists:stock_items,id','prohibits:project_id'],
            'source'        => ['required','string', Rule::in(['henting','montering','varer'])],
            'type'          => ['required','string','max:255'],
            'note'          => ['required','string'],
            'qty_expected'  => ['nullable','integer','min:0'],
            'qty_received'  => ['nullable','integer','min:0'],
        ]);

        $dev = new Deviation();
        $dev->project_id    = $data['project_id']    ?? null;
        $dev->stock_item_id = $data['stock_item_id'] ?? null;
        $dev->source        = $data['source'];
        $dev->type          = $data['type'];
        $dev->note          = $data['note'];
        $dev->qty_expected  = $data['qty_expected'] ?? null;
        $dev->qty_received  = $data['qty_received'] ?? null;
        $dev->status        = 'open';
        $dev->opened_at     = now();
        $dev->save();

        return back()->with('status','Avvik registrert.');
    }



    public function resolve(Deviation $deviation)
    {
        $deviation->update(['status'=>'resolved','resolved_at'=>now()]);
        return back()->with('status','Avvik markert som løst.');
    }


    public function resolveRoute(\Illuminate\Http\Request $request, \App\Models\Deviation $deviation)
    {
        // Is this deviation tied to a StockItem (Varer)?
        $isVarer = ($deviation->stock_item_id !== null)
            || (strtolower((string) $deviation->source) === 'varer');

        // Validation: date + destination are only required for HO/MO (not Varer)
        $data = $request->validate([
            'goods_note'      => ['nullable','string'],
            'delivery_date'   => [$isVarer ? 'nullable' : 'required','date'],
            'destination'     => ['nullable', Rule::in(['henting','montering','varer'])],
            'resolution_note' => ['nullable','string'],
        ]);

        // --- VARER (StockItem) path ---
        if ($isVarer) {
            // Mark deviation resolved
            $deviation->status      = 'resolved';
            $deviation->resolved_at = now();
            if (isset($data['resolution_note']) && $data['resolution_note'] !== null) {
                $deviation->resolution_note = $data['resolution_note'];
            }
            if (auth()->check()) {
                $deviation->resolved_by = auth()->user()->name ?? 'bruker';
            }
            $deviation->save();

            // Send back to Varer til lager
            return redirect()->route('varer.index')->with('status', 'Avvik løst.');
        }

        // --- PROJECT (Henting/Montering) path ---
        $project = $deviation->project;
        if (! $project) {
            // Safety net: if there is no project, just resolve + return to Avvik
            $deviation->update([
                'status'      => 'resolved',
                'resolved_at' => now(),
            ]);
            return redirect()->route('avvik.index')->with('status','Avvik løst.');
        }

        // Decide where to send the project (default to its source if missing)
        $dest = $data['destination']
            ?? (in_array(strtolower((string)$deviation->source), ['henting','montering'], true)
                    ? strtolower((string)$deviation->source)
                    : 'henting');

        // Reset pipeline → “Venter på levering” in selected bucket
        $project->fill([
            'goods_note'           => $data['goods_note'] ?? $project->goods_note,
            'delivery_date'        => $data['delivery_date'],     
            'bucket'               => $dest,                      
            'delivered_at'         => null,
            'staged_location'      => null,
            'ready_at'             => null,
            'notified_at'          => null,
            'pickup_time_from'     => null,
            'pickup_time_to'       => null,
            'pickup_collected_at'  => null,
            'mount_started_at'     => null,
            'mount_completed_at'   => null,
        ])->save();

        // Close the deviation
        $deviation->status      = 'resolved';
        $deviation->resolved_at = now();
        if (isset($data['resolution_note']) && $data['resolution_note'] !== null) {
            $deviation->resolution_note = $data['resolution_note'];
        }
        if (auth()->check()) {
            $deviation->resolved_by = auth()->user()->name ?? 'bruker';
        }
        $deviation->save();

        // Redirect to chosen destination page
        return redirect(
            $dest === 'montering' ? route('montering.index') : route('henting.index')
        )->with('status', 'Avvik løst. Saken er flyttet til '.strtoupper($dest).' → Venter på levering.');
    }


}

