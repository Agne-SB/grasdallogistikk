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
            'note'         => ['nullable','string'],
            'qty_expected' => ['nullable','integer'],
            'qty_received' => ['nullable','integer'],
        ]);

        $dev = \App\Models\Deviation::create($data + [
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
}

