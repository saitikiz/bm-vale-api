@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} {{ trans('cruds.player.title_singular') }}
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route("admin.players.update", [$player->id]) }}" enctype="multipart/form-data">
            @method('PUT')
            @csrf
            <div class="form-group">
                <label for="customer_username">{{ trans('cruds.player.fields.customer_username') }}</label>
                <input class="form-control {{ $errors->has('customer_username') ? 'is-invalid' : '' }}" type="text" name="customer_username" id="customer_username" value="{{ old('customer_username', $player->customer_username) }}">
                @if($errors->has('customer_username'))
                    <div class="invalid-feedback">
                        {{ $errors->first('customer_username') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.player.fields.customer_username_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="customer_code">{{ trans('cruds.player.fields.customer_code') }}</label>
                <input class="form-control {{ $errors->has('customer_code') ? 'is-invalid' : '' }}" type="text" name="customer_code" id="customer_code" value="{{ old('customer_code', $player->customer_code) }}">
                @if($errors->has('customer_code'))
                    <div class="invalid-feedback">
                        {{ $errors->first('customer_code') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.player.fields.customer_code_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="customerid">{{ trans('cruds.player.fields.customerid') }}</label>
                <input class="form-control {{ $errors->has('customerid') ? 'is-invalid' : '' }}" type="text" name="customerid" id="customerid" value="{{ old('customerid', $player->customerid) }}">
                @if($errors->has('customerid'))
                    <div class="invalid-feedback">
                        {{ $errors->first('customerid') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.player.fields.customerid_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="data">{{ trans('cruds.player.fields.data') }}</label>
                <textarea class="form-control {{ $errors->has('data') ? 'is-invalid' : '' }}" name="data" id="data">{{ old('data', $player->data) }}</textarea>
                @if($errors->has('data'))
                    <div class="invalid-feedback">
                        {{ $errors->first('data') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.player.fields.data_helper') }}</span>
            </div>
            <div class="form-group">
                <label for="site_id">{{ trans('cruds.player.fields.site') }}</label>
                <select class="form-control select2 {{ $errors->has('site') ? 'is-invalid' : '' }}" name="site_id" id="site_id">
                    @foreach($sites as $id => $entry)
                        <option value="{{ $id }}" {{ (old('site_id') ? old('site_id') : $player->site->id ?? '') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                    @endforeach
                </select>
                @if($errors->has('site'))
                    <div class="invalid-feedback">
                        {{ $errors->first('site') }}
                    </div>
                @endif
                <span class="help-block">{{ trans('cruds.player.fields.site_helper') }}</span>
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