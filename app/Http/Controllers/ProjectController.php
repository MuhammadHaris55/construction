<?php

namespace App\Http\Controllers;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Inertia\Inertia;
use Illuminate\Http\Request as Req;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use \Carbon\Carbon;

use App\Models\Project;
use App\Models\Trade;
use App\Models\Item;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;


class ProjectController extends Controller
{

    public function index()
    {
        //Validating request
        request()->validate([
            'direction' => ['in:asc,desc'],
            'field' => ['in:name,email']
        ]);

        //Project data
        $query = Project::query();
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

        return Inertia::render('Projects/Index', [
            'projects' => Project::first(),
            'balances' => $query->paginate(12)
                ->through(
                    fn ($proj) =>
                    [
                        'id' => $proj->id,
                        'name' => $proj->name,
                        'address' => $proj->address,
                        'start' => $proj->start,
                        'end' => $proj->end,
                        // 'email' => $proj->email,
                        // 'phone_no' => $proj->phone_no,
                        // 'stn_no' => $proj->stn_no,
                        // 'ntn_no' => $proj->ntn_no,
                        'delete' => Trade::where('id', $proj->id)->first() ? false : true,

                    ],
                ),
            'filters' => request()->all(['search', 'field', 'direction'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Projects/Create');
    }

    public function store(Req $request)
    {

        Request::validate([
            'name' => ['required', 'unique:projects', 'max:255'],
            'start' => ['required'],
            'end' => ['required'],
            // 'email' => ['required', 'email', 'unique:Projects,email'],
        ]);
        DB::transaction(function () use ($request) {
            $project = Project::create([
                'name' => strtoupper($request->name),
                'address' => $request->address,
                'start' => $request->start,
                'end' => $request->end,
            ]);

            Setting::create([
                'key' => 'active_project',
                'value' => $project->id,
                'user_id' => Auth::user()->id,
            ]);

            Storage::makeDirectory('/public/' . $project->id);
            session(['project_id' => $project->id]);
        });
        return Redirect::route('projects')->with('success', 'Project created');
    }

    public function edit(Project $project)
    {
        return Inertia::render('Projects/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'address' => $project->address,
                'start' => $project->start,
                'end' => $project->end,
                // 'email' => $Project->email,
                // 'address' => $Project->address,
                // 'stn_no' => $Project->stn_no,
                // 'phone_no' => $Project->phone_no,
                // 'ntn_no' => $Project->ntn_no,
                // 'account_id' => $Project->account_id,
            ],
        ]);
    }

    public function update(Project $project, Req $request)
    {
        // dd($request);
        Request::validate([
            'name' => ['required',  'max:255'],
            // 'email' => ['required', 'email'],
            // 'address' => ['nullable'],
            // 'stn_no' => ['nullable'],
            // 'phone_no' => ['nullable'],
            // 'ntn_no' => ['nullable'],
        ]);
        DB::transaction(function () use ($request, $project) {
            // dd($request);
            $project->name = strtoupper($request->name);
            $project->address = $request->address;
            $project->start = $request->start;
            $project->end = $request->end;
            // $project->email = $request->email;
            // $project->stn_no = $request->stn_no;
            // $project->phone_no = $request->phone_no;
            // $project->ntn_no = $request->ntn_no;
            $project->save();
        });


        return Redirect::route('projects')->with('success', 'Project updated.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return Redirect::back()->with('success', 'Project deleted.');
    }

    //Project Change function
    public function projch($id)
    {

        $active_proj = Setting::where('user_id', Auth::user()->id)->where('key', 'active_project')->first();
        $active_proj->value = $id;
        $active_proj->save();

        session(['project_id' => $id]);

        // $active_yr = Setting::where('user_id', Auth::user()->id)->where('key', 'active_year')->first();
        // // dd($active_yr);
        // if ($active_yr) {
        //     $active_yr->value = Year::where('company_id', $id)->latest()->first()->id;
        //     $active_yr->save();
        //     session(['year_id' => $active_yr->value]);
        // } else {
        //     $active_yr = Year::where('company_id', $id)->latest()->first()->id;
        //     session(['year_id' => $active_yr]);
        // }

        return back()->withInput();
    }

    public function excel($proj_id)
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(11);


        $i = 0;


        $project = Project::where('id', $proj_id)->first();

        $start = Carbon::createFromFormat('Y-m-d', $project->start);
        $end = Carbon::createFromFormat('Y-m-d', $project->end);

        $diff_in_months = $start->diffInMonths($end);

        $a = 'H';
        for ($j = 0; $j <= $diff_in_months; $j++) {
            //     //To format the start date
            $start = Carbon::parse($start)->format('Y-m-d');
            //     //To save the last item date according to the trade end date
            if ($j == $diff_in_months) {
                $lastDayofMonth = Carbon::parse($end)->format('Y-m-d');
            } else {
                $lastDayofMonth = Carbon::parse($start)->endOfMonth()->format('Y-m-d');
            }

            if ($j == 0) {
                $lastDayofMonths = [Carbon::parse($start)->format('M d, Y')];
                $spreadsheet->getActiveSheet()->fromArray($lastDayofMonths, NULL, $a . '4')->getColumnDimension($a)->setWidth(15);
            } else {
                $lastDayofMonths = [Carbon::parse($lastDayofMonth)->format('M d, Y')];
                $spreadsheet->getActiveSheet()->fromArray($lastDayofMonths, NULL, $a . '4')->getColumnDimension($a)->setWidth(15);
            }
            $colArray = ['D:D', $a . ':' . $a];
            foreach ($colArray as $key => $col) {

                $FORMAT_ACCOUNTING = '_($ #,##0.00_);_($ \(#,##0.00\);_($ "-"??_);_(@_)';
                $spreadsheet->getActiveSheet()->getStyle($col)->getNumberFormat()->setFormatCode($FORMAT_ACCOUNTING);
            }
            $tcell = $a;
            $a++;

            //Top
            $spreadsheet->getActiveSheet()->getStyle('E3:' . $a . '3')->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->getStyle('E3:' . $a . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $spreadsheet->getActiveSheet()->getStyle('E3:' . $a . '3')
                ->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            //Bottom
            $spreadsheet->getActiveSheet()->getStyle('E4:' . $a . '4')
                ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            //Left
            $spreadsheet->getActiveSheet()->getStyle('E3')
                ->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            //Right
            $spreadsheet->getActiveSheet()->getStyle('E4:' . $a . '4')->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->getStyle('E4')
                ->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

            $start = Carbon::parse($lastDayofMonth)->addDays(1)->toDateString();
        }
        // $SUMRANGE = 'j6:j9';
        // $spreadsheet->getActiveSheet()->setCellValue('j11', '=SUM(j6:j9)');






        $spreadsheet->getActiveSheet()->getStyle($a . '3')
            ->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

        $spreadsheet->getActiveSheet()->getStyle($a . '4')
            ->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);



        $spreadsheet->getActiveSheet()->mergeCells('A1:C1');
        $spreadsheet->getActiveSheet()->getStyle('A1:C1')->getFont()->setSize(13);
        $spreadsheet->getActiveSheet()->getStyle('A1:C1')->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->getStyle('A1:C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()->mergeCells('A1:C1');
        $spreadsheet->getActiveSheet()->fromArray([$project->name . ' Job Cost Report'], NULL, 'A1')->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getStyle('A1:C1')
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

        // $spreadsheet->getActiveSheet()->getstyle('A1:E1')
        //     ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


        $spreadsheet->getActiveSheet()->mergeCells('B4:C4');
        $spreadsheet->getActiveSheet()->getStyle('B4:C4')->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('B4:C4')->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->getStyle('B4:C4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()->fromArray(['Estimated Project Revenue'], NULL, 'B4')->getColumnDimension('B')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(27);

        $spreadsheet->getActiveSheet()->getStyle('B4:C4')
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);


        $spreadsheet->getActiveSheet()->fromArray(['Start Date'], NULL, 'E3')->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->fromArray(['End Date'], NULL, 'F3')->getColumnDimension('F')->setWidth(15);

        $spreadsheet->getActiveSheet()->fromArray([Carbon::parse($project->start)->format('M d, Y')], NULL, 'E4')->getColumnDimension('E')->setWidth(15);
        $spreadsheet->getActiveSheet()->fromArray([Carbon::parse($project->end)->format('M d, Y')], NULL, 'F4')->getColumnDimension('F')->setWidth(15);
        // Project name and date divided end.

        // Trade Revenue
        $trades = Trade::where('project_id', $proj_id)->where('cost', null)->get();
        $i = 0;
        foreach ($trades as $trade) {
            $items[$i] = Item::where('trade_id', $trade->id)
                ->where('actual', 0)->where('cost', null)
                ->get();
            $i++;
        }

        // Trade Cost
        $trades_cost = Trade::where('project_id', $proj_id)->where('revenue', null)->get();
        $i = 0;
        foreach ($trades_cost as $trade) {
            $items_cost[$i] = Item::where('trade_id', $trade->id)
                ->where('actual', 0)->where('revenue', null)
                ->get();
            $i++;
        }


        //Actual Revenue
        $actual_revenue = Trade::where('project_id', $proj_id)->where('actual', 1)->where('cost', '!>', 0)->get();
        $i = 0;
        foreach ($actual_revenue as $trade) {
            $item_rev[$i] = Item::where('parent_id', $trade->parent_id)
                ->where('actual', 1)
                ->where('cost', '!>', 0)
                ->get();
            $i++;
        }

        // Actual Cost
        $actual_cost = Trade::where('project_id', $proj_id)->where('actual', 1)->where('revenue', '!>', 0)->get();

        $i = 0;
        foreach ($actual_cost as $trade) {

            $item_cos[$i] = Item::where('parent_id', $trade->parent_id)
                ->where('actual', 1)
                ->where('revenue', '!>', 0)
                ->get();
            $i++;
        }

        $total_est_revenue = 0;
        $total_est_cost = 0;
        $total_act_revenue = 0;
        $total_act_cost = 0;
        // $t_item_est_rev = 0;


        $i = 6;
        // Trade Estimate Revenue
        for ($x = 0; $x < count($trades); $x++) {
            $trade_name = [$trades[$x]->name];
            $trade_revenue = [strval($trades[$x]->revenue)];
            $total_est_revenue = $total_est_revenue += $trades[$x]->revenue;
            $tradte_start = [Carbon::parse($trades[$x]->start)->format('M d, Y')];
            $trade_end = [Carbon::parse($trades[$x]->end)->format('M d, Y')];
            $spreadsheet->getActiveSheet()->mergeCells('A' . $i . ':' . 'C' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_name, NULL, 'A' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_revenue, NULL, 'D' . $i)->getColumnDimension('D')->setWidth(15);
            $spreadsheet->getActiveSheet()->fromArray($tradte_start, NULL, 'E' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_end, NULL, 'F' . $i);

            $a = 'H';
            $z = 0;
            // dd($items);
            foreach ($items as $item) {
                $itemss[$z] = $item;
                $z++;
            }

            // dd($itemss);
            $start = Carbon::createFromFormat('Y-m-d', $project->start);
            $end = Carbon::createFromFormat('Y-m-d', $itemss[$x][0]->start);
            $diff = $start->diffInMonths($end);
            for ($z = 0; $z < $diff; $z++) {
                $a++;
            }

            foreach ($itemss[$x] as $value) {
                $item_revenue = [strval($value->revenue)];
                $spreadsheet->getActiveSheet()->fromArray($item_revenue, NULL, $a . $i);
                $a++;
            }

            $i++;
        }
        $tcell = 'H';
        $i++;
        //trow_est_rev Mean Calcution Of Total Estimate Revenue
        $trow_est_rev = $i;
        for ($k = 0; $k <= $diff_in_months; $k++) {
            $spreadsheet->getActiveSheet()->getStyle($tcell . $trow_est_rev)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            // $spreadsheet->getActiveSheet()->getStyle('E3:' . $a . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $trow_est_rev, '=SUM(' . $tcell . 6 . ':' . $tcell . $i . ')');
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }
        // dd($trow_est_rev);

        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        // $spreadsheet->getActiveSheet()->getStyle('E3:' . $a . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $spreadsheet->getActiveSheet()->fromArray(['Total'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray([strval($total_est_revenue)], NULL, 'D' . $i);


        $i += 3;
        $spreadsheet->getActiveSheet()->mergeCells('B' . $i . ':' . 'C' . $i);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setSize(13);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $spreadsheet->getActiveSheet()->mergeCells('B' . $i . ':' . 'C' . $i);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $spreadsheet->getActiveSheet()->fromArray(['Estimated Project Cost'], NULL, 'B' . $i);
        $i += 2;

        // Trade Estimate Cost
        for ($x = 0; $x < count($trades_cost); $x++) {
            $trade_name = [$trades_cost[$x]->name];
            $trade_cost = [strval($trades_cost[$x]->cost)];
            $total_est_cost = $total_est_cost += $trades_cost[$x]->cost;
            $tradte_start = [Carbon::parse($trades_cost[$x]->start)->format('M d, Y')];
            $trade_end = [Carbon::parse($trades_cost[$x]->end)->format('M d, Y')];
            $spreadsheet->getActiveSheet()->fromArray($trade_name, NULL, 'A' . $i);
            $spreadsheet->getActiveSheet()->mergeCells('A' . $i . ':' . 'C' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_cost, NULL, 'D' . $i)->getColumnDimension('D')->setWidth(15);
            $spreadsheet->getActiveSheet()->fromArray($tradte_start, NULL, 'E' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_end, NULL, 'F' . $i);
            $a = 'H';
            $z = 0;
            // dd($trades);
            foreach ($items_cost as $item) {
                $itemss[$z] = $item;
                $z++;
            }

            $start = Carbon::createFromFormat('Y-m-d', $project->start);
            $end = Carbon::createFromFormat('Y-m-d', $itemss[$x][0]->start);
            $diff = $start->diffInMonths($end);
            for ($z = 0; $z < $diff; $z++) {
                $a++;
            }
            foreach ($itemss[$x] as $value) {
                $item_cost = [strval($value->cost)];
                $spreadsheet->getActiveSheet()->fromArray($item_cost, NULL, $a . $i);
                $a++;
            }
            $i++;
        }
        $tcell = 'H';
        $i++;
        //trow_est_cost Mean Calcution Of Total Estimate Cost
        $trow_est_cost = $trow_est_rev + 5;
        for ($k = 0; $k <= $diff_in_months; $k++) {
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i,)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=SUM(' . $tcell . $trow_est_cost . ':' . $tcell . $i . ')');
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }
        $trow_est_cost = $i;


        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);

        $spreadsheet->getActiveSheet()->fromArray(['Total'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray([strval($total_est_cost)], NULL, 'D' . $i);
        $i += 2;



        //Calculation Of Total Estimate Profit trow_est_rev - trow_est_cost
        $tcell = 'H';
        for ($k = 0; $k <= $diff_in_months; $k++) {
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);

            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=(' . $tcell . $trow_est_rev . '-' . $tcell . $trow_est_cost . ')');
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }
        $trow_est_profit = $i;



        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $total_est_profit = $total_est_revenue - $total_est_cost;
        $spreadsheet->getActiveSheet()->fromArray(['Total Estimated Profit'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray([strval($total_est_profit)], NULL, 'D' . $i);

        $i += 3;
        $spreadsheet->getActiveSheet()->mergeCells('B' . $i . ':' . 'C' . $i);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->mergeCells('B' . $i . ':' . 'C' . $i);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $spreadsheet->getActiveSheet()->fromArray(['Actual Project Revenue'], NULL, 'B' . $i);
        $i += 2;
        // Trade Actual Revenue
        for ($x = 0; $x < count($actual_revenue); $x++) {
            $trade_name = [$actual_revenue[$x]->name];
            $trade_revenue = [strval($actual_revenue[$x]->revenue)];
            $total_act_revenue = $total_act_revenue += $actual_revenue[$x]->revenue;
            $tradte_start = [Carbon::parse($actual_revenue[$x]->start)->format('M d, Y')];
            $trade_end = [Carbon::parse($actual_revenue[$x]->end)->format('M d, Y')];
            $spreadsheet->getActiveSheet()->fromArray($trade_name, NULL, 'A' . $i);
            $spreadsheet->getActiveSheet()->mergeCells('A' . $i . ':' . 'C' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_revenue, NULL, 'D' . $i)->getColumnDimension('D')->setWidth(15);
            $spreadsheet->getActiveSheet()->fromArray($tradte_start, NULL, 'E' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_end, NULL, 'F' . $i);
            $a = 'H';
            $z = 0;
            // dd($item_rev);
            foreach ($item_rev as $item) {
                $itemss[$z] = $item;
                $z++;
            }

            $start = Carbon::createFromFormat('Y-m-d', $project->start);
            $end = Carbon::createFromFormat('Y-m-d', $itemss[$x][0]->start);
            $diff = $start->diffInMonths($end);

            for ($z = 0; $z < $diff; $z++) {
                $a++;
            }

            foreach ($itemss[$x] as $value) {
                $item_revenue = [strval($value->revenue)];
                $spreadsheet->getActiveSheet()->fromArray($item_revenue, NULL, $a . $i);
                $a++;
            }

            $i++;
        };


        $tcell = 'H';
        $i++;

        //trow_act_revenue Mean Calculation Of Total Actual Revenue
        $trow_act_rev = $trow_est_profit + 5;
        for ($k = 0; $k <= $diff_in_months; $k++) {
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=SUM(' . $tcell . $trow_act_rev . ':' . $tcell . $i . ')');
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }
        $trow_act_rev = $i;

        // $i++;
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);

        $spreadsheet->getActiveSheet()->fromArray(['Total'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray([strval($total_act_revenue)], NULL, 'D' . $i);

        $i += 3;
        $spreadsheet->getActiveSheet()->mergeCells('B' . $i . ':' . 'C' . $i);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $spreadsheet->getActiveSheet()->fromArray(['Actual Project Cost'], NULL, 'B' . $i);
        $i += 2;

        for ($x = 0; $x < count($actual_cost); $x++) {
            $trade_name = [$actual_cost[$x]->name];
            $trade_cost = [strval($actual_cost[$x]->cost)];
            $total_act_cost = $total_act_cost += $actual_cost[$x]->cost;
            $tradte_start = [Carbon::parse($actual_cost[$x]->start)->format('M d, Y')];
            $trade_end = [Carbon::parse($actual_cost[$x]->end)->format('M d, Y')];
            $spreadsheet->getActiveSheet()->mergeCells('A' . $i . ':' . 'C' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_name, NULL, 'A' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_cost, NULL, 'D' . $i)->getColumnDimension('D')->setWidth(15);
            $spreadsheet->getActiveSheet()->fromArray($tradte_start, NULL, 'E' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_end, NULL, 'F' . $i);
            $a = 'H';
            $z = 0;

            foreach ($item_cos as $item) {
                $itemss[$z] = $item;
                $z++;
            }

            $start = Carbon::createFromFormat('Y-m-d', $project->start);
            $end = Carbon::createFromFormat('Y-m-d', $itemss[$x][0]->start);
            $diff = $start->diffInMonths($end);

            for ($z = 0; $z < $diff; $z++) {
                $a++;
            }

            foreach ($itemss[$x] as $value) {
                $item_cost = [strval($value->cost)];
                $spreadsheet->getActiveSheet()->fromArray($item_cost, NULL, $a . $i);
                $a++;
            }
            $i++;
        };

        $tcell = 'H';
        $i++;
        //trow_act_cost Mean Calcution Of Total Actual Cost
        $trow_act_cost = $trow_act_rev + 5;
        for ($k = 0; $k <= $diff_in_months; $k++) {
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=SUM(' . $tcell . $trow_act_cost . ':' . $tcell . $i . ')');
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }
        $trow_act_cost = $i;

        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);

        $spreadsheet->getActiveSheet()->fromArray(['Total'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray([strval($total_act_cost)], NULL, 'D' . $i);
        $i += 2;

        $tcell = 'H';
        for ($k = 0; $k <= $diff_in_months; $k++) {
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=(' . $tcell . $trow_act_rev . '-' . $tcell . $trow_act_cost . ')');
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }
        $trow_act_profit = $i;

        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $total_act_profit = $total_act_revenue - $total_act_cost;
        $spreadsheet->getActiveSheet()->fromArray(['Total Actual Project Profit'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray([strval($total_act_profit)], NULL, 'D' . $i);
        $i += 3;
        $spreadsheet->getActiveSheet()->mergeCells('B' . $i . ':' . 'C' . $i);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setSize(12);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $spreadsheet->getActiveSheet()->fromArray(['Variance'], NULL, 'B' . $i);
        $spreadsheet->getActiveSheet()->getStyle('B' . $i . ':' . 'C' . $i)
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        $i += 2;

        $tcell = 'H';
        for ($k = 0; $k <= $diff_in_months; $k++) {
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=(' . $tcell . $trow_est_rev . '-' . $tcell . $trow_act_rev . ')');
            // $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
            //     ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }


        $spreadsheet->getActiveSheet()->fromArray(['Project Revenue'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray(['$' . strval($total_est_revenue - $total_act_revenue)], NULL, 'D' . $i);
        $i++;


        $tcell = 'H';
        for ($k = 0; $k <= $diff_in_months; $k++) {
         
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=(' . $tcell . $trow_est_cost . '-' . $tcell . $trow_act_cost . ')');
            // $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
            //     ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }

        $spreadsheet->getActiveSheet()->fromArray(['Project Cost'], NULL, 'C' . $i);
        $spreadsheet->getActiveSheet()->fromArray(['$' . strval($total_est_cost - $total_act_cost)], NULL, 'D' . $i);
        $i++;

        $tcell = 'H';
        //Variance Estimate Profite - Actual Profit
        for ($k = 0; $k <= $diff_in_months; $k++) {
               $spreadsheet->getActiveSheet()->getStyle($tcell . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $spreadsheet->getActiveSheet()->setCellValue($tcell . $i, '=(' . $tcell . $trow_est_profit . '-' . $tcell . $trow_act_profit . ')');
            $spreadsheet->getActiveSheet()->getStyle($tcell . $i)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $tcell++;
        }

       
        $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $spreadsheet->getActiveSheet()->fromArray(['Project Profit'], NULL, 'C' . $i);
         $spreadsheet->getActiveSheet()->getStyle('C' . $i . ':' . 'D' . $i)->getFont()->setBold(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $spreadsheet->getActiveSheet()->fromArray(['$' . strval($total_est_profit - $total_act_profit)], NULL, 'D' . $i);

        // dd($total_est_revenue - $total_act_revenue);


        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . $proj_id . '/' . 'Control Sheet.xlsx'));
        return response()->download(storage_path('app/public/' . $proj_id . '/' .   'Control Sheet.xlsx'));
    }
}
