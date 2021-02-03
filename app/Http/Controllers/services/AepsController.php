<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Controller;
use http\Client\Curl\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AepsController extends Controller
{
    public function iciciKyc(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs, [
                'shopName'=>'required|string|min:3|max:25',
                'state'=>'required|integer',
                'district'=>'required|integer',
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
           $auth= new AuthController();
            $userResponse=$auth->getUser();
            $user = json_decode($userResponse->getContent(),true);
            return response()->json($user);

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()]);
        }
    }
}
