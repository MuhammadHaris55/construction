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
            $query->where('start', 'LIKE', '%' . request('search') . '%');
        }

        // if (request('searche')) {
        //     $query->where('start', 'LIKE', '%' . request('searche') . '%');
        // }

        if (request()->has(['field', 'direction'])) {
            $query->orderBy(request('field'), request('direction'));
        }

        return Inertia::render('Items/Index', [
            'filters' => request()->all(['search', 'searche', 'field', 'direction']),
            'projchange' => Project::where('id', session('project_id'))->get(),
            'balance' => $query->where('project_id', session('project_id'))->with('trade')->get(),
            'balances' => $query->where('project_id', session('project_id'))
            ->paginate()
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
            $query->where('start', 'LIKE', '%' . request('search') . '%');
        }

        // if (request('searche')) {
        //     $query->where('start', 'LIKE', '%' . request('searche') . '%');
        // }

        if (request()->has(['field', 'direction'])) {
            $query->orderBy(request('field'), request('direction'));
        }

        return Inertia::render('Items/ActualIndex', [
            'filters' => request()->all(['search', 'searche', 'field', 'direction']),
            'projchange' => Project::where('id', session('project_id'))->get(),
            'balance' => $query->where('project_id', session('project_id'))->with('trade')->get(),
            'balances' => $query->where('project_id', session('project_id'))->paginate()
                ->through(function ($item) {
                    return [
                        'id' => $item->id,
                        'start' => $item->start,
                        'end' => $item->end,
                        'revenue' => $item->revenue,
                        'cost' => $item->cost,
                        'actual' => $item->actual == 0 ? 'Estimate' : 'Actual',
                        'project_id' => $item->project_id,
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

        if($request->revenue > 0){
            $pre_item = Item::where('start', $request->start)
                ->where('end', $request->end)
                ->where('trade_id', $request->trade_id)
                ->where('actual', $request->actual)
                ->where('cost', $request->cost)
                ->first();
        }else {
            $pre_item = Item::where('start', $request->start)
                ->where('end', $request->end)
                ->where('trade_id', $request->trade_id)
                ->where('actual', $request->actual)
                ->where('revenue', $request->revenue)
                ->first();
        }
        
        if($pre_item)
        {
            return Redirect::route('actual_items')->with('warning', 'Item already exist.');
        } else {
            Item::create([
                'start' => $request->start,
                'end' => $request->end,
                'revenue' => $request->revenue,
                'cost' => $request->cost,
                'actual' => $request->actual,
                'trade_id' => $request->trade_id,
                'project_id' => session('project_id'),
            ]);
            return Redirect::route('actual_items')->with('success', 'Item created.');
        }
    }

    public function edit(Item $item)
    {
        return Inertia::render('Items/Edit', [
            'item' => [
                'id' => $item->id,
                'start' => $item->start,
                'end' => $item->end,
                'revenue' => intval($item->revenue),
                'cost' => intval($item->cost),
                'trade_id' => $item->trade_id,
                'trade_name' => $item->trade->name,
            ],
        ]);
    }

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

    public function destroy(Item $item)
    {
        $item->delete();
        return Redirect::back()->with('success', 'Item deleted.');
    }
}
