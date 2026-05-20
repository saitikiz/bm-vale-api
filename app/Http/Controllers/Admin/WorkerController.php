<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyWorkerRequest;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateWorkerRequest;
use App\Models\Site;
use App\Models\Worker;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class WorkerController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('worker_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Worker::with(['site'])->select(sprintf('%s.*', (new Worker)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'worker_show';
                $editGate      = 'worker_edit';
                $deleteGate    = 'worker_delete';
                $crudRoutePart = 'workers';

                return view('partials.datatablesActions', compact(
                    'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('id', function ($row) {
                return $row->id ? $row->id : '';
            });
            $table->editColumn('uuid', function ($row) {
                return $row->uuid ? $row->uuid : '';
            });
            $table->editColumn('active', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->active ? 'checked' : null) . '>';
            });
            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : '';
            });
            $table->addColumn('site_name', function ($row) {
                return $row->site ? $row->site->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'active', 'site']);

            return $table->make(true);
        }

        $sites = Site::get();

        return view('admin.workers.index', compact('sites'));
    }

    public function create()
    {
        abort_if(Gate::denies('worker_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.workers.create', compact('sites'));
    }

    public function store(StoreWorkerRequest $request)
    {
        $worker = Worker::create($request->all());

        return redirect()->route('admin.workers.index');
    }

    public function edit(Worker $worker)
    {
        abort_if(Gate::denies('worker_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $worker->load('site');

        return view('admin.workers.edit', compact('sites', 'worker'));
    }

    public function update(UpdateWorkerRequest $request, Worker $worker)
    {
        $worker->update($request->all());

        return redirect()->route('admin.workers.index');
    }

    public function show(Worker $worker)
    {
        abort_if(Gate::denies('worker_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $worker->load('site', 'workerBonusRequests');

        return view('admin.workers.show', compact('worker'));
    }

    public function destroy(Worker $worker)
    {
        abort_if(Gate::denies('worker_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $worker->delete();

        return back();
    }

    public function massDestroy(MassDestroyWorkerRequest $request)
    {
        $workers = Worker::find(request('ids'));

        foreach ($workers as $worker) {
            $worker->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
