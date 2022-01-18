<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use App\Models\Item;
use App\Models\Project;
use Illuminate\Http\Request as Req;

class ItemController extends Controller
{
    
    public function index()
    {
        //Validating request
        request()->validate([
            'direction' => ['in:asc,desc'],
            'field' => ['in:name']
        ]);

        //Searching request
        $query = Item::query();

        $query->where('actual', 0);

        if (request('search')) {
            $query->where('id', 'LIKE', '%' . request('search') . '%');
        }

        if (request('searche')) {
            $query->where('start', 'LIKE', '%' . request('searche') . '%');
        }

        if (request()->has(['field', 'direction'])) {
            $query->orderBy(request('field'), request('direction'));
        } else {
            $query->orderBy(('id'), ('asc'));
        }

        return Inertia::render('Items/Index', [
            'filters' => request()->all(['search', 'searche', 'field', 'direction']),
            'balances' => $query->paginate(10)
                ->through(function ($item) {
                    return [
                        'id' => $item->id,
                        'start' => $item->start,
                        'end' => $item->end,
                        'revenue' => $item->revenue,
                        'cost' => $item->cost,
                        'actual' => $item->actual == 0 ? 'Estimate' : 'Actual',
                        'trade_id' => $item->trade->name,
                        // 'delete' => Quantity::where('item_id', $item->id)->first() ? false : true,

                    ];
                }),
            'projects' => Project::all()
                ->map(
                    function ($proj) {
                        return [
                            'id' => $proj->id,
                            'name' => $proj->name,
                        ];
                    }
                ),
        ]);
    }

    public function actual_index()
    {
        //Validating request
        request()->validate([
            'direction' => ['in:asc,desc'],
            'field' => ['in:name']
        ]);

        //Searching request
        $query = Item::query();

        $query->where('actual', 1);

        if (request('search')) {
            $query->where('id', 'LIKE', '%' . request('search') . '%');
        }

        if (request('searche')) {
            $query->where('start', 'LIKE', '%' . request('searche') . '%');
        }

        if (request()->has(['field', 'direction'])) {
            $query->orderBy(request('field'), request('direction'));
        } else {
            $query->orderBy(('id'), ('asc'));
        }


        return Inertia::render('Items/ActualIndex', [
            'filters' => request()->all(['search', 'searche', 'field', 'direction']),
            'balances' => $query->paginate(10)
                ->through(function ($item) {
                    return [
                        'id' => $item->id,
                        'start' => $item->start,
                        'end' => $item->end,
                        'revenue' => $item->revenue,
                        'cost' => $item->cost,
                        'actual' => $item->actual == 0 ? 'Estimate' : 'Actual',
                        'trade_id' => $item->trade->name,
                        // 'delete' => Quantity::where('item_id', $item->id)->first() ? false : true,

                    ];
                }),
            'projects' => Project::all()
                ->map(
                    function ($proj) {
                        return [
                            'id' => $proj->id,
                            'name' => $proj->name,
                        ];
                    }
                ),
        ]);
    }

    public function actual_create(Item $item)
    {
        return Inertia::render('Items/CreateActual', [
            'item' => [
                'id' => $item->id,
                'start' => $item->start,
                'end' => $item->end,
                'revenue' => intval($item->revenue),
                'cost' => intval($item->cost),
                'trade_id' => $item->trade_id,
                'trade_name' => $item->trade->name,
                // 'unit_id' =>$item->unit_id,
            ],
        ]);
    }

    public function store(Req $request)
    {
        Request::validate([
            'start' => ['required'],
            'end' => ['required'],
            'actual' => ['required'],
            'trade_id' => ['required'],
        ]);
        
        Item::create([
            'start' => $request->start,
            'end' => $request->end,
            'revenue' => $request->revenue,
            'cost' => $request->cost,
            'actual' => $request->actual,
            'trade_id' => $request->trade_id,
        ]);

        return Redirect::route('items')->with('success', 'Item created.');
    }

    // public function create()
    // {
    //     $unittype = \App\Models\UnitType::all()->map->only('id', 'name');

    //     if ($unittype->first()) {

    //         return Inertia::render('Items/Create', [
    //             'unittypes' => $unittype,
    //         ]);
    //     } else {
    //         return Redirect::route('unittypes.create')->with('warning', 'UNIT TYPE NOT FOUND, Please create unit type first.');
    //     }
    // }

    // public function store(Req  $request)
    // {
    //     Request::validate([

    //         'items.*.name' => ['required', 'unique:items', 'max:255'],
    //         'items.*.hscode' => ['required', 'unique:items', 'max:255'],
    //     ]);

    //     $items = $request->items;
    //     // dd($accounts);
    //     foreach ($items as $item) {
    //         // dd($acc);
    //         Item::create([
    //             'name' => $item['name'],
    //             'description' => $item['description'],
    //             'hscode' => $item['hscode'],
    //             'unit_id' => $item['unit_id'],
    //             'file_id' => null,


    //         ]);
    //     }
    //     return Redirect::route('items')->with('success', 'Item created.');
    // }

    // public function edit(Item $item)
    // {
    //     return Inertia::render('Items/Edit', [
    //         'item' => [
    //             'id' => $item->id,
    //             'name' => $item->name,
    //             'description' => $item->description,
    //             'hscode' => $item->hscode,
    //             // 'unit_id' =>$item->unit_id,

    //         ],
    //     ]);
    // }
    

    // public function update(Item $item)
    // {
    //     Request::validate([
    //         'name' => ['required', 'max:255'],
    //         'hscode' => ['required', 'max:255'],

    //     ]);

    //     $item->name = Request::input('name');
    //     $item->description = Request::input('description');
    //     $item->hscode = Request::input('hscode');
    //     $item->save();

    //     return Redirect::route('items')->with('success', 'Item updated.');
    // }

    // public function destroy(Item $item)
    // {
    //     $item->delete();
    //     return Redirect::back()->with('success', 'Item deleted.');
    // }
}
