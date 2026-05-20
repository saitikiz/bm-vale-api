@extends('layouts.admin')
@section('content')
@can('bonus_request_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('admin.bonus-requests.create') }}">
                {{ trans('global.add') }} {{ trans('cruds.bonusRequest.title_singular') }}
            </a>
        </div>
    </div>
@endcan
<div class="card">
    <div class="card-header">
        {{ trans('cruds.bonusRequest.title_singular') }} {{ trans('global.list') }}
    </div>

    <div class="card-body">
        <table class=" table table-bordered table-striped table-hover ajaxTable datatable datatable-BonusRequest">
            <thead>
                <tr>
                    <th width="10">

                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.id') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.uuid') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.worker') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.customer_username') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.customer_code') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.customerid') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.source') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.ip') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.status') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.status_reason') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.note') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.locked_at') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.retry_count') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.last_error') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.site') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonusRequest.fields.bonus') }}
                    </th>
                    <th>
                        &nbsp;
                    </th>
                </tr>
                <tr>
                    <td>
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <select class="search">
                            <option value>{{ trans('global.all') }}</option>
                            @foreach($workers as $key => $item)
                                <option value="{{ $item->name }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <select class="search" strict="true">
                            <option value>{{ trans('global.all') }}</option>
                            @foreach(App\Models\BonusRequest::SOURCE_SELECT as $key => $item)
                                <option value="{{ $key }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <select class="search" strict="true">
                            <option value>{{ trans('global.all') }}</option>
                            @foreach(App\Models\BonusRequest::STATUS_SELECT as $key => $item)
                                <option value="{{ $key }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <input class="search" type="text" placeholder="{{ trans('global.search') }}">
                    </td>
                    <td>
                        <select class="search">
                            <option value>{{ trans('global.all') }}</option>
                            @foreach($sites as $key => $item)
                                <option value="{{ $item->name }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                        <select class="search">
                            <option value>{{ trans('global.all') }}</option>
                            @foreach($bonus as $key => $item)
                                <option value="{{ $item->name }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td>
                    </td>
                </tr>
            </thead>
        </table>
    </div>
</div>



@endsection
@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('bonus_request_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}';
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.bonus-requests.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).data(), function (entry) {
          return entry.id
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }
  dtButtons.push(deleteButton)
@endcan

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: "{{ route('admin.bonus-requests.index') }}",
    columns: [
      { data: 'placeholder', name: 'placeholder' },
{ data: 'id', name: 'id' },
{ data: 'uuid', name: 'uuid' },
{ data: 'worker_name', name: 'worker.name' },
{ data: 'customer_username', name: 'customer_username' },
{ data: 'customer_code', name: 'customer_code' },
{ data: 'customerid', name: 'customerid' },
{ data: 'source', name: 'source' },
{ data: 'ip', name: 'ip' },
{ data: 'status', name: 'status' },
{ data: 'status_reason', name: 'status_reason' },
{ data: 'note', name: 'note' },
{ data: 'locked_at', name: 'locked_at' },
{ data: 'retry_count', name: 'retry_count' },
{ data: 'last_error', name: 'last_error' },
{ data: 'site_name', name: 'site.name' },
{ data: 'bonus_name', name: 'bonus.name' },
{ data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  };
  let table = $('.datatable-BonusRequest').DataTable(dtOverrideGlobals);
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
let visibleColumnsIndexes = null;
$('.datatable thead').on('input', '.search', function () {
      let strict = $(this).attr('strict') || false
      let value = strict && this.value ? "^" + this.value + "$" : this.value

      let index = $(this).parent().index()
      if (visibleColumnsIndexes !== null) {
        index = visibleColumnsIndexes[index]
      }

      table
        .column(index)
        .search(value, strict)
        .draw()
  });
table.on('column-visibility.dt', function(e, settings, column, state) {
      visibleColumnsIndexes = []
      table.columns(":visible").every(function(colIdx) {
          visibleColumnsIndexes.push(colIdx);
      });
  })
});

</script>
@endsection