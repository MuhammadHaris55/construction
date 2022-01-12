<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use App\Models\Trade;
use App\Models\Project;
use App\Models\Item;

class TradeController extends Controller
{
    public function index()
    {
        //Validating request
        request()->validate([
            'direction' => ['in:asc,desc'],
            'field' => ['in:name']
        ]);

        $query = Trade::query();

        //Searching request
        if (request('search')) {
            $query->where('name', 'LIKE', '%' . request('search') . '%');
        }
        //Ordering request
        if (request()->has(['field', 'direction'])) {
            $query->orderBy(
                request('field'),
                request('direction')
            );
        }

        return Inertia::render('Trades/Index', [
            'trade' => Trade::first(),
            'balances' => $query->paginate(12)
                ->through(
                    fn ($trade) =>
                    [
                        'id' => $trade->id,
                        'name' => $trade->name,
                        'start' => $trade->start,
                        'end' => $trade->end,
                        'revenue' => $trade->revenue,
                        'cost' => $trade->cost,
                        'actual' => $trade->actual,
                        'project_id' => $trade->project_id,
                        'enable' => $trade->enable,
                        'delete' => Item::where('trade_id', $trade->id)->first() ? false : true,
                    ],
                ),
            'filters' => request()->all(['search', 'field', 'direction'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Trades/Create', [
            'projects' => Project::all(),
        ]);
    }

    public function store()
    {
        Request::validate([
            'name' => ['required', 'unique:trades', 'max:255'],

        ]);
        $trade = Trade::create([
            'name' => strtoupper(Request::input('name')),
            'start' => Request::input('start'),
            'end' => Request::input('end'),
            'revenue' => Request::input('revenue'),
            'cost' => Request::input('cost'),
            'actual' => Request::input('actual'),
            'project_id' => Request::input('project_id')[0]['id'],
        ]);

        return Redirect::route('trades')->with('success', 'Trade created');
    }

    public function edit(Trade $trade)
    {
        return Inertia::render('Trades/Edit', [
            'trade' => [
                'id' => $trade->id,
                'name' => $trade->name,
                'start' => $trade->start,
                'end' => $trade->end,
                'revenue' => $trade->revenue,
                'cost' => $trade->cost,
                'actual' => $trade->actual,
                'project_id' => $trade->project_id,
                'enable' => $trade->enable,
            ],
            'projects' => Project::all(),
        ]);
    }

    public function update(Trade $trade)
    {
        Request::validate([
            'name' => ['required', 'max:255'],
        ]);

        $trade->name = strtoupper(Request::input('name'));
        $trade->start = Request::input('start');
        $trade->end = Request::input('end');
        $trade->revenue = Request::input('revenue');
        $trade->cost = Request::input('cost');
        $trade->actual = Request::input('actual');
        $trade->project_id = Request::input('project_id');
        $trade->save();

        return Redirect::route('trades')->with('success', 'Trade updated.');
    }

    public function destroy(Trade $trade)
    {
        $trade->delete();
        return Redirect::back()->with('success', 'Trade deleted.');
    }
}
