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

        // $query->where('project_id', session('project_id'));

        return Inertia::render('Trades/Index', [
            'trade' => Trade::first(),
            'projects' => Project::all(),
            'projchange' => Project::where('id', session('project_id'))->first(),
            'balances' => $query->where('project_id', session('project_id'))
                ->paginate(1)
                ->through(
                    fn ($trade) =>
                    [
                        'id' => $trade->id,
                        'name' => $trade->name,
                        'start' => $trade->start,
                        'end' => $trade->end,
                        'revenue' => $trade->revenue,
                        'cost' => $trade->cost,
                        'actual' => $trade->actual == 0 ? 'Estimated' : 'Actual',
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
        if (session('project_id')) {
            return Inertia::render('Trades/Create', [
                'projects' => Project::all(),
                'projchange' => Project::where('id', session('project_id'))->first(),
            ]);
        } else {
            return redirect()->route('projects')->with('warning', 'Create Project First');
        }
    }

    public function store(Req $request)
    {
        Request::validate([
            'name' => ['required', 'max:255'],
            'start' => ['required'],
            'end' => ['required'],
            // 'project_id' => ['required'],
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
                // 'project_id' => Request::input('project_id')['id'],
                'project_id' => session('project_id'),
            ]);
            $trade->parent_id = $trade->id;
            $trade->save();

            //To calculate the month between start and end date of trade
            $start = Carbon::createFromFormat('Y-m-d', $request->start);
            $proj_end = Carbon::parse($request->end)->endOfMonth()->format('Y-m-d');
            // $end = Carbon::createFromFormat('Y-m-d', $request->end);
            $end = Carbon::createFromFormat('Y-m-d', $proj_end);
            $diff_in_months = $start->diffInMonths($end);

            // dd($diff_in_months);

            //According to month ...if months = 0 or months is > 0
            if ($diff_in_months > 0) {
                $revenue = round($request->revenue / $diff_in_months, 2);
                $cost = round($request->cost / $diff_in_months, 2);
            } else {
                $revenue =  $request->revenue;
                $cost =  $request->cost;
            }

            //For loop length according to trade months
            for ($i = 0; $i <= $diff_in_months; $i++) {
                //To format the start date
                $start = Carbon::parse($start)->format('Y-m-d');

                //To save the last item date according to the trade end date
                // dd($diff_in_months);
                if ($i == $diff_in_months) {
                    $lastDayofMonth = Carbon::parse($request->end)->format('Y-m-d');
                } else {
                    $lastDayofMonth = Carbon::parse($start)->endOfMonth()->format('Y-m-d');
                }

                //According to the value we are getting from user ....its a revenue or cost
                if ($request->revenue == null && $request->revenue == '') {
                    $item[$i] = Item::create([

                        'start' => $start,
                        'end' => $lastDayofMonth,
                        'cost' => $cost,
                        'actual' => $request->actual,
                        'trade_id' => $trade->id,
                        'parent_id' => $trade->id,
                        'project_id' => session('project_id'),
                    ]);
                } else {
                    $item[$i] = Item::create([
                        'start' => $start,
                        'end' => $lastDayofMonth,
                        'revenue' => $revenue,
                        'actual' => $request->actual,
                        'trade_id' => $trade->id,
                        'parent_id' => $trade->id,
                        'project_id' => session('project_id'),
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
            'project' => Project::where('id', $trade->project_id)->first(),
        ]);
    }

    public function update(Req $request, Trade $trade)
    {
        Request::validate([
            'name' => ['required', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $trade) {

            //Getting requested and previous amount
            $pre_amount = $trade->revenue > 0 ? $trade->revenue : $trade->cost;
            $amount = $request->revenue > 0 ? $request->revenue : $request->cost;
            //Getting requested and previous amount
            $pre_amount_type = $trade->revenue > 0 ? 'revenue' : 'cost';
            $amount_type = $request->revenue > 0 ? 'revenue' : 'cost';

            //To calculate the month between start and end date of trade
            $pre_start = Carbon::createFromFormat('Y-m-d', $trade->start);
            $pre_end = Carbon::createFromFormat('Y-m-d', $trade->end);
            $pre_diff_in_months = $pre_start->diffInMonths($pre_end);

            //To calculate the month between start and end date of trade
            $start = Carbon::createFromFormat('Y-m-d', $request->start);
            $end = Carbon::createFromFormat('Y-m-d', $request->end);
            $diff_in_months = $start->diffInMonths($end);

            /* Checking that there is a difference between the previous number of months and the requested number of months
              or in previous amount and requested amount (revenue or cost) */
            if ($pre_diff_in_months != $diff_in_months || $pre_amount != $amount || $pre_amount_type != $amount_type) {
                //Deleting previous items because of the difference in date and amount
                $items = Item::where('trade_id', $trade->id)->get();
                foreach ($items as $item) {
                    $item->delete();
                }

                //According to month ...if months = 0 or months is > 0
                if ($diff_in_months > 0) {
                    $revenue = round($request->revenue / $diff_in_months, 2);
                    $cost = round($request->cost / $diff_in_months, 2);
                } else {
                    $revenue =  $request->revenue;
                    $cost =  $request->cost;
                }

                //Creating items by for loop according to the length of months of trade
                for ($i = 0; $i <= $diff_in_months; $i++) {
                    //To format the start date
                    $start = Carbon::parse($start)->format('Y-m-d');

                    //To save the last item date according to the trade end date
                    if ($i == $diff_in_months) {
                        $lastDayofMonth = Carbon::parse($request->end)->format('Y-m-d');
                    } else {
                        $lastDayofMonth = Carbon::parse($start)->endOfMonth()->format('Y-m-d');
                    }
                    //According to the value we are getting from user ....its a revenue or cost
                    if ($request->revenue == null && $request->revenue == '') {
                        $item[$i] = Item::create([

                            'start' => $start,
                            'end' => $lastDayofMonth,
                            'cost' => $cost,
                            'actual' => $request->actual,
                            'trade_id' => $trade->id,
                        ]);
                    } else {
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

                //Updating trade in database
                $trade->name = strtoupper($request->name);
                $trade->start = $request->start;
                $trade->end = $request->end;
                $trade->revenue = $request->revenue;
                $trade->cost = $request->cost;
                // $trade->actual = $request->actual;
                $trade->project_id = session('project_id');
                $trade->save();
            }
            // elseif($pre_amount_type != $amount_type)
            // {
            //     //Updating previous items because of the change in amount type (revenue / cost)
            //     $items = Item::where('trade_id', $trade->id)->get();
            //     foreach($items as $item)
            //     {
            //         $revenORcost = $
            //         $item->revenue = ;
            //         $item->delete();
            //     }
            // }
            else {
                //Updating trade in database
                $trade->name = strtoupper(Request::input('name'));
                $trade->project_id = session('project_id');
                $trade->save();
            }
        });
        return Redirect::route('trades')->with('success', 'Trade updated.');
    }

    public function destroy(Trade $trade)
    {
        $trade->delete();
        return Redirect::back()->with('success', 'Trade deleted.');
    }
}
