<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyBonuRequest;
use App\Http\Requests\StoreBonuRequest;
use App\Http\Requests\UpdateBonuRequest;
use App\Models\Bonus;
use App\Models\Site;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class BonusController extends Controller
{
    use MediaUploadingTrait;

    public function index(Request $request)
    {
        abort_if(Gate::denies('bonu_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        if ($request->ajax()) {
            $query = Bonus::with(['site'])->select(sprintf('%s.*', (new Bonus)->table));
            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                $viewGate      = 'bonu_show';
                $editGate      = 'bonu_edit';
                $deleteGate    = 'bonu_delete';
                $crudRoutePart = 'bonus';

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
            $table->editColumn('source', function ($row) {
                return $row->source ? $row->source : '';
            });
            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : '';
            });
            $table->editColumn('category', function ($row) {
                return $row->category ? Bonus::CATEGORY_SELECT[$row->category] : '';
            });
            $table->editColumn('priority', function ($row) {
                return $row->priority ? $row->priority : '';
            });
            $table->editColumn('ordering', function ($row) {
                return $row->ordering ? $row->ordering : '';
            });
            $table->editColumn('image', function ($row) {
                if ($photo = $row->image) {
                    return sprintf(
                        '<a href="%s" target="_blank"><img src="%s" width="50px" height="50px"></a>',
                        $photo->url,
                        $photo->thumbnail
                    );
                }

                return '';
            });
            $table->editColumn('delay', function ($row) {
                return $row->delay ? $row->delay : '';
            });
            $table->editColumn('auto_assign', function ($row) {
                return '<input type="checkbox" disabled ' . ($row->auto_assign ? 'checked' : null) . '>';
            });

            $table->editColumn('timezone', function ($row) {
                return $row->timezone ? $row->timezone : '';
            });
            $table->addColumn('site_name', function ($row) {
                return $row->site ? $row->site->name : '';
            });

            $table->rawColumns(['actions', 'placeholder', 'active', 'image', 'auto_assign', 'site']);

            return $table->make(true);
        }

        $sites = Site::get();

        return view('admin.bonus.index', compact('sites'));
    }

    public function create()
    {
        abort_if(Gate::denies('bonu_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.bonus.create', compact('sites'));
    }

    public function store(StoreBonuRequest $request)
    {
        $bonu = Bonus::create($request->all());

        if ($request->input('image', false)) {
            $bonu->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $bonu->id]);
        }

        return redirect()->route('admin.bonus.index');
    }

    public function edit(Bonus $bonu)
    {
        abort_if(Gate::denies('bonu_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $sites = Site::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $bonu->load('site');

        return view('admin.bonus.edit', compact('bonu', 'sites'));
    }

    public function update(UpdateBonuRequest $request, Bonus $bonu)
    {
        $bonu->update($request->all());

        if ($request->input('image', false)) {
            if (! $bonu->image || $request->input('image') !== $bonu->image->file_name) {
                if ($bonu->image) {
                    $bonu->image->delete();
                }
                $bonu->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
            }
        } elseif ($bonu->image) {
            $bonu->image->delete();
        }

        return redirect()->route('admin.bonus.index');
    }

    public function show(Bonus $bonu)
    {
        abort_if(Gate::denies('bonu_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bonu->load('site', 'bonusBonusRequests');

        return view('admin.bonus.show', compact('bonu'));
    }

    public function destroy(Bonus $bonu)
    {
        abort_if(Gate::denies('bonu_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bonu->delete();

        return back();
    }

    public function massDestroy(MassDestroyBonuRequest $request)
    {
        $bonus = Bonus::find(request('ids'));

        foreach ($bonus as $bonu) {
            $bonu->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('bonu_create') && Gate::denies('bonu_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Bonus();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
