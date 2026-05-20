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
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-siteBonusRequests">
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
                </thead>
                <tbody>
                    @foreach($bonusRequests as $key => $bonusRequest)
                        <tr data-entry-id="{{ $bonusRequest->id }}">
                            <td>

                            </td>
                            <td>
                                {{ $bonusRequest->id ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->uuid ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->worker->name ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->customer_username ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->customer_code ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->customerid ?? '' }}
                            </td>
                            <td>
                                {{ App\Models\BonusRequest::SOURCE_SELECT[$bonusRequest->source] ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->ip ?? '' }}
                            </td>
                            <td>
                                {{ App\Models\BonusRequest::STATUS_SELECT[$bonusRequest->status] ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->status_reason ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->note ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->locked_at ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->retry_count ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->last_error ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->site->name ?? '' }}
                            </td>
                            <td>
                                {{ $bonusRequest->bonus->name ?? '' }}
                            </td>
                            <td>
                                @can('bonus_request_show')
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.bonus-requests.show', $bonusRequest->id) }}">
                                        {{ trans('global.view') }}
                                    </a>
                                @endcan

                                @can('bonus_request_edit')
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.bonus-requests.edit', $bonusRequest->id) }}">
                                        {{ trans('global.edit') }}
                                    </a>
                                @endcan

                                @can('bonus_request_delete')
                                    <form action="{{ route('admin.bonus-requests.destroy', $bonusRequest->id) }}" method="POST" onsubmit="return confirm('{{ trans('global.areYouSure') }}');" style="display: inline-block;">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <input type="submit" class="btn btn-xs btn-danger" value="{{ trans('global.delete') }}">
                                    </form>
                                @endcan

                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@section('scripts')
@parent
<script>
    $(function () {
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)
@can('bonus_request_delete')
  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.bonus-requests.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
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

  $.extend(true, $.fn.dataTable.defaults, {
    orderCellsTop: true,
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  let table = $('.datatable-siteBonusRequests:not(.ajaxTable)').DataTable({ buttons: dtButtons })
  $('a[data-toggle="tab"]').on('shown.bs.tab click', function(e){
      $($.fn.dataTable.tables(true)).DataTable()
          .columns.adjust();
  });
  
})

</script>
@endsection