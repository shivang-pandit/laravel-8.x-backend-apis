<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use RestResponseFactory;

class AdminController extends Controller
{
    /**
     * @param AdminLoginRequest $request
     * @return string JSON
     */
    public function login(AdminLoginRequest $request)
    {
        if (Auth::guard('admin')->attempt(['email' => $request['email'], 'password' => $request['password']])) {
            $admin = Auth::guard('admin')->user();
            $token = $admin->createToken('loan-app', ['admin'])->plainTextToken;
            $admin['token'] = $token;
            return RestResponseFactory::success($admin, 'Login successful')->toJSON();
        }
    }
    
    /**
     * @return string JSON
     */
    public function profile()
    {
        $admin = Auth::user();
        if (!$admin) {
            return RestResponseFactory::not_found([], 'User not found.')->toJSON();
        }
        return RestResponseFactory::success($admin, 'Admin Profile.')->toJSON();
    }
}
