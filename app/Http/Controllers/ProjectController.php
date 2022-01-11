<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;


use Inertia\Inertia;
use Illuminate\Http\Request as Req;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use Artisan;

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
            'name' => ['required', 'max:255'],
            'start' => ['required'],
            'end' => ['required'],
            // 'email' => ['required', 'email', 'unique:Projects,email'],
        ]);
        DB::transaction(function () use ($request) {

            Project::create([
                'name' => strtoupper($request->name),
                'address' => $request->address,
                'start' => $request->start, 
                'end' => $request->end, 
                
                // 'email' => $request->email,
                // 'stn_no' => $request->stn_no,
                // 'phone_no' => $request->phone_no,
                // 'ntn_no' => $request->ntn_no,
                // 'account_id' => $account->id,

            ]);
        });
        return Redirect::route('projects')->with('success', 'Project created');
    }

    public function edit(Project $project)
    {
        return Inertia::render('Projects/Edit', [
            'Project' => [
                'id' => $project->id,
                'name' => $project->name,
                 'start_date' => $project->start, 
                'end_date' => $project->end, 
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
            $project->start = $request->end;
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
}
