<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBonusRequestRequest;
use App\Http\Requests\UpdateBonusRequestRequest;
use App\Http\Resources\Admin\BonusRequestResource;
use App\Models\BonusRequest;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BonusRequestApiController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('bonus_request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new BonusRequestResource(BonusRequest::with(['worker', 'site', 'bonus'])->get());
    }

    public function store(StoreBonusRequestRequest $request)
    {
        $bonusRequest = BonusRequest::create($request->all());

        return (new BonusRequestResource($bonusRequest))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(BonusRequest $bonusRequest)
    {
        abort_if(Gate::denies('bonus_request_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return new BonusRequestResource($bonusRequest->load(['worker', 'site', 'bonus']));
    }

    public function update(UpdateBonusRequestRequest $request, BonusRequest $bonusRequest)
    {
        $bonusRequest->update($request->all());

        return (new BonusRequestResource($bonusRequest))
            ->response()
            ->setStatusCode(Response::HTTP_ACCEPTED);
    }

    public function destroy(BonusRequest $bonusRequest)
    {
        abort_if(Gate::denies('bonus_request_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $bonusRequest->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }
}
