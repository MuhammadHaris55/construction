<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Inertia\Inertia;
use Illuminate\Http\Request as Req;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;

use App\Models\Project;
use App\Models\Trade;
use App\Models\Item;


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
     //   dd($id);
        $active_proj = Setting::where('user_id', Auth::user()->id)->where('key', 'active_company')->first();
        if ($active_proj) {
            $active_proj->value = $id;
            $active_proj->save();
        } else {
            $active_proj = $id;
        }

        session(['company_id' => $id]);

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

    public function excel()
    {
        $spreadsheet = new Spreadsheet();
        // dd(session()->get('project_id'));

        $colArray = ['H:H', 'I:I', 'J:J', 'K:K'];
        foreach ($colArray as $key => $col) {

            $FORMAT_ACCOUNTING = '_(* #,##0.00_);_(* \(#,##0.00\);_(* "-"??_);_(@_)';
            $spreadsheet->getActiveSheet()->getStyle($col)->getNumberFormat()->setFormatCode($FORMAT_ACCOUNTING);
        }



        $spreadsheet->getActiveSheet()->getstyle('A1:G1')
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $trade = Trade::where('project_id', session()->get('project_id'))->first();
        // dd($trade);

        // $spreadsheet->getActiveSheet()->getstyle('L3')
        //     ->getBorders()->getbottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        // $spreadsheet->getActiveSheet()->getstyle('L4')
        //     ->getBorders()->getbottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // $spreadsheet->getActiveSheet()->getstyle('O3')
        //     ->getBorders()->getbottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        // $spreadsheet->getActiveSheet()->getstyle('O4')
        //     ->getBorders()->getbottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

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
        // $company = \App\Models\BankBalance::where('company_id', session('company_id'))
        //     ->where('year_id', session('year_id'))->first();
        // if ($company) {
        //     $end = $company->year->end ? new Carbon($company->year->end) : null;
        // } else {
        //     return Redirect::route('balances.create')->with('success', 'Create Account first.');
        // }
        $spreadsheet->getActiveSheet()->fromArray(['PROJECT NAME'], NULL, 'A1')->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->fromArray(['REVISED ESTIMATE'], NULL, 'D1')->getColumnDimension('D')->setWidth(20);
        $spreadsheet->getActiveSheet()->fromArray(['Billed to Date'], NULL, 'F1')->getColumnDimension('F')->setWidth(20);
        $spreadsheet->getActiveSheet()->fromArray(['Remaining Balance'], NULL, 'G1')->getColumnDimension('G')->setWidth(20);


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
