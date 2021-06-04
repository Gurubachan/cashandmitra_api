<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Http\Controllers\AESCrypt;
use App\Models\services\BCOnboarding;
use App\Models\services\UserWiseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RBPController extends Controller
{
    protected $header=array();
    protected $error_message=null;
    protected $base_url=null;
    public function __construct(){
        $this->base_url=config('keys.rbpfinivis.url');
        $authResponse=$this->authorisation();
        if($authResponse['response'] && $authResponse['data']->isSuccess){
            $this->header=array('Authorization: Bearer '.$authResponse['data']->data->token);
        }else{
            $this->error_message=$authResponse['data']->message;
        }

    }
    public function authorisation(){
        try{
                $url=config('keys.rbpfinivis.url').'Signature/authorize';
                $secretKey="secretKey:".config('keys.rbpfinivis.secretKey');
                $saltKey="saltKey:".config('keys.rbpfinivis.saltKey');
                $encryptDecryptKey="encryptdecryptKey:".config('keys.rbpfinivis.encryptdecryptKey');
                $otherData=array($secretKey,$saltKey,$encryptDecryptKey);
                return curl($url,'POST',null,$otherData);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function state(Request $request){
        try {
            $url=$this->base_url.'Common/acquireState';
            return curl($url,'GET',null,$this->header);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function district(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs,[
                'stateId'=>'required'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $url=$this->base_url.'Common/acquireDistrictViaState';
            $postData=array('stateId'=>$inputs['stateId']);
            $encryptPostData=array('data'=>$this->encryption($postData));

            return curl($url,'POST',json_encode($encryptPostData),$this->header);

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function registration(Request $request){
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

            $onBoarding=array(
                'name'=>'',
                'email'=>'',
                'mobileNo'=>'',
                'shopName'=>'',
                'address1'=>'',
                'address2'=>'',
                'pincode'=>'',
                'aadhaarNo'=>'',
                'panNo'=>'',
                'stateId'=>'',
                'districtId'=>''
            );

            /*Save to onboarding table*/
            $onboard = new BCOnboarding();
            $onboard->userId=Auth::user()->id;
            $onboard->serviceId=15;
            $onboard->providerid=4;
            $onboard->requested_data=$onBoarding;
            $onboard->save();

            $url=$this->base_url.'Onboarding/merchantRegistration';
            $encryptPostData=array('data'=>$this->encryption($onBoarding));
            $onBoardingResponse=curl($url,'POST',json_encode($encryptPostData),$this->header);

            $updateOnboard= BCOnboarding::find($onboard->id);
            $updateOnboard->response_data=$onBoardingResponse['data'];


            if($onBoardingResponse['response']){
                if($onBoardingResponse['response']['data']->isSuccess){
                    $updateOnboard->bcId=$onBoardingResponse['data']->data->merchant_Id;

                    $myServiceUpdate=UserWiseService::where('userId','=',Auth::user()->id)
                        ->where('serviceId','=',16)
                        ->where('isActive','=',true)
                        ->update([
                            'onBoardiReferance'=>$onBoardingResponse['data']->data->merchant_Id,
                            'remark'=>$onBoardingResponse['data']->data->statusDescription,
                            'onboarded'=>true,
                        ]);

                }
            }
            $updateOnboard->save();
            return response()->json([
                    'response'=>true,
                    'message'=>$onBoardingResponse['data']->data->statusDescription,
                    'data'=>$updateOnboard]
            );

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function status(Request $request){
        try {

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function customer_registration(Request $request){
        try {

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function bankIIN(Request $request){
        try {
            $url=config('keys.rbpfinivis.url').'Common/acquireBankIIN';
            return curl($url,'GET',null,$this->header);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function transaction(Request $request){
        try {

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function encryption(Array $data){
        try {
            $crypt= new AESCrypt("abcd");
            return $crypt->encryptText(json_encode($data));
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }

    public function decryption(String $data){
        try {
            $crypt= new AESCrypt("abcd");
            return $crypt->decryptCipher($data);
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }
}
