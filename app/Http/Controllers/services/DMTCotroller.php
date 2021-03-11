<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class DMTCotroller extends Controller
{
    private $url="http://34.93.39.198:8080";
    public function getToken(){
        try {
            $postData=config('keys.isu_dmt');
            $response=curl($this->url."/getlogintoken.json","POST", json_encode($postData));
            return $response;
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function getCustomer(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $validation=Validator::make($input,
                ['mobile'=>'required|digits:10']
            );
            if($validation->fails()){
                return response()->json(['response'=>false,'message'=>$validation->errors()]);
            }

           $response = curl($this->url."/dmt/getcustomer/".$input['contact'],"POST",null);

        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }
}
