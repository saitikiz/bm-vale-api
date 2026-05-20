<?php

namespace App\Http\Requests;

use App\Models\BonusRequest;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StoreBonusRequestRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('bonus_request_create');
    }

    public function rules()
    {
        return [
            'uuid' => [
                'string',
                'required',
                'unique:bonus_requests',
            ],
            'customer_username' => [
                'string',
                'nullable',
            ],
            'customer_code' => [
                'string',
                'nullable',
            ],
            'customerid' => [
                'string',
                'nullable',
            ],
            'source' => [
                'required',
            ],
            'ip' => [
                'string',
                'nullable',
            ],
            'status_reason' => [
                'string',
                'nullable',
            ],
            'note' => [
                'string',
                'nullable',
            ],
            'locked_at' => [
                'date_format:' . config('panel.date_format') . ' ' . config('panel.time_format'),
                'nullable',
            ],
            'retry_count' => [
                'required',
                'integer',
                'min:-2147483648',
                'max:2147483647',
            ],
        ];
    }
}
