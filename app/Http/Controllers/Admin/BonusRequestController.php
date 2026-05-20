<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyBonusRequestRequest;
use App\Http\Requests\StoreBonusRequestRequest;
use App\Http\Requests\UpdateBonusRequestRequest;
use App\Models\Bonus;
use App\Models\BonusRequest;
use App\Models\Site;
use App\Models\Worker;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class BonusRequestController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('bonus_request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = BonusRequest::with(['worker', 'site', 'bonus'])->select(sprintf('%s.*', (new BonusRequest)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'bonus_request_show';
                $editGate      = 'bonus_request_edit';
                $deleteGate    = 'bonus_request_delete';
                $crudRoutePart = 'bonus-requests';

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
            $table->addColumn('worker_name', function ($row) {
                return $row->worker ? $row->worker->name : '';
            });

            $table->editColumn('customer_username', function ($row) {
                return $row->customer_username ? $row->customer_username : '';
            });
            $table->editColumn('customer_code', function ($row) {
                return $row->customer_code ? $row->customer_code : '';
            });
            $table->editColumn('customerid', function ($row) {
                return $row->customerid ? $row->customerid : '';
            });
            $table->editColumn('source', function ($row) {
                return $row->source ? BonusRequest::SOURCE_SELECT[$row->source] : '';
            });
            $table->editColumn('ip', function ($row) {
                return $row->ip ? $row->ip : '';
            });
            $table->editColumn('status', function ($row) {
                return $row->status ? BonusRequest::STATUS_SELECT[$row->status] : '';
            });
            $table->editColumn('status_reason', function ($row) {
                return $row->status_reason ? $row->status_reason : '';
            });
            $table->editColumn('note', function ($row) {
                return $row->note ? $row->note : '';
            });

            $table->editColumn('retry_count', function ($row) {
                return $row->retry_count ? $row->retry_count : '';
            });
            $table->editColumn('last_error', function ($row) {
                return $row->last_error ? $row->last_error : '';
            });
            $table->addColumn('site_name', function ($row) {
                return $row->site ? $row->site->name : '';
            });

            $table->addColumn('bonus_name', function ($row) {
                return $row->bonus ? $row->bonus->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'worker', 'site', 'bonus']);

            return $table->make(true);
        }

        $workers = Worker::get();
        $sites   = Site::get();
        $bonus   = Bonus::get();

        return view('admin.bonusRequests.index', compact('workers', 'sites', 'bonus'));
    }

    public function create()
    {
        abort_if(Gate::denies('bonus_request_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $workers = Worker::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bonuses = Bonus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.bonusRequests.create', compact('bonuses', 'sites', 'workers'));
    }

    public function store(StoreBonusRequestRequest $request)
    {
        $bonusRequest = BonusRequest::create($request->all());

        return redirect()->route('admin.bonus-requests.index');
    }

    public function edit(BonusRequest $bonusRequest)
    {
        abort_if(Gate::denies('bonus_request_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $workers = Worker::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bonuses = Bonus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bonusRequest->load('worker', 'site', 'bonus');

        return view('admin.bonusRequests.edit', compact('bonusRequest', 'bonuses', 'sites', 'workers'));
    }

    public function update(UpdateBonusRequestRequest $request, BonusRequest $bonusRequest)
    {
        $bonusRequest->update($request->all());

        return redirect()->route('admin.bonus-requests.index');
    }

    public function show(BonusRequest $bonusRequest)
    {
        abort_if(Gate::denies('bonus_request_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bonusRequest->load('worker', 'site', 'bonus');

        return view('admin.bonusRequests.show', compact('bonusRequest'));
    }

    public function destroy(BonusRequest $bonusRequest)
    {
        abort_if(Gate::denies('bonus_request_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bonusRequest->delete();

        return back();
    }

    public function massDestroy(MassDestroyBonusRequestRequest $request)
    {
        $bonusRequests = BonusRequest::find(request('ids'));

        foreach ($bonusRequests as $bonusRequest) {
            $bonusRequest->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
