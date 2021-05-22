<?php

namespace App\Http\Controllers\bank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class IFSCController extends Controller
{
    public function getBankDetails(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $validator=validator()->make($input,
                ['ifsccode'=>'required|regex:/[A-Z0-9]{11}/']);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://ifsc.razorpay.com/'.$input['ifsccode'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            curl_close($curl);
            if($http_status!=200){
                return response()->json([
                    'response'=>false,
                    'message'=>$http_status,
                ],$http_status) ;
            }else{
                return response()->json([
                    'response'=>true,
                    'message'=>'IFSC Code fetched',
                    'data'=>json_decode($response)
                ]) ;
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }

    }
    /*Get bank list*/
    public function getBankIIN(Request $request){
        try {
           $authResponse=$this->getAuthorisation();
            $url=config('keys.rbpfinivis.url').'Signature/authorize';
            if($authResponse['response'] && $authResponse['data']->isSuccess){}
            $header=array('Authorization: Bearer '. $authResponse['data']->data->token);
           $response=curl($url,'GET',null,$header);
           return $response;
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    /*Get auth key*/
    public function getAuthorisation(){
        try {
            $url=config('keys.rbpfinivis.url').'Signature/authorize';
            $secretKey="secretKey:1234";
            $saltKey="saltKey:456";
            $encryptdecryptKey="encryptdecryptKey:abcd";
            $otherData=array($secretKey,$saltKey,$encryptdecryptKey);
            $response=curl($url,'POST',null,$otherData);
            //logger($response);
            return $response;
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
