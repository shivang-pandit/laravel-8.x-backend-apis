<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerLoginRequest;
use Illuminate\Support\Facades\Auth;
use RestResponseFactory;

class CustomerController extends Controller
{
    /**
     * @param CustomerLoginRequest $request
     * @return string JSON
     */
    public function login(CustomerLoginRequest $request)
    {
        if (Auth::guard('customer')->attempt(['email' => $request['email'], 'password' => $request['password']])) {
            $customer = Auth::guard('customer')->user();
            $token = $customer->createToken('loan-app', ['customer'])->plainTextToken;
            $customer['token'] = $token;
            return RestResponseFactory::success($customer, 'Login successful')->toJSON();
        } else {
            return RestResponseFactory::badrequest('Invalid credential')->toJSON();
        }
    }

    /**
     * @return string JSON
     */
    public function profile()
    {
        $customer = Auth::user();
        if (!$customer) {
            return RestResponseFactory::not_found([], 'Customer not found.')->toJSON();
        }
        return RestResponseFactory::success($customer, 'Customer Profile.')->toJSON();
    }
}
