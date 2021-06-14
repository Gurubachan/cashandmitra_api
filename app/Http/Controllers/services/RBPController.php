<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\cms\PinCodeController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AESCrypt;
use App\Models\cms\AepsCustomers;
use App\Models\cms\BankList;
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
                'stateId'=>'required|string',
                'districtId'=>'required|string',
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],500);
            }
            $user=Auth::user();


            $service= new ServiceController();
            $response=$service->getuserwiseService(Auth::user()->id,16,1);

            if(isset($response[0]->onBoardReferance) && $response[0]->onBoardReferance!=""){
                    $eKycOnboardResponse=$this->ekyconboarding($response[0]->onBoardReferance);

                    if($eKycOnboardResponse['response']){
                        $data=$eKycOnboardResponse['data'];
                        if($data->isSuccess && $data->statusCode==000){
                            $eKycOtp= $this->ekycotp($response[0]->onBoardReferance);
                            //logger($eKycOtp);
                            if($eKycOtp['response']){
                                return response()->json([
                                    'response'=>$eKycOtp['data']->isSuccess,
                                    'message'=>$eKycOtp['data']->message,
                                    'data'=>$eKycOtp['data']->data
                                ]);
                            }else{
                                return response()->json(['response'=>false,'message'=>$eKycOtp['message'],'data'=>$eKycOtp['data']]);
                            }
                        }else{
                            return response()->json(['response'=>false,'message'=>$data->message,'data'=>$data]);
                        }
                    }else{
                        return response()->json(['response'=>false,'message'=>$eKycOnboardResponse['message'],'data'=>$eKycOnboardResponse['data']]);
                    }
            }else {
                $pcc = new PinCodeController();
                $pinCode = $pcc->getPinCode($user->pincode);
                //logger($pinCode);
                $onBoarding = array(
                    'name' => $user->fname . ' ' . $user->lname,
                    'emailId' => $user->email,
                    'mobileNo' => $user->contact,
                    'shopName' => isset($inputs['shopName']) ? $inputs['shopName'] : "CASHAND Point",
                    'address1' => $user->address,
                    'address2' => $pinCode->village . ', ' . $pinCode->poName . ', ' . $pinCode->SubDistrict . ', ' . $pinCode->district . ', ' . $pinCode->state . ', ' . $pinCode->pinCode,
                    'pincode' => $pinCode->pinCode,
                    'aadhaarNo' => $user->aadhaar,
                    'panNo' => $user->panNo,
                    'stateId' => $inputs['stateId'],
                    'districtId' => $inputs['districtId']
                );

                /*Save to onboarding table*/

                $onboardData = array(
                    'userId' => Auth::user()->id,
                    'serviceId' => 16,
                    'providerId' => 4,
                    'requested_data' => $onBoarding
                );
                $onboard = BCOnboarding::updateOrCreate(
                    [
                        'userId' => Auth::user()->id,
                        'serviceId' => 16,
                        'providerId' => 4
                    ],
                    $onboardData
                );
                $url = $this->base_url . 'Onboarding/merchantRegistration';
                $encryptPostData = array('data' => $this->encryption($onBoarding));
                $onBoardingResponse = curl($url, 'POST', json_encode($encryptPostData), $this->header);

                $updateOnboard = BCOnboarding::find($onboard->id);
                $updateOnboard->response_data = $onBoardingResponse['data'];

                //logger($onBoardingResponse);
                if ($onBoardingResponse['response']) {
                    $rbpOnboard = $onBoardingResponse['data'];
                    if ($rbpOnboard->isSuccess) {
                        $updateOnboard->bcId = $rbpOnboard->data->merchant_Id;

                        $myServiceUpdate = UserWiseService::where('userId', '=', Auth::user()->id)
                            ->where('serviceId', '=', 16)
                            ->where('isActive', '=', true)
                            ->update([
                                'onBoardReferance' => $rbpOnboard->data->merchant_Id,
                                'remark' => $rbpOnboard->data->statusDescription,
                                'onboarded' => true,
                            ]);
                        $updateOnboard->save();
                        return response()->json(['response' => true, 'message' => $rbpOnboard->data->statusDescription]);
                    } else {
                        $updateOnboard->save();
                        return response()->json(['response' => false, 'message' => $rbpOnboard->message], 500);
                    }
                } else {
                    $updateOnboard->save();
                    return response()->json(['response' => false, 'message' => $onBoardingResponse['data']->message]);
                }
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage(),'errorLine'=>$exception->getLine()],500);
        }
    }

    public function ekyconboarding($merchantId){
        try {
            if(isset($merchantId)){
                $data=array('merchant_id'=>$merchantId);
                $url=$this->base_url.'Onboarding/merchantekyconboarding';
                $encryptPostData=array('data'=>$this->encryption($data));
                return $serverResponse = curl($url,'POST',json_encode($encryptPostData),$this->header);
            }else{
                return ['response'=>false,'message'=>'Invalid merchant id'];
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage(),'errorLine'=>$exception->getLine()],500);
        }
    }

    public function ekycotp($merchantId){
        try {
            if(isset($merchantId)){
                $data=array('merchant_id'=>$merchantId);
                $url=$this->base_url.'CustomerAeps/merchantekycotp';
                $encryptPostData=array('data'=>$this->encryption($data));
                return $serverResponse = curl($url,'POST',json_encode($encryptPostData),$this->header);
            }else{
                return ['response'=>false,'message'=>'Invalid merchant id'];
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage(),'errorLine'=>$exception->getLine()],500);
        }
    }

    public function otpResend(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $validator=Validator::make($input,[
                'ekycPrimaryKeyId'=>"required|string",
                'ekycTxnId'=>"required|string"
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $service= new ServiceController();
            $response=$service->getuserwiseService(Auth::user()->id,16,1);
            $postData=array(
                'ekycPrimaryKeyId'=>$input['ekycPrimaryKeyId'],
                'ekycTxnId'=>$input['ekycTxnId'],
                'merchant_id'=>$response[0]->onBoardReferance
            );
            $url=$this->base_url.'CustomerAeps/merchantekycotpresend';
            $encryptPostData=json_encode(array('data'=>$this->encryption($postData)));
            $resendOtpResponse=curl($url,'POST',$encryptPostData,$this->header);
            if($resendOtpResponse['response']){
                $data=$resendOtpResponse['data'];
                return response()->json(['response'=>$data->isSuccess,'message'=>$data->message,'data'=>$data->data]);
            }else{
                return response()->json($resendOtpResponse);
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage(),'errorLine'=>$exception->getLine()],500);
        }
    }

    public function eKycProcess(Request $request){
        try {
            $input=json_decode($request->getContent(),true);
            $validator=Validator::make($input,[
                'otp'=>"required",
                'ekycPrimaryKeyId'=>"required",
                'ekycTxnId'=>"required",
                'fingerprintData'=>'required'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $service= new ServiceController();
            $response=$service->getuserwiseService(Auth::user()->id,16,1);

            $postData=array(
                'otp'=>$input['otp'],
                'ekycPrimaryKeyId'=>$input['ekycPrimaryKeyId'],
                'ekycTxnId'=>$input['ekycTxnId'],
                'fingerprintData'=>$input['fingerprintData'],
                'merchant_Id'=>$response[0]->onBoardReferance
            );
            $url=$this->base_url.'CustomerAeps/merchantekycproccess';
            $encryptedData=json_encode(array('data'=>$this->encryption($postData)));
            $eKycResponse=curl($url,'POST',$encryptedData,$this->header);

            if($eKycResponse['response']){
                $data=$eKycResponse['data'];
                return response()->json(['response'=>$data->isSuccess,'message'=>$data->message,'data'=>$data->data]);
            }else{
                return response()->json($eKycResponse);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage(),'errorLine'=>$exception->getLine()],500);
        }
    }
    public function status(Request $request){
        try {
            $service= new ServiceController();
            $response=$service->getuserwiseService(Auth::user()->id,16,1);
            $postData=array(
                'merchant_id'=>$response[0]->onBoardReferance,
                'emailId'=>Auth::user()->email,
                'mobileNo'=>Auth::user()->contact
            );
            $url=$this->base_url.'Onboarding/merchantStatus';
            $encryptData=json_encode(array('data'=>$this->encryption($postData)));
            $statusResponse=curl($url,'POST',$encryptData,$this->header);
            if($statusResponse['response']){
                $data=$statusResponse['data'];
                /*
                 * Update User wise service
                 */

                $status=array('PK'=>'pending','A'=>'active','R'=>'rejected','D'=>'deactive');
                $updateData=array(
                    'onboardStatus'=>$status[$data->data->statusCode],
                    'remark'=>$data->data->statusDescription
                );
                UserWiseService::updateOrCreate(
                    [
                        'userId' => Auth::user()->id,
                        'serviceId' => 16,
                    ],
                    $updateData
                );
                BCOnboarding::updateOrCreate([
                    'userId'=>Auth::user()->id,
                    'serviceId'=>16,
                    'providerId'=>4
                ],[
                    'status'=>$status[$data->data->statusCode],
                    'response_data'=>$data
                ]);

                return response()->json(['response'=>$data->isSuccess,'message'=>$data->message,'data'=>$data->data]);
            }else{
                return response()->json($statusResponse);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage(),'errorLine'=>$exception->getLine()],500);
        }
    }

    public function customerRegistration(
        int $contact,
        string $name,
        int $pinCode,
        string $merchantId
    ){
        try {
            $url=$this->base_url.'CustomerAeps/Onboarding';
            $postData=array(
                'customermobileNo'=>$contact,
                'merchant_Id'=>$merchantId,
                'customername'=>$name,
                'customerpinCode'=>$pinCode
                );
            logger($postData);
            $encryptData=array('data'=>$this->encryption($postData));

                $newCustomer=curl($url,'POST',json_encode($postData),$this->header);
                logger($newCustomer);
                if($newCustomer['response']){
                    $data=$newCustomer['data'];
                    $newCustomer=$data->data;
                    if($data->isSuccess){
                         $response=AepsCustomers::upateOrCreate(
                            ['contact'=>$contact],
                            [
                                'name'=>$newCustomer->customerName,
                                'pinCode'=>$newCustomer->customerPinCode,
                                'merchantId'=>$newCustomer->merchant_Id,
                                'rbpCustomerId'=>$newCustomer->customerId,
                                'created_at'=>now()
                            ]
                        );
                         return ['response'=>true,'message'=>'Success','data'=>$response];
                    }else{
                        return ['response'=>false,'message'=>$data->message];
                    }
                }else{
                    return ['response'=>false,'message'=>$newCustomer['message']];
                }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getCustomer(Request $request){
        try {
            $input=json_decode($request->getContent(),true);
            $validation=Validator::make($input,[
                'contact'=>'required|integer|digits:10'
            ]);
            if($validation->fails()){
                return response()->json(['response'=>false,'message'=>$validation->errors()],400);
            }
            $response=AepsCustomers::where('contact',$input['contact'])->get();
            if(count($response)>0){
                return response()->json(['response'=>true,'message'=>'Existing Customer','data'=>$response]);
            }else{
                return response()->json(['response'=>false,'message'=>'Customer not found']);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function bankIIN(Request $request){
        try {
            $data=BankList::orderBy('bankName','ASC')->get();
            if($data->count()>0){
                return response()->json(['response'=>true,'message'=>"Success",'data'=>$data]);
            }else{
                $response=$this->updateBankList();
                return response()->json($response);
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function updateBankList(){
        try {
            $url=config('keys.rbpfinivis.url').'Common/acquireBankIIN';
            $responseBank= curl($url,'GET',null,$this->header);
            if($responseBank['response']){
                $data=$responseBank['data'];
                $insertData=array();
                if($data->isSuccess){
                    foreach ($data->data as $bl){
                        $insertData[]=array(
                            'bankiin'=>$bl->bankIin,
                            'bankName'=>$bl->bankName,
                            'created_at'=>now()
                        );
                    }
                    BankList::truncate();
                    BankList::insert($insertData);
                    return ['response'=>true,'message'=>"Success",'data'=>$data->data];
                }else{
                    return ['response'=>false,'message'=>$data->message];
                }
            }else{
                return ['response'=>false,'message'=>$responseBank['message']];
            }
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }

    public function transaction(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $service= new ServiceController();
            $myService=$service->getuserwiseService(Auth::user()->id,16,1);

            $validator=Validator::make($input,[
                'customerId'=>'required|string',
                'Amount'=>'required',
                'aadhaarNo'=>'required|integer|digits:12',
                'bankIIN'=>'required',
                'txnCode'=>'required|string',
                'Latitude'=>'required',
                'Longitude'=>'required',
                'fingerData'=>'required'
            ]);

            if(isset($input['customerId']) && $input['customerId']!="" && $input['customerId']!=null){
                $validator=Validator::make($input,[
                    'customerId'=>'required|string'
                ]);

            }else{
                $validator=Validator::make($input,[
                    'customerContact'=>"required|integer|digits:10",
                    'customerName'=>'required|string',
                    'customerPin'=>'required|integer|digits:6'
                ]);
            }
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }

            if(!isset($input['customerId']) && $input['customerId']=="" && $input['customerId']==null){
                $newCustomer=$this->customerRegistration($input['customerContact'],$input['customerName'],$input['customerPin'],$myService[0]->onBoardReferance);
                if($newCustomer['response']){
                    $customerId=$newCustomer['data'][0]->rbpCustomerId;
                }else{
                    return response()->json(['response'=>false,'message'=>"Unable to create new customer.".$newCustomer['message']]);
                }
            }else{
                $customerId=$input['customerId'];
            }
            $saveData=array(
                'customerId'=>$customerId,
                'Amount'=>$input['Amount'],
                'aadhaarNo'=>$input['aadhaarNo'],
                'merchant_id'=>$myService[0]->onBoardReferance,
                'bankIIN'=>$input['bankIIN'],
                'PipeName'=>'sbi',
                'txnCode'=>$input['txnCode'],
                'Latitude'=>$input['Latitude'],
                'Longitude'=>$input['Longitude'],
                'fingerData'=>$input['fingerData']
            );

            return response()->json(['response'=>true,'message'=>'Success','data'=>$saveData]);
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
