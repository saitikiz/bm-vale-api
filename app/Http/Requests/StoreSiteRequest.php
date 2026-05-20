<?php

namespace App\Http\Requests;

use App\Models\Site;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreSiteRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('site_create');
    }

    public function rules()
    {
        return [
            'uuid' => [
                'string',
                'nullable',
            ],
            'name' => [
                'string',
                'required',
            ],
            'token' => [
                'string',
                'required',
            ],
        ];
    }
}
