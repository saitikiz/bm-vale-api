@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.create') }} {{ trans('cruds.bonusRequest.title_singular') }}
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("admin.bonus-requests.store") }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="required" for="uuid">{{ trans('cruds.bonusRequest.fields.uuid') }}</label>
                <input class="form-control {{ $errors->has('uuid') ? 'is-invalid' : '' }}" type="text" name="uuid" id="uuid" value="{{ old('uuid', '') }}" required>
                @if($errors->has('uuid'))
                    <div class="invalid-feedback">
                        {{ $errors->first('uuid') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.uuid_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="worker_id">{{ trans('cruds.bonusRequest.fields.worker') }}</label>
                <select class="form-control select2 {{ $errors->has('worker') ? 'is-invalid' : '' }}" name="worker_id" id="worker_id">
                    @foreach($workers as $id => $entry)
                        <option value="{{ $id }}" {{ old('worker_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                    @endforeach
                </select>
                @if($errors->has('worker'))
                    <div class="invalid-feedback">
                        {{ $errors->first('worker') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.worker_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="customer_username">{{ trans('cruds.bonusRequest.fields.customer_username') }}</label>
                <input class="form-control {{ $errors->has('customer_username') ? 'is-invalid' : '' }}" type="text" name="customer_username" id="customer_username" value="{{ old('customer_username', '') }}">
                @if($errors->has('customer_username'))
                    <div class="invalid-feedback">
                        {{ $errors->first('customer_username') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.customer_username_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="customer_code">{{ trans('cruds.bonusRequest.fields.customer_code') }}</label>
                <input class="form-control {{ $errors->has('customer_code') ? 'is-invalid' : '' }}" type="text" name="customer_code" id="customer_code" value="{{ old('customer_code', '') }}">
                @if($errors->has('customer_code'))
                    <div class="invalid-feedback">
                        {{ $errors->first('customer_code') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.customer_code_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="customerid">{{ trans('cruds.bonusRequest.fields.customerid') }}</label>
                <input class="form-control {{ $errors->has('customerid') ? 'is-invalid' : '' }}" type="text" name="customerid" id="customerid" value="{{ old('customerid', '') }}">
                @if($errors->has('customerid'))
                    <div class="invalid-feedback">
                        {{ $errors->first('customerid') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.customerid_helper') }}</span>
            </div>
            <div class="form-group">
                <label class="required">{{ trans('cruds.bonusRequest.fields.source') }}</label>
                <select class="form-control {{ $errors->has('source') ? 'is-invalid' : '' }}" name="source" id="source" required>
                    <option value disabled {{ old('source', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                    @foreach(App\Models\BonusRequest::SOURCE_SELECT as $key => $label)
                        <option value="{{ $key }}" {{ old('source', 'other') === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @if($errors->has('source'))
                    <div class="invalid-feedback">
                        {{ $errors->first('source') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.source_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="ip">{{ trans('cruds.bonusRequest.fields.ip') }}</label>
                <input class="form-control {{ $errors->has('ip') ? 'is-invalid' : '' }}" type="text" name="ip" id="ip" value="{{ old('ip', '') }}">
                @if($errors->has('ip'))
                    <div class="invalid-feedback">
                        {{ $errors->first('ip') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.ip_helper') }}</span>
            </div>
            <div class="form-group">
                <label>{{ trans('cruds.bonusRequest.fields.status') }}</label>
                <select class="form-control {{ $errors->has('status') ? 'is-invalid' : '' }}" name="status" id="status">
                    <option value disabled {{ old('status', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                    @foreach(App\Models\BonusRequest::STATUS_SELECT as $key => $label)
                        <option value="{{ $key }}" {{ old('status', 'new') === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @if($errors->has('status'))
                    <div class="invalid-feedback">
                        {{ $errors->first('status') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.status_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="status_reason">{{ trans('cruds.bonusRequest.fields.status_reason') }}</label>
                <input class="form-control {{ $errors->has('status_reason') ? 'is-invalid' : '' }}" type="text" name="status_reason" id="status_reason" value="{{ old('status_reason', '') }}">
                @if($errors->has('status_reason'))
                    <div class="invalid-feedback">
                        {{ $errors->first('status_reason') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.status_reason_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="note">{{ trans('cruds.bonusRequest.fields.note') }}</label>
                <input class="form-control {{ $errors->has('note') ? 'is-invalid' : '' }}" type="text" name="note" id="note" value="{{ old('note', '') }}">
                @if($errors->has('note'))
                    <div class="invalid-feedback">
                        {{ $errors->first('note') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.note_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="locked_at">{{ trans('cruds.bonusRequest.fields.locked_at') }}</label>
                <input class="form-control datetime {{ $errors->has('locked_at') ? 'is-invalid' : '' }}" type="text" name="locked_at" id="locked_at" value="{{ old('locked_at') }}">
                @if($errors->has('locked_at'))
                    <div class="invalid-feedback">
                        {{ $errors->first('locked_at') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.locked_at_helper') }}</span>
            </div>
            <div class="form-group">
                <label class="required" for="retry_count">{{ trans('cruds.bonusRequest.fields.retry_count') }}</label>
                <input class="form-control {{ $errors->has('retry_count') ? 'is-invalid' : '' }}" type="number" name="retry_count" id="retry_count" value="{{ old('retry_count', '1') }}" step="1" required>
                @if($errors->has('retry_count'))
                    <div class="invalid-feedback">
                        {{ $errors->first('retry_count') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.retry_count_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="last_error">{{ trans('cruds.bonusRequest.fields.last_error') }}</label>
                <textarea class="form-control {{ $errors->has('last_error') ? 'is-invalid' : '' }}" name="last_error" id="last_error">{{ old('last_error') }}</textarea>
                @if($errors->has('last_error'))
                    <div class="invalid-feedback">
                        {{ $errors->first('last_error') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.last_error_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="site_id">{{ trans('cruds.bonusRequest.fields.site') }}</label>
                <select class="form-control select2 {{ $errors->has('site') ? 'is-invalid' : '' }}" name="site_id" id="site_id">
                    @foreach($sites as $id => $entry)
                        <option value="{{ $id }}" {{ old('site_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                    @endforeach
                </select>
                @if($errors->has('site'))
                    <div class="invalid-feedback">
                        {{ $errors->first('site') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.site_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="bonus_id">{{ trans('cruds.bonusRequest.fields.bonus') }}</label>
                <select class="form-control select2 {{ $errors->has('bonus') ? 'is-invalid' : '' }}" name="bonus_id" id="bonus_id">
                    @foreach($bonuses as $id => $entry)
                        <option value="{{ $id }}" {{ old('bonus_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                    @endforeach
                </select>
                @if($errors->has('bonus'))
                    <div class="invalid-feedback">
                        {{ $errors->first('bonus') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.bonusRequest.fields.bonus_helper') }}</span>
            </div>
            <div class="form-group">
                <button class="btn btn-danger" type="submit">
                    {{ trans('global.save') }}
                </button>
            </div>
        </form>
    </div>
</div>



@endsection