@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.show') }} {{ trans('cruds.site.title') }}
    </div>

    <div class="card-body">
        <div class="form-group">
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.sites.index') }}">
                    {{ trans('global.back_to_list') }}
                </a>
            </div>
            <table class="table table-bordered table-striped">
                <tbody>
                    <tr>
                        <th>
                            {{ trans('cruds.site.fields.id') }}
                        </th>
                        <td>
                            {{ $site->id }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.site.fields.uuid') }}
                        </th>
                        <td>
                            {{ $site->uuid }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.site.fields.active') }}
                        </th>
                        <td>
                            <input type="checkbox" disabled="disabled" {{ $site->active ? 'checked' : '' }}>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.site.fields.name') }}
                        </th>
                        <td>
                            {{ $site->name }}
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {{ trans('cruds.site.fields.token') }}
                        </th>
                        <td>
                            {{ $site->token }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="form-group">
                <a class="btn btn-default" href="{{ route('admin.sites.index') }}">
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
            <a class="nav-link" href="#site_workers" role="tab" data-toggle="tab">
                {{ trans('cruds.worker.title') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#site_bonus_requests" role="tab" data-toggle="tab">
                {{ trans('cruds.bonusRequest.title') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#site_players" role="tab" data-toggle="tab">
                {{ trans('cruds.player.title') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#site_bonus" role="tab" data-toggle="tab">
                {{ trans('cruds.bonu.title') }}
            </a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane" role="tabpanel" id="site_workers">
            @includeIf('admin.sites.relationships.siteWorkers', ['workers' => $site->siteWorkers])
        </div>
        <div class="tab-pane" role="tabpanel" id="site_bonus_requests">
            @includeIf('admin.sites.relationships.siteBonusRequests', ['bonusRequests' => $site->siteBonusRequests])
        </div>
        <div class="tab-pane" role="tabpanel" id="site_players">
            @includeIf('admin.sites.relationships.sitePlayers', ['players' => $site->sitePlayers])
        </div>
        <div class="tab-pane" role="tabpanel" id="site_bonus">
            @includeIf('admin.sites.relationships.siteBonus', ['bonus' => $site->siteBonus])
        </div>
    </div>
</div>

@endsection