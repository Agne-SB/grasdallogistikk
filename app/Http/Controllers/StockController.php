<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $q = trim((string) $request->get('q',''));

        $items = \App\Models\StockItem::query()
            ->when(! $request->boolean('include_delivered'), fn($q) => $q->whereNull('delivered_at'))
            ->when($q, fn($qq) => $qq->where(function($w) use ($q){
                $w->where('title','like',"%{$q}%")
                    ->orWhere('supplier','like',"%{$q}%");
            }))
            ->orderByRaw('COALESCE(delivery_date, created_at) ASC')
            ->paginate(25)
            ->withQueryString();

        return view('varer.index', compact('items','q'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => ['required','string','max:255'],
            'supplier'      => ['required','string','max:255'],
            'delivery_date' => ['nullable','date'],
            'delivery_time' => ['nullable','string','max:50'],
        ]);

        StockItem::create($data + ['status' => 'bestilt']);

        return redirect()->route('varer.index')->with('ok','Vare lagt til.');
    }

    public function updateStatus(\Illuminate\Http\Request $request, \App\Models\StockItem $item)
    {
        $data = $request->validate([
            'status'     => ['required', \Illuminate\Validation\Rule::in(['bestilt','i_behandling','levert','avvik'])],
            'issue_note' => ['nullable','string'],
        ]);

        if ($data['status'] === 'levert') {
            $item->delivered_at = $item->delivered_at ?? now();
        } else {
            $item->delivered_at = null;
        }

        $item->status = $data['status'];
        if (array_key_exists('issue_note', $data)) {
            $item->issue_note = $data['issue_note'];
        }
        $item->save();

        return back()->with('status','Oppdatert.');
    }

}
