<?php

namespace App\Http\Requests;

use App\Models\Bonus;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class UpdateBonuRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('bonu_edit');
    }

    public function rules()
    {
        return [
            'uuid' => [
                'string',
                'required',
                'unique:bonus,uuid,' . request()->route('bonu')->id,
            ],
            'name' => [
                'string',
                'nullable',
            ],
            'category' => [
                'required',
            ],
            'priority' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'ordering' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'delay' => [
                'nullable',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
            'start_at' => [
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
                'nullable',
            ],
            'end_at' => [
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
                'nullable',
            ],
            'timezone' => [
                'string',
                'required',
            ],
            'site_id' => [
                'required',
                'integer',
            ],
            'function_name' => [
                'string',
                'nullable',
            ],
            'sourceid' => [
                'string',
                'nullable',
            ],
        ];
    }
}
