@extends('layouts.admin')
@section('content')

    <div class="card">
        <div class="card-header">
            {{ trans('global.create') }} {{ trans('cruds.bonu.title_singular') }}
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route("admin.bonus.store") }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="required" for="uuid">{{ trans('cruds.bonu.fields.uuid') }}</label>
                    <input class="form-control {{ $errors->has('uuid') ? 'is-invalid' : '' }}" type="text" name="uuid"
                           id="uuid" value="{{ old('uuid', '') }}" required>
                    @if($errors->has('uuid'))
                        <div class="invalid-feedback">
                            {{ $errors->first('uuid') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.uuid_helper') }}</span>
                </div>
                <div class="form-group">
                    <div class="form-check {{ $errors->has('active') ? 'is-invalid' : '' }}">
                        <input type="hidden" name="active" value="0">
                        <input class="form-check-input" type="checkbox" name="active" id="active"
                               value="1" {{ old('active', 0) == 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">{{ trans('cruds.bonu.fields.active') }}</label>
                    </div>
                    @if($errors->has('active'))
                        <div class="invalid-feedback">
                            {{ $errors->first('active') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.active_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="name">{{ trans('cruds.bonu.fields.name') }}</label>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" type="text" name="name"
                           id="name" value="{{ old('name', '') }}">
                    @if($errors->has('name'))
                        <div class="invalid-feedback">
                            {{ $errors->first('name') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.name_helper') }}</span>
                </div>
                <div class="form-group">
                    <label class="required">{{ trans('cruds.bonu.fields.category') }}</label>
                    <select class="form-control {{ $errors->has('category') ? 'is-invalid' : '' }}" name="category"
                            id="category" required>
                        <option value
                                disabled {{ old('category', null) === null ? 'selected' : '' }}>{{ trans('global.pleaseSelect') }}</option>
                        @foreach(App\Models\Bonus::CATEGORY_SELECT as $key => $label)
                            <option value="{{ $key }}" {{ old('category', 'genel') === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('category'))
                        <div class="invalid-feedback">
                            {{ $errors->first('category') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.category_helper') }}</span>
                </div>
                <div class="form-group">
                    <label class="required" for="priority">{{ trans('cruds.bonu.fields.priority') }}</label>
                    <input class="form-control {{ $errors->has('priority') ? 'is-invalid' : '' }}" type="number"
                           name="priority" id="priority" value="{{ old('priority', '0') }}" step="1" required>
                    @if($errors->has('priority'))
                        <div class="invalid-feedback">
                            {{ $errors->first('priority') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.priority_helper') }}</span>
                </div>
                <div class="form-group">
                    <label class="required" for="ordering">{{ trans('cruds.bonu.fields.ordering') }}</label>
                    <input class="form-control {{ $errors->has('ordering') ? 'is-invalid' : '' }}" type="number"
                           name="ordering" id="ordering" value="{{ old('ordering', '0') }}" step="1" required>
                    @if($errors->has('ordering'))
                        <div class="invalid-feedback">
                            {{ $errors->first('ordering') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.ordering_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="description">{{ trans('cruds.bonu.fields.description') }}</label>
                    <textarea class="form-control ckeditor {{ $errors->has('description') ? 'is-invalid' : '' }}"
                              name="description" id="description">{!! old('description') !!}</textarea>
                    @if($errors->has('description'))
                        <div class="invalid-feedback">
                            {{ $errors->first('description') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.description_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="image">{{ trans('cruds.bonu.fields.image') }}</label>
                    <div class="needsclick dropzone {{ $errors->has('image') ? 'is-invalid' : '' }}"
                         id="image-dropzone">
                    </div>
                    @if($errors->has('image'))
                        <div class="invalid-feedback">
                            {{ $errors->first('image') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.image_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="delay">{{ trans('cruds.bonu.fields.delay') }}</label>
                    <input class="form-control {{ $errors->has('delay') ? 'is-invalid' : '' }}" type="number"
                           name="delay" id="delay" value="{{ old('delay', '') }}" step="1">
                    @if($errors->has('delay'))
                        <div class="invalid-feedback">
                            {{ $errors->first('delay') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.delay_helper') }}</span>
                </div>
                <div class="form-group">
                    <div class="form-check {{ $errors->has('auto_assign') ? 'is-invalid' : '' }}">
                        <input type="hidden" name="auto_assign" value="0">
                        <input class="form-check-input" type="checkbox" name="auto_assign" id="auto_assign"
                               value="1" {{ old('auto_assign', 0) == 1 ? 'checked' : '' }}>
                        <label class="form-check-label"
                               for="auto_assign">{{ trans('cruds.bonu.fields.auto_assign') }}</label>
                    </div>
                    @if($errors->has('auto_assign'))
                        <div class="invalid-feedback">
                            {{ $errors->first('auto_assign') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.auto_assign_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="start_at">{{ trans('cruds.bonu.fields.start_at') }}</label>
                    <input class="form-control datetime {{ $errors->has('start_at') ? 'is-invalid' : '' }}" type="text"
                           name="start_at" id="start_at" value="{{ old('start_at') }}">
                    @if($errors->has('start_at'))
                        <div class="invalid-feedback">
                            {{ $errors->first('start_at') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.start_at_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="end_at">{{ trans('cruds.bonu.fields.end_at') }}</label>
                    <input class="form-control datetime {{ $errors->has('end_at') ? 'is-invalid' : '' }}" type="text"
                           name="end_at" id="end_at" value="{{ old('end_at') }}">
                    @if($errors->has('end_at'))
                        <div class="invalid-feedback">
                            {{ $errors->first('end_at') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.end_at_helper') }}</span>
                </div>
                <div class="form-group">
                    <label class="required" for="timezone">{{ trans('cruds.bonu.fields.timezone') }}</label>
                    <input class="form-control {{ $errors->has('timezone') ? 'is-invalid' : '' }}" type="text"
                           name="timezone" id="timezone" value="{{ old('timezone', '') }}" required>
                    @if($errors->has('timezone'))
                        <div class="invalid-feedback">
                            {{ $errors->first('timezone') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.timezone_helper') }}</span>
                </div>
                <div class="form-group">
                    <label class="required" for="site_id">{{ trans('cruds.bonu.fields.site') }}</label>
                    <select class="form-control select2 {{ $errors->has('site') ? 'is-invalid' : '' }}" name="site_id"
                            id="site_id" required>
                        @foreach($sites as $id => $entry)
                            <option value="{{ $id }}" {{ old('site_id') == $id ? 'selected' : '' }}>{{ $entry }}</option>
                        @endforeach
                    </select>
                    @if($errors->has('site'))
                        <div class="invalid-feedback">
                            {{ $errors->first('site') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.site_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="function_name">{{ trans('cruds.bonu.fields.function_name') }}</label>
                    <input class="form-control {{ $errors->has('function_name') ? 'is-invalid' : '' }}" type="text"
                           name="function_name" id="function_name" value="{{ old('function_name', '') }}">
                    @if($errors->has('function_name'))
                        <div class="invalid-feedback">
                            {{ $errors->first('function_name') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.function_name_helper') }}</span>
                </div>
                <div class="form-group">
                    <label for="sourceid">{{ trans('cruds.bonu.fields.sourceid') }}</label>
                    <input class="form-control {{ $errors->has('sourceid') ? 'is-invalid' : '' }}" type="text"
                           name="sourceid" id="sourceid" value="{{ old('sourceid', '') }}">
                    @if($errors->has('sourceid'))
                        <div class="invalid-feedback">
                            {{ $errors->first('sourceid') }}
                        </div>
                    @endif
                    <span class="help-block">{{ trans('cruds.bonu.fields.sourceid_helper') }}</span>
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

@section('scripts')
    <script>
        $(document).ready(function () {
            function SimpleUploadAdapter(editor) {
                editor.plugins.get('FileRepository').createUploadAdapter = function (loader) {
                    return {
                        upload: function () {
                            return loader.file
                                .then(function (file) {
                                    return new Promise(function (resolve, reject) {
                                        // Init request
                                        var xhr = new XMLHttpRequest();
                                        xhr.open('POST', '{{ route('admin.bonus.storeCKEditorImages') }}', true);
                                        xhr.setRequestHeader('x-csrf-token', window._token);
                                        xhr.setRequestHeader('Accept', 'application/json');
                                        xhr.responseType = 'json';

                                        // Init listeners
                                        var genericErrorText = `Couldn't upload file: ${file.name}.`;
                                        xhr.addEventListener('error', function () {
                                            reject(genericErrorText)
                                        });
                                        xhr.addEventListener('abort', function () {
                                            reject()
                                        });
                                        xhr.addEventListener('load', function () {
                                            var response = xhr.response;

                                            if (!response || xhr.status !== 201) {
                                                return reject(response && response.message ? `${genericErrorText}\n${xhr.status} ${response.message}` : `${genericErrorText}\n ${xhr.status} ${xhr.statusText}`);
                                            }

                                            $('form').append('<input type="hidden" name="ck-media[]" value="' + response.id + '">');

                                            resolve({default: response.url});
                                        });

                                        if (xhr.upload) {
                                            xhr.upload.addEventListener('progress', function (e) {
                                                if (e.lengthComputable) {
                                                    loader.uploadTotal = e.total;
                                                    loader.uploaded = e.loaded;
                                                }
                                            });
                                        }

                                        // Send request
                                        var data = new FormData();
                                        data.append('upload', file);
                                        data.append('crud_id', '{{ $bonu->id ?? 0 }}');
                                        xhr.send(data);
                                    });
                                })
                        }
                    };
                }
            }

            var allEditors = document.querySelectorAll('.ckeditor');
            for (var i = 0; i < allEditors.length; ++i) {
                ClassicEditor.create(
                    allEditors[i], {
                        extraPlugins: [SimpleUploadAdapter]
                    }
                );
            }
        });
    </script>

    <script>
        Dropzone.options.imageDropzone = {
            url: '{{ route('admin.bonus.storeMedia') }}',
            maxFilesize: 2, // MB
            acceptedFiles: '.jpeg,.jpg,.png,.gif',
            maxFiles: 1,
            addRemoveLinks: true,
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            params: {
                size: 2,
                width: 2048,
                height: 2048
            },
            success: function (file, response) {
                $('form').find('input[name="image"]').remove()
                $('form').append('<input type="hidden" name="image" value="' + response.name + '">')
            },
            removedfile: function (file) {
                file.previewElement.remove()
                if (file.status !== 'error') {
                    $('form').find('input[name="image"]').remove()
                    this.options.maxFiles = this.options.maxFiles + 1
                }
            },
            init: function () {
                @if(isset($bonu) && $bonu->image)
                var file = {!! json_encode($bonu->image) !!}
                this.options.addedfile.call(this, file)
                this.options.thumbnail.call(this, file, file.preview ?? file.preview_url)
                file.previewElement.classList.add('dz-complete')
                $('form').append('<input type="hidden" name="image" value="' + file.file_name + '">')
                this.options.maxFiles = this.options.maxFiles - 1
                @endif
            },
            error: function (file, response) {
                if ($.type(response) === 'string') {
                    var message = response //dropzone sends it's own error messages in string
                } else {
                    var message = response.errors.file
                }
                file.previewElement.classList.add('dz-error')
                _ref = file.previewElement.querySelectorAll('[data-dz-errormessage]')
                _results = []
                for (_i = 0, _len = _ref.length; _i < _len; _i++) {
                    node = _ref[_i]
                    _results.push(node.textContent = message)
                }

                return _results
            }
        }

    </script>
@endsection
