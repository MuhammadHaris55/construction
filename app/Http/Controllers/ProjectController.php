<?php

namespace App\Http\Controllers;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Inertia\Inertia;
use Illuminate\Http\Request as Req;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
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
            'balances' => $query->paginate(1)
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
                        // 'delete' => Account::where('id', $proj->id)->first() ? false : true,

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
        $colArray = ['H:H', 'I:I', 'J:J', 'K:K'];
        foreach ($colArray as $key => $col) {
            $FORMAT_ACCOUNTING = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';
            $spreadsheet->getActiveSheet()->getStyle($col)->getNumberFormat()->setFormatCode($FORMAT_ACCOUNTING);
        }

        $i = 0;

        // $trades_rev = Trade::where('project_id', $proj_id)->where('revenue', null)->get();

        //prject Date Divided Start
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
            $a++;

            //Top
            $spreadsheet->getActiveSheet()->getStyle('E3:' . $a . '3')
                ->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            //Bottom
            $spreadsheet->getActiveSheet()->getStyle('E4:' . $a . '4')
                ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            //Left
            $spreadsheet->getActiveSheet()->getStyle('E3')
                ->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            //Right
            $spreadsheet->getActiveSheet()->getStyle('E4')
                ->getBorders()->getLeft()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

            $start = Carbon::parse($lastDayofMonth)->addDays(1)->toDateString();
        }

        $spreadsheet->getActiveSheet()->getStyle($a . '3')
            ->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

        $spreadsheet->getActiveSheet()->getStyle($a . '4')
            ->getBorders()->getRight()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

        $spreadsheet->getActiveSheet()->mergeCells('A1:C1');
        $spreadsheet->getActiveSheet()->fromArray([$project->name . ' Job Cost Report'], NULL, 'A1')->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getStyle('A1:C1')
            ->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
        // $spreadsheet->getActiveSheet()->getstyle('A1:E1')
        //     ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $spreadsheet->getActiveSheet()->fromArray(['Estimated Project Revenue'], NULL, 'B4')->getColumnDimension('B')->setWidth(15);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(15);
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
        $actual_revenue = Trade::where('project_id', $proj_id)->where('cost', null)->get();
        // dd($actual_revenue);
        $i = 0;
        foreach ($actual_revenue as $trade) {
            $item_rev[$i] = Item::where('trade_id', $trade->id)
                ->where('actual', 1)
                ->where('cost', 0.00)
                ->get();
            $i++;
        }
        // dd($item_rev);






        $i = 6;
        // Trade Revenue
        for ($x = 0; $x < count($trades); $x++) {
            $trade_name = [$trades[$x]->name];
            $trade_revenue = [$trades[$x]->revenue];
            $tradte_start = [Carbon::parse($trades[$x]->start)->format('M d, Y')];
            $trade_end = [Carbon::parse($trades[$x]->end)->format('M d, Y')];
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
                $item_revenue = [$value->revenue];
                $spreadsheet->getActiveSheet()->fromArray($item_revenue, NULL, $a . $i);
                $a++;
            }
            $i++;
        }

        $i += 3;
        $spreadsheet->getActiveSheet()->fromArray(['Estimated Project Cost'], NULL, 'B' . $i);
        $i++;

        // Trade Cost
        for ($x = 0; $x < count($trades_cost); $x++) {
            $trade_name = [$trades_cost[$x]->name];
            $trade_revenue = [$trades_cost[$x]->cost];
            $tradte_start = [Carbon::parse($trades[$x]->start)->format('M d, Y')];
            $trade_end = [Carbon::parse($trades[$x]->end)->format('M d, Y')];
            $spreadsheet->getActiveSheet()->fromArray($trade_name, NULL, 'A' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_revenue, NULL, 'D' . $i)->getColumnDimension('D')->setWidth(15);
            $spreadsheet->getActiveSheet()->fromArray($tradte_start, NULL, 'E' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_end, NULL, 'F' . $i);
            $a = 'H';
            $z = 0;
            // dd($items);
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
                $item_cost = [$value->cost];
                $spreadsheet->getActiveSheet()->fromArray($item_cost, NULL, $a . $i);
                $a++;
            }
            $i++;
        }

        $i += 3;
        $spreadsheet->getActiveSheet()->fromArray(['Actual Project Revenue'], NULL, 'B' . $i);
        $i++;

        for ($x = 0; $x < count($actual_revenue); $x++) {
            $trade_name = [$actual_revenue[$x]->name];
            $trade_revenue = [$actual_revenue[$x]->revenue];
            $tradte_start = [Carbon::parse($trades[$x]->start)->format('M d, Y')];
            $trade_end = [Carbon::parse($trades[$x]->end)->format('M d, Y')];
            $spreadsheet->getActiveSheet()->fromArray($trade_name, NULL, 'A' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_revenue, NULL, 'D' . $i)->getColumnDimension('D')->setWidth(15);
            $spreadsheet->getActiveSheet()->fromArray($tradte_start, NULL, 'E' . $i);
            $spreadsheet->getActiveSheet()->fromArray($trade_end, NULL, 'F' . $i);
            $a = 'H';
            $z = 0;
            // dd($item_rev);
            foreach ($item_rev as $item) {
                // dd($item);
                $itemss[$z] = $item;
                $z++;
            }


            // $start = Carbon::createFromFormat('Y-m-d', $project->start);
            // $end = Carbon::createFromFormat('Y-m-d', $itemss[$x][0]->start);
            // $diff = $start->diffInMonths($end);
            // dd($diff);

            // for ($z = 0; $z < $diff; $z++) {
            //     $a++;`
            // }

            // dd($itemss);
            foreach ($itemss[$x] as $value) {
                // dd($value->revenue);
                $item_revenue = [$value->revenue];
                $spreadsheet->getActiveSheet()->fromArray($item_revenue, NULL, $a . $i);
                $a++;
            }
            $i++;
        }





        // $i = 5;
        // foreach ($trades as $trade) {
        //     $trade_name = [$trade->name];
        //     $trade_revenue = [$trade->revenue];
        //     $tradte_start = [$trade->start];
        //     $trade_end = [$trade->end];
        //     $spreadsheet->getActiveSheet()->fromArray($trade_name, NULL, 'A' . $i)->setMergeCells(['A' . $i . ':' . 'C' . $i]);
        //     $spreadsheet->getActiveSheet()->fromArray($trade_revenue, NULL, 'D' . $i);
        //     $spreadsheet->getActiveSheet()->fromArray($tradte_start, NULL, 'E' . $i);
        //     $spreadsheet->getActiveSheet()->fromArray($trade_end, NULL, 'F' . $i);
        //     $i++;
        // }



        // dd($items);

        // dd($item_end);



        // dd($cnt);
        // $spreadsheet->getActiveSheet()->fromArray($, NULL, 'h5');
        // $widthArray = ['10','10', '10','10',  '10','10','20', '20', '20', '15', '25', '17', '17', '17', '20', '20', '20', '20', '20'];
        // foreach (range('A', 'O') as $key => $col) {
        //     $spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth($widthArray[$key]);
        // }


        // $spreadsheet->getActiveSheet()->setMergeCells(['D5:E5']);
        //commit 192
        // $spreadsheet->getActiveSheet()->getStyle('C3:C5')->applyFromArray(
        // $spreadsheet->getActiveSheet()->getStyle('A1:D1')->applyFromArray(
        //     array(
        //         'font'  => array(
        //             'bold'  =>  true,
        //         ),
        //         'alignment' => array(
        //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        //             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        //             'wrapText' => true,
        //         ),
        //     )
        // );

        // $spreadsheet->getActiveSheet()->getStyle('D3:D5')->applyFromArray(
        //     array(
        //         'alignment' => array(
        //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        //             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        //             'wrapText' => true,
        //         ),
        //     )
        // );


        // $item = Item::all();
        // dd($item);

        // $rowArray = ['SR#', 'BANK', 'ACCOUNT#', 'ACCOUNT TYPE', 'CURRENCY', 'ADDRESS', 'AS PER LEDGER', 'AS PER BANK STATEMENT', 'AS PER CONFIRMATION', 'DIFFERENCE', 'CREATED', 'SENT', 'REMINDER', 'RECEIVED'];
        // / $spreadsheet->getActiveSheet()->fromArray($rowArray, NULL, 'B7');
        // $widthArray = ['10', '5', '20', '20', '20', '15', '25', '17', '17', '17', '20', '20', '20', '20', '20'];
        // foreach (range('A', 'O') as $key => $col) {
        //     $spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth($widthArray[$key]);

        // $spreadsheet->getActiveSheet()->fromArray(['PERIOD:'], NULL, 'C4');
        // $spreadsheet->getActiveSheet()->fromArray(['SUBJECT:'], NULL, 'C5');
        // $spreadsheet->getActiveSheet()->fromArray([$company->company->name], NULL, 'D3');
        // $spreadsheet->getActiveSheet()->fromArray([$end ? $end->format("M d Y") : null], NULL, 'D4');
        // $spreadsheet->getActiveSheet()->fromArray(['Bank Confirmation Control Sheet'], NULL, 'D5');

        // $spreadsheet->getActiveSheet()->fromArray(['Prepared By:'], NULL, 'K3');
        // $spreadsheet->getActiveSheet()->fromArray(['Reviewed By:'], NULL, 'K4');
        // $spreadsheet->getActiveSheet()->fromArray(['Date:'], NULL, 'N3');
        // $spreadsheet->getActiveSheet()->fromArray(['Date:'], NULL, 'N4');



        // $spreadsheet->getActiveSheet()->getStyle('B7:O7')->applyFromArray(
        //     array(
        //         'fill' => array(
        //             'fillType' => Fill::FILL_SOLID,
        //             'color' => array('rgb' => '484848')
        //         ),
        //         'font'  => array(
        //             'bold'  =>  true,
        //             'color' => array('rgb' => 'FFFFFF')
        //         ),
        //         'alignment' => array(
        //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        //             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        //             'wrapText' => true,
        //         ),
        //     )
        // );

        // $rowArray = ['SR#', 'BANK', 'ACCOUNT#', 'ACCOUNT TYPE', 'CURRENCY', 'ADDRESS', 'AS PER LEDGER', 'AS PER BANK STATEMENT', 'AS PER CONFIRMATION', 'DIFFERENCE', 'CREATED', 'SENT', 'REMINDER', 'RECEIVED'];
        // $spreadsheet->getActiveSheet()->fromArray($rowArray, NULL, 'B7');
        // $widthArray = ['10', '5', '20', '20', '20', '15', '25', '17', '17', '17', '20', '20', '20', '20', '20'];
        // foreach (range('A', 'O') as $key => $col) {
        //     $spreadsheet->getActiveSheet()->getColumnDimension($col)->setWidth($widthArray[$key]);
        // }

        // $dataa = \App\Models\BankConfirmation::where('company_id', session('company_id'))->where('year_id', session('year_id'))->first();
        // if ($dataa) {
        //     $data = \App\Models\BankBalance::where('company_id', session('company_id'))->where('year_id', session('year_id'))->get()
        //         ->map(
        //             function ($bal) {
        //                 return [
        //                     'id' => $bal->id,
        //                     'bank' => $bal->bankAccount->bankBranch->bank->name,
        //                     'number' => $bal->bankAccount->name,
        //                     'type' => $bal->bankAccount->type,
        //                     'currency' => $bal->bankAccount->currency,
        //                     'branch' => $bal->bankAccount->bankBranch->address,
        //                     'ledger' => $bal->ledger,
        //                     'statement' => $bal->statement,
        //                     'confirmation' => $bal->confirmation,
        //                     'difference' => $bal->statement - $bal->confirmation ? $bal->statement - $bal->confirmation : '0',
        //                     'confirm_create' => $bal->bankAccount->bankBranch->bankConfirmations()->where('company_id', session('company_id'))->where('year_id', session('year_id'))->get()->first()->confirm_create,
        //                     'sent' => $bal->bankAccount->bankBranch->bankConfirmations
        //                         ->filter(function ($confirmation) {
        //                             return ($confirmation->company_id == session('company_id') && $confirmation->year_id == session('year_id'));
        //                         })->first()->sent,
        //                     'reminder' => $bal->bankAccount->bankBranch->bankConfirmations()->where('company_id', session('company_id'))->where('year_id', session('year_id'))->get()->first()->reminder,
        //                     'received' => $bal->bankAccount->bankBranch->bankConfirmations()->where('company_id', session('company_id'))->where('year_id', session('year_id'))->get()->first()->received,
        //                 ];
        //             }
        //         )
        //         ->toArray();
        // } else {
        //     return Redirect::route('confirmations')->with('success', 'Please Create Confirmation.');
        // }

        // $cnt = count($data);
        // for ($i = 0; $i < $cnt; $i++) {
        //     // dd($data[$i]);
        //     $data[$i]['confirm_create'] = $data[$i]['confirm_create'] ? new Carbon($data[$i]['confirm_create']) : null;
        //     $data[$i]['confirm_create'] = $data[$i]['confirm_create'] ? $data[$i]['confirm_create']->format('F j, Y') : null;
        //     $data[$i]['sent'] = $data[$i]['sent'] ? new Carbon($data[$i]['sent']) : null;
        //     $data[$i]['sent'] = $data[$i]['sent'] ? $data[$i]['sent']->format('F j, Y') : null;
        //     $data[$i]['reminder'] = $data[$i]['reminder'] ? new Carbon($data[$i]['reminder']) : null;
        //     $data[$i]['reminder'] = $data[$i]['reminder'] ? $data[$i]['reminder']->format('F j, Y') : null;
        //     $data[$i]['received'] = $data[$i]['received'] ? new Carbon($data[$i]['received']) : null;
        //     $data[$i]['received'] = $data[$i]['received'] ? $data[$i]['received']->format('F j, Y') : null;
        // }


        // // dd($cnt);
        // $spreadsheet->getActiveSheet()->fromArray($data, NULL, 'B9');




        // $total = 0;
        // for ($i = 0; $i < $cnt; $i++) {
        //     $total = $total + $data[$i]['ledger'];
        // }

        // // dd($total);
        // $tstr = $cnt + 9;
        // $tcell = "H" . strval($tstr);
        // $spreadsheet->getActiveSheet()->setCellValue($tcell, $total);

        // $styleArray = [
        //     'borders' => [
        //         'outline' => [
        //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
        //             'color' => [
        //                 'rgb' => '484848',
        //             ],


        //         ],
        //     ],
        // ];
        // $spreadsheet->getActiveSheet()->getStyle($tcell)->applyFromArray($styleArray);

        $writer = new Xlsx($spreadsheet);
        $writer->save(storage_path('app/public/' . 'Control Sheet.xlsx'));
        return response()->download(storage_path('app/public/' .  'Control Sheet.xlsx'));
    }
}
