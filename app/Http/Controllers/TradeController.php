<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use App\Models\Trade;
use App\Models\Project;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request as Req;
use Carbon\Carbon;


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

    public function store(Req $request)
    {
        Request::validate([
            'name' => ['required', 'max:255'],
            'start' => ['required'],
            'end' => ['required'],
            'project_id' => ['required'],

        ]);
        DB::transaction(function () use ($request) {
            //Creating trade in database
            $trade = Trade::create([
                'name' => strtoupper(Request::input('name')),
                'start' => Request::input('start'),
                'end' => Request::input('end'),
                'revenue' => Request::input('revenue'),
                'cost' => Request::input('cost'),
                'actual' => Request::input('actual'),
                'project_id' => Request::input('project_id')['id'],
            ]);

            //To calculate the month between start and end date of trade
            $start = Carbon::createFromFormat('Y-m-d', $request->start);
            $end = Carbon::createFromFormat('Y-m-d', $request->end);
            $diff_in_months = $start->diffInMonths($end);
          
            //According to month ...if months = 0 or months is > 0
            if($diff_in_months > 0)
            {
               $revenue = round($request->revenue / $diff_in_months, 2);
               $cost = round($request->cost / $diff_in_months, 2);
            }else {
               $revenue =  $request->revenue;
               $cost =  $request->cost;
            }

            //For loop length according to trade months
            for($i = 0; $i <= $diff_in_months; $i++)
            {
                //To format the start date
                $start = Carbon::parse($start)->format('Y-m-d');
                
                //To save the last item date according to the trade end date
                if($i == $diff_in_months)
                {
                    $lastDayofMonth = Carbon::parse($request->end)->format('Y-m-d');
                }else {
                    $lastDayofMonth = Carbon::parse($start)->endOfMonth()->format('Y-m-d');
                }
               
                //According to the value we are getting from user ....its a revenue or cost
                if($request->revenue == null && $request->revenue == ''){
                    $item[$i] = Item::create([
                        'start' => $start,
                        'end' => $lastDayofMonth,
                        'cost' => $cost,
                        'actual' => $request->actual,
                        'trade_id' => $trade->id,
                    ]);
                }else{
                    $item[$i] = Item::create([
                        'start' => $start,
                        'end' => $lastDayofMonth,
                        'revenue' => $revenue,
                        'actual' => $request->actual,
                        'trade_id' => $trade->id,
                    ]);
                }
                //To get the next month by previous month last day
                $start = Carbon::parse($lastDayofMonth)->addDays(1)->toDateString();
            }
            
        });
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
