<?php

namespace App\Http\Requests;

use App\Models\Player;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StorePlayerRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('player_create');
    }

    public function rules()
    {
        return [
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
        ];
    }
}
