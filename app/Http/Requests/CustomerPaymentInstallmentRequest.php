<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CustomerPaymentInstallmentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'amount' => 'required|numeric|gt:0',
        ];
    }


    /**
     * return validation messages.
     * @param $validator
     * @return void
     */
    public function failedValidation(Validator $validator)
    {
        $errorsArr = array();

        if ($validator->fails()) {
            $errorsArr = array_merge($errorsArr, $validator->messages()->all(':message'));
        }

        if (count($errorsArr) > 0) {
            throw new HttpResponseException(response()->json([
                'code' => 400,
                'status' => 'badrequest',
                'message' => implode("", $errorsArr)
            ]));
        }

    }
}
