<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyPlayerRequest;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Models\Player;
use App\Models\Site;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class PlayersController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('player_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Player::with(['site'])->select(sprintf('%s.*', (new Player)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'player_show';
                $editGate      = 'player_edit';
                $deleteGate    = 'player_delete';
                $crudRoutePart = 'players';

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
            $table->editColumn('customer_username', function ($row) {
                return $row->customer_username ? $row->customer_username : '';
            });
            $table->editColumn('customer_code', function ($row) {
                return $row->customer_code ? $row->customer_code : '';
            });
            $table->editColumn('customerid', function ($row) {
                return $row->customerid ? $row->customerid : '';
            });
            $table->editColumn('data', function ($row) {
                return $row->data ? $row->data : '';
            });
            $table->addColumn('site_name', function ($row) {
                return $row->site ? $row->site->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'site']);

            return $table->make(true);
        }

        $sites = Site::get();

        return view('admin.players.index', compact('sites'));
    }

    public function create()
    {
        abort_if(Gate::denies('player_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.players.create', compact('sites'));
    }

    public function store(StorePlayerRequest $request)
    {
        $player = Player::create($request->all());

        return redirect()->route('admin.players.index');
    }

    public function edit(Player $player)
    {
        abort_if(Gate::denies('player_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $player->load('site');

        return view('admin.players.edit', compact('player', 'sites'));
    }

    public function update(UpdatePlayerRequest $request, Player $player)
    {
        $player->update($request->all());

        return redirect()->route('admin.players.index');
    }

    public function show(Player $player)
    {
        abort_if(Gate::denies('player_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $player->load('site');

        return view('admin.players.show', compact('player'));
    }

    public function destroy(Player $player)
    {
        abort_if(Gate::denies('player_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $player->delete();

        return back();
    }

    public function massDestroy(MassDestroyPlayerRequest $request)
    {
        $players = Player::find(request('ids'));

        foreach ($players as $player) {
            $player->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
