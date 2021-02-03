<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use App\Models\cms\CallStatus;
use App\Models\cms\PinCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PinCodeController extends Controller
{
    public function index(){
        try {

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()]);
        }
    }

    public function fetch($pinCode){
        try {
            $pinCode= PinCode::where("pinCode",$pinCode)
                ->orderBy('village','ASC')
                ->get();
            if(count($pinCode)>0){
                return response()->json([
                    'response'=>true,
                    "message"=>count($pinCode)." Pin Code Details fetched",
                    "data"=>$pinCode
                ],200);
            }else{
                return response()->json([
                    'response'=>false,
                    "message"=>"Wrong Pin Code Enter",
                ],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,"message"=>$exception->getMessage()]);
        }
    }

    public function getState(){
        try {
            $ch=curl_init();
            curl_setopt($ch,CURLOPT_URL, "http://uat.dhansewa.com/Common/GetState");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);
            return $response;
        }catch (\Exception $exception) {
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function getDistrict(Request $request){
        try {
            $inputs=json_decode($request->getContent(), true);
            $validator=Validator::make($inputs,[
                'stateid'=>'required|integer'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $postdata=array('stateid'=>$inputs['stateid']);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://uat.dhansewa.com/Common/GetDistrictByState',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS =>json_encode($postdata),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            return $response;
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getCallStatus(){
        try {
            return response()->json(['response'=>true,'message'=>'Call Status fetched','data'=>CallStatus::all()]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
