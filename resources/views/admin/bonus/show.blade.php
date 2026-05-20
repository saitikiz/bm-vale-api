@extends('layouts.admin')
@section('content')

    <div class="card">
        <div class="card-header">
            {{ trans('global.show') }} {{ trans('cruds.bonu.title') }}
        </div>

        <div class="card-body">
            <div class="form-group">
                <div class="form-group">
                    <a class="btn btn-default" href="{{ route('admin.bonus.index') }}">
                        {{ trans('global.back_to_list') }}
                    </a>
                </div>
                <table class="table table-bordered table-striped">
                    <tbody>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.id') }}
                        </th>
                        <td>
                            {{ $bonu->id }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.uuid') }}
                        </th>
                        <td>
                            {{ $bonu->uuid }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.active') }}
                        </th>
                        <td>
                            <input type="checkbox" disabled="disabled" {{ $bonu->active ? 'checked' : '' }}>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.name') }}
                        </th>
                        <td>
                            {{ $bonu->name }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.category') }}
                        </th>
                        <td>
                            {{ App\Models\Bonus::CATEGORY_SELECT[$bonu->category] ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.priority') }}
                        </th>
                        <td>
                            {{ $bonu->priority }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.ordering') }}
                        </th>
                        <td>
                            {{ $bonu->ordering }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.description') }}
                        </th>
                        <td>
                            {!! $bonu->description !!}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.image') }}
                        </th>
                        <td>
                            @if($bonu->image)
                                <a href="{{ $bonu->image->getUrl() }}" target="_blank" style="display: inline-block">
                                    <img src="{{ $bonu->image->getUrl('thumb') }}">
                                </a>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.delay') }}
                        </th>
                        <td>
                            {{ $bonu->delay }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.auto_assign') }}
                        </th>
                        <td>
                            <input type="checkbox" disabled="disabled" {{ $bonu->auto_assign ? 'checked' : '' }}>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.start_at') }}
                        </th>
                        <td>
                            {{ $bonu->start_at }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.end_at') }}
                        </th>
                        <td>
                            {{ $bonu->end_at }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.timezone') }}
                        </th>
                        <td>
                            {{ $bonu->timezone }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.site') }}
                        </th>
                        <td>
                            {{ $bonu->site->name ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.function_name') }}
                        </th>
                        <td>
                            {{ $bonu->function_name }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.bonu.fields.sourceid') }}
                        </th>
                        <td>
                            {{ $bonu->sourceid }}
                        </td>
                    </tr>
                    </tbody>
                </table>
                <div class="form-group">
                    <a class="btn btn-default" href="{{ route('admin.bonus.index') }}">
                        {{ trans('global.back_to_list') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            {{ trans('global.relatedData') }}
        </div>
        <ul class="nav nav-tabs" role="tablist" id="relationship-tabs">
            <li class="nav-item">
                <a class="nav-link" href="#bonus_bonus_requests" role="tab" data-toggle="tab">
                    {{ trans('cruds.bonusRequest.title') }}
                </a>
            </li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane" role="tabpanel" id="bonus_bonus_requests">
                @includeIf('admin.bonus.relationships.bonusBonusRequests', ['bonusRequests' => $bonu->bonusBonusRequests])
            </div>
        </div>
    </div>

@endsection
