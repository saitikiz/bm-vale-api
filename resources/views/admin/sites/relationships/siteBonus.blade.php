@can('bonu_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route('admin.bonus.create') }}">
                {{ trans('global.add') }} {{ trans('cruds.bonu.title_singular') }}
            </a>
        </div>
    </div>
@endcan

<div class="card">
    <div class="card-header">
        {{ trans('cruds.bonu.title_singular') }} {{ trans('global.list') }}
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class=" table table-bordered table-striped table-hover datatable datatable-siteBonus">
                <thead>
                <tr>
                    <th width="10">

                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.id') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.uuid') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.active') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.name') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.category') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.priority') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.ordering') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.image') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.delay') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.auto_assign') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.start_at') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.end_at') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.timezone') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.site') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.function_name') }}
                    </th>
                    <th>
                        {{ trans('cruds.bonu.fields.sourceid') }}
                    </th>
                    <th>
                        &nbsp;
                    </th>
                </tr>
                </thead>
                <tbody>
                @foreach($bonus as $key => $bonu)
                    <tr data-entry-id="{{ $bonu->id }}">
                        <td>

                        </td>
                        <td>
                            {{ $bonu->id ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->uuid ?? '' }}
                        </td>
                        <td>
                            <span style="display:none">{{ $bonu->active ?? '' }}</span>
                            <input type="checkbox" disabled="disabled" {{ $bonu->active ? 'checked' : '' }}>
                        </td>
                        <td>
                            {{ $bonu->name ?? '' }}
                        </td>
                        <td>
                            {{ App\Models\Bonus::CATEGORY_SELECT[$bonu->category] ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->priority ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->ordering ?? '' }}
                        </td>
                        <td>
                            @if($bonu->image)
                                <a href="{{ $bonu->image->getUrl() }}" target="_blank" style="display: inline-block">
                                    <img src="{{ $bonu->image->getUrl('thumb') }}">
                                </a>
                            @endif
                        </td>
                        <td>
                            {{ $bonu->delay ?? '' }}
                        </td>
                        <td>
                            <span style="display:none">{{ $bonu->auto_assign ?? '' }}</span>
                            <input type="checkbox" disabled="disabled" {{ $bonu->auto_assign ? 'checked' : '' }}>
                        </td>
                        <td>
                            {{ $bonu->start_at ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->end_at ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->timezone ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->site->name ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->function_name ?? '' }}
                        </td>
                        <td>
                            {{ $bonu->sourceid ?? '' }}
                        </td>
                        <td>
                            @can('bonu_show')
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.bonus.show', $bonu->id) }}">
                                    {{ trans('global.view') }}
                                </a>
                            @endcan

                            @can('bonu_edit')
                                <a class="btn btn-xs btn-info" href="{{ route('admin.bonus.edit', $bonu->id) }}">
                                    {{ trans('global.edit') }}
                                </a>
                            @endcan

                            @can('bonu_delete')
                                <form action="{{ route('admin.bonus.destroy', $bonu->id) }}" method="POST"
                                      onsubmit="return confirm('{{ trans('global.areYouSure') }}');"
                                      style="display: inline-block;">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="submit" class="btn btn-xs btn-danger"
                                           value="{{ trans('global.delete') }}">
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
            @can('bonu_delete')
            let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
            let deleteButton = {
                text: deleteButtonTrans,
                url: "{{ route('admin.bonus.massDestroy') }}",
                className: 'btn-danger',
                action: function (e, dt, node, config) {
                    var ids = $.map(dt.rows({selected: true}).nodes(), function (entry) {
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
                            data: {ids: ids, _method: 'DELETE'}
                        })
                            .done(function () {
                                location.reload()
                            })
                    }
                }
            }
            dtButtons.push(deleteButton)
            @endcan

            $.extend(true, $.fn.dataTable.defaults, {
                orderCellsTop: true,
                order: [[1, 'desc']],
                pageLength: 100,
            });
            let table = $('.datatable-siteBonus:not(.ajaxTable)').DataTable({buttons: dtButtons})
            $('a[data-toggle="tab"]').on('shown.bs.tab click', function (e) {
                $($.fn.dataTable.tables(true)).DataTable()
                    .columns.adjust();
            });

        })

    </script>
@endsection
