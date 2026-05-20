<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\StoreBonuRequest;
use App\Http\Requests\UpdateBonuRequest;
use App\Http\Resources\Admin\BonuResource;
use App\Models\Bonus;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BonusesApiController extends Controller
{
    use MediaUploadingTrait;

    public function index()
    {
        abort_if(Gate::denies('bonu_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new BonuResource(Bonus::with(['site'])->get());
    }

    public function store(StoreBonuRequest $request)
    {
        $bonu = Bonus::create($request->all());

        if ($request->input('image', false)) {
            $bonu->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
        }

        return (new BonuResource($bonu))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Bonus $bonu)
    {
        abort_if(Gate::denies('bonu_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new BonuResource($bonu->load(['site']));
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

        return (new BonuResource($bonu))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(Bonus $bonu)
    {
        abort_if(Gate::denies('bonu_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bonu->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
