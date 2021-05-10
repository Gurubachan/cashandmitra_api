<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use App\Models\cms\SMS;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SMSController extends Controller
{
    public function sendOTP(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);

            $validator=Validator::make($inputs,[
                'contact'=>'required|numeric|digits:10'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $api_key = '45D2FE503A9FF9';
            $contacts = $inputs['contact'];
            $from = 'EVLOTP';
            $otp=rand(100000,999999);
            $sms_text = urlencode('Your one time password is - ' .$otp);

//Submit to server

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, "http://sms.thinksimple.co.in/app/smsapi/index.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api_key."&campaign=8150&routeid=7&type=text&contacts=".$contacts."&senderid=".$from."&msg=".$sms_text);
            $response = curl_exec($ch);
            curl_close($ch);
           //  $response="cashand/".$otp;
            $isDelivered=false;
            if(substr($response,0,3)=="ERR"){
                $explode=explode(":",$response);
                //print_r($explode);
            }else{
                $explode=explode("/",$response);
                $isDelivered=true;
            }

            $ctime= strtotime(date('Y-m-d H:i:s'));
            $newTime=$ctime+300;

            if($isDelivered){
                $sendMessage= new SMS();
                $sendMessage->id=$explode[1];
                $sendMessage->contactnumber=$contacts;
                $sendMessage->otp=$otp;
                $sendMessage->isdelivered=true;
               // $sendMessage->experytime=date("Y-m-d H:i:s", $newTime);
                $sendMessage->experytime=$newTime;
                $sendMessage->save();
                return response()->json(['response'=>true,
                    'message'=>"Message sent successfully.",
                    'data'=>['expiryTime'=>$newTime,'contact'=>$contacts]]
                );
            }else{
                return response()->json(['response'=>false,
                    'message'=>$response
                    ]
                );
            }


        }catch (\Exception $exception){
            return response()->json(
                [
                    'response'=>false,
                    'message'=>$exception->getMessage()
                ],500);
        }
    }

    public function verifyOtp(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);

            $validator=Validator::make($inputs,[
                'otp'=>'required|numeric|digits:6',
                /*'expiryTime'=>'required|numeric',*/
                'contact'=>'required|numeric|digits:10'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false, 'message'=>$validator->errors()],400);
            }
            $otp=SMS::where('otp','=',$inputs['otp'])
                ->where('experytime','>=',strtotime(date("Y-m-d H:i:s")))
                ->where('contactnumber','=',$inputs['contact'])

                ->where('isdelivered', true)
                ->update([
                    'isverified'=>1,
                    'verifiedtime'=>date("Y-m-d H:i:s"),
                    'updated_at'=>date("Y-m-d H:i:s")
                ]);
            if($otp){
                //get user details if exist
                if(isset(Auth::user()->id)){
                    $check_user=User::where('contact','=',$inputs['contact'])
                        ->update(['contact_verified_at'=>date("Y-m-d H:i:s")]);
                    if($check_user){
                        return response()->json(['response'=>true,'message'=>'OTP Verified','data'=>User::find(Auth::user()->id)]);
                    }else{
                        return response()->json(['response'=>false,'message'=>'Unable to verify contact number']);
                    }
                }else{
                    return response()->json(['response'=>true,'message'=>'OTP Verified']);
                }
            }else{
                return response()->json(['response'=>false,'message'=>'Unable to verified OTP', 'data'=>strtotime(date("Y-m-d H:i:s"))],401);
            }
        }catch (\Exception $exception){
            return response()->json(
                [
                    'response'=>false,
                    'message'=>$exception->getMessage()
                ],500);
        }
    }

    public function sendSMS($contact, $message){
        try {
            if(isset($contact) &&
                is_numeric($contact) &&
                isset($message) &&
                is_string($message)
            ){
                $api_key = config('keys.sms.apikey');
                $contacts = $contact;
                $from = 'CASHND';

                $sms_text = urlencode($message);
                $postData="?key=".$api_key."&campaign=8150&routeid=7&type=text&contacts=".$contacts."&senderid=".$from."&msg=".$sms_text;
                $api_url=config('keys.sms.url').$postData;
                $response=file_get_contents($api_url);
                return $response;
            } else{
                return ['response'=>false,'message'=>'Invalid contact or message'];
            }
        }catch (\Exception $exception){
            return response()->json(
                [
                    'response'=>false,
                    'message'=>$exception->getMessage()
                ],500);
        }
    }
}
