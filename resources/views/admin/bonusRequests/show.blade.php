@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('cruds.bonusRequest.title') }}
    </div>

    <div class="card-body">
        <div class="form-group">
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.bonus-requests.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.id') }}
                        </th>
                        <td>
                            {{ $bonusRequest->id }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.uuid') }}
                        </th>
                        <td>
                            {{ $bonusRequest->uuid }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.worker') }}
                        </th>
                        <td>
                            {{ $bonusRequest->worker->name ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.customer_username') }}
                        </th>
                        <td>
                            {{ $bonusRequest->customer_username }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.customer_code') }}
                        </th>
                        <td>
                            {{ $bonusRequest->customer_code }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.customerid') }}
                        </th>
                        <td>
                            {{ $bonusRequest->customerid }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.source') }}
                        </th>
                        <td>
                            {{ App\Models\BonusRequest::SOURCE_SELECT[$bonusRequest->source] ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.ip') }}
                        </th>
                        <td>
                            {{ $bonusRequest->ip }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.status') }}
                        </th>
                        <td>
                            {{ App\Models\BonusRequest::STATUS_SELECT[$bonusRequest->status] ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.status_reason') }}
                        </th>
                        <td>
                            {{ $bonusRequest->status_reason }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.note') }}
                        </th>
                        <td>
                            {{ $bonusRequest->note }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.locked_at') }}
                        </th>
                        <td>
                            {{ $bonusRequest->locked_at }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.retry_count') }}
                        </th>
                        <td>
                            {{ $bonusRequest->retry_count }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.last_error') }}
                        </th>
                        <td>
                            {{ $bonusRequest->last_error }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.site') }}
                        </th>
                        <td>
                            {{ $bonusRequest->site->name ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonusRequest.fields.bonus') }}
                        </th>
                        <td>
                            {{ $bonusRequest->bonus->name ?? '' }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.bonus-requests.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
        </div>
    </div>
</div>



@endsection