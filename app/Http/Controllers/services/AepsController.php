<?php

namespace App\Http\Controllers\services;

use App\Events\ICICI\CheckTransactionStatusEvent;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Controller;

use App\Models\services\BCOnboarding;
use App\Models\services\ICICIAEPSTransaction;
use App\Models\services\UserWiseService;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

            /*
             * Prepare file for BC on boarding
             */
            $aadhaarImage=explode(',' , $inputs['aadhaarimage']);
            $panImage=explode(',' , $inputs['panimage']);
            $onBoarding=array(
                'bc_f_name'=>$user['data'][0]['fname'],
                'bc_m_name'=>($user['data'][0]['mname']!=null)?$user['data'][0]['mname']:"",
                'bc_l_name'=>$user['data'][0]['lname'],
                'emailid'=>$user['data'][0]['email'],
                'phone1'=>$user['data'][0]['contact'],
                'phone2'=>$user['data'][0]['contact'],
                'bc_dob'=>date("d-m-Y", strtotime($user['data'][0]['dob'])),
                'bc_state'=>$inputs['state'],
                'bc_district'=>$inputs['district'],
                'bc_address'=>$user['data'][0]['address'],
                'bc_block'=>$user['data'][0]['SubDistrict'],
                'bc_city'=>$user['data'][0]['SubDistrict'],
                'bc_landmark'=>$user['data'][0]['address'],
                'bc_loc'=>$user['data'][0]['village'],
                'bc_mohhalla'=>$user['data'][0]['SubDistrict'],
                'bc_pan'=>$user['data'][0]['panNo'],
                'bc_pincode'=>$user['data'][0]['PinCode'],
                'shopname'=>$inputs['shopName'],
                "kyc1"=>$aadhaarImage[1],
                "kyc2"=>$panImage[1],
                "kyc3"=>"",
                "kyc4"=>"",
                'saltkey'=>config('keys.mahagram.salt'),
                'secretkey'=>config('keys.mahagram.secret'),
                'shopType'=>$inputs['shopType'],
                'qualification'=>$inputs['qualification'],
                'population'=>$inputs['population'],
                'locationType'=>$inputs['shopLocation'],
            );
            $onboard = new BCOnboarding();
            $onboard->userId=Auth::user()->id;
            $onboard->serviceId=1;
            $onboard->providerid=2;
            $onboard->requested_data=$onBoarding;

            $onboard->save();
            $url=config('keys.mahagram.baseurl')."AEPS/APIBCRegistration";
            $response=curl($url,"POST",json_encode($onBoarding));

            //return response()->json(['response'=>true,'message'=>$response,'data'=>$onboard,'test'=>gettype($response)]);
//            $response['data'][0]->Message == "Success" &&
           /* if($response['response']== true){*/
            $updateOnboard= BCOnboarding::find($onboard->id);
            $updateOnboard->response_data=$response['data'];
            $updateOnboard->save();
                $myServiceUpdate=UserWiseService::where('userId','=',Auth::user()->id)
                    ->where('serviceId','=',1)
                    ->where('isActive','=',true)
                    ->update([
                        'onBoardiReferance'=>$response['data'][0]->bc_id,
                        'remark'=>'Waiting for bank verification',
                        'onboarded'=>true,
                    ]);

                return response()->json([
                    'response'=>true,
                    'message'=>$response['data'][0]->Message,
                    'data'=>$updateOnboard]
                );
           /* }else{
                return response()->json([
                    'response'=>false,
                    'message'=>$response['data'][0]->Message,
                    ]
                );
            }*/


        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function iciciKYCStatusCheck(Request $request){
        try {
            $url=config('keys.mahagram.baseurl')."AEPS/APIBCStatus";
            $myService=UserWiseService::where('userId','=',Auth::user()->id)
                ->where('serviceId','=',1)
                ->get();
            $postData=array(
                'bc_id'=>$myService[0]->onBoardReferance,
                'saltkey'=>config('keys.mahagram.salt'),
                'secretkey'=>config('keys.mahagram.secret')
                );

            $response=curl($url,'POST',json_encode($postData));
            if($response['response']){
                $updateResponse=UserWiseService::find($myService[0]->id);
                $updateResponse->remark=$response[0]->remarks;
                $updateResponse->onboardStatus=strtolower($response[0]->status);
                $updateResponse->save();
                return response()->json(['response'=>true,'message'=>$response]);
            }else{
                return response()->json(['response'=>false,'message'=>$response['message']],$response['response_code']);
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function initTransaction(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            /*$validation=Validator::make($input,['myIp'=>'required|ip']);
            if($validation->fails()){
                return response()->json(['response'=>false,'message'=>$validation->errors()],400);
            }*/
            $bcData=UserWiseService::select('onBoardReferance')
                ->where('userId','=', Auth::user()->id)
                ->where('serviceId','=',1)
                ->where('isActive','=', true)
                ->where('onboardStatus','=', 'active')
                ->where('onBoardReferance','<>','')
                ->whereNotNull('onBoardReferance')
                ->get();
            if(count($bcData)==1){
                $postData= array(
                    'bc_id'=>$bcData[0]->onBoardReferance,
                    'phone1'=>Auth::user()->contact,
                    //'ip'=>$input['myIp'],
                    'ip'=>request()->ip(),
                    'userid'=>Auth::user()->id,
                    'saltkey'=>config('keys.mahagram.salt'),
                    'secretkey'=>config('keys.mahagram.secret')
                );
                $url=config('keys.mahagram.baseurl')."AEPS/BCInitiate";
                $curlResponse= curl($url,'POST',json_encode($postData));
                if($curlResponse['response']){
                    return response()->json(['response'=>true,'message'=>'BC Authenticated successfully.', 'data'=>$curlResponse['data']]);
                }else{
                    return response()->json(['response'=>false,'message'=>$curlResponse['message']],$curlResponse['response_code']);
                }

            }else{
                return response(['response'=>false,'message'=>'You are not authorized'],401);
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
/* This method access by mahagram on transaction initiate */
    public function checkStatus(Request $request){
        try {
            if($request->method()== "GET"){
                $input=$request->all();
            }else{
                $input=json_decode($request->getContent(), true);
            }
            $icici = new ICICIAEPSTransaction();
            if($input['Txntype']=="AP"){
                $icici->serviceId=14;
            }else{
                $icici->serviceId=1;
            }

            $icici->txnType=$input['Txntype'];
            $icici->txnTime=date("Y-m-d H:i:s", strtotime($input['Timestamp']));
            $icici->bcId=$input['BcId'];
            $icici->userId=$input['TerminalId'];
            $stanNo=explode("|",$input['TransactionId']);
            $icici->stanNo=$stanNo[0];
            $icici->aadhar=$stanNo[1];
            $icici->amount=$input['Amount'];
            $icici->status=$input['TxnStatus'];
            $icici->iin=$input['BankIIN'];
            $icici->txnMedium=$input['TxnMedium'];
            $icici->mobile=$input['EndCustMobile'];
            $icici->response=$input;
            $icici->response_at=date("Y-m-d H:i:s");
            $icici->save();

            return response()->json(['STATUS'=>"SUCCESS",'MESSAGE'=>'Success','TRANSACTION_ID'=>$icici->id,"VENDOR_ID"=>$icici->userId]);
            /*return josn_encode(['STATUS'=>"SUCCESS",'MESSAGE'=>'Success','TRANSACTION_ID'=>$icici->id,"VENDOR_ID"=>$icici->userId]);

            return ['STATUS'=>"SUCCESS",'MESSAGE'=>'Success','TRANSACTION_ID'=>$icici->id,"VENDOR_ID"=>$icici->userId];*/
        }catch (\Exception $exception){
            return response()->json(['STATUS'=>'FAILED','MESSAGE'=>$exception->getMessage()],500);
        }
    }
    public function updateStatus(Request $request){
        try {
            if($request->method()== "GET"){
                $input=$request->all();
            }else{
                $input=json_decode($request->getContent(), true);
            }
            $icici = ICICIAEPSTransaction::find($input['TransactionId']);
            $icici->status=$input['Status'];
            $icici->rrn=$input['rrn'];
            $icici->update_response=$input;
            $icici->update_response_at=date("Y-m-d H:i:s");
            $icici->save();
            event(New CheckTransactionStatusEvent($icici));
            return response()->json(['STATUS'=>'SUCCESS','MESSAGE'=>'Update Successfully!!']);
        }catch (\Exception $exception){
            return response()->json(['STATUS'=>'FAILED','MESSAGE'=>$exception->getMessage()],500);
        }
    }

    public function checkAePSTxnStatus($transactionId){
        try {

            $icici= ICICIAEPSTransaction::findOrFail($transactionId);


                $url=config('keys.mahagram.baseurl')."Common/CheckAePSTxnStatus";
                $postData=array(
                    'saltkey'=>config('keys.mahagram.salt'),
                    'secretkey'=>config('keys.mahagram.secret'),
                    'stanno'=>$icici->stanNo
                );
                $result=curl($url,'POST',json_encode($postData));
                //return $result['data']->statuscode;
                if($result['data']->statuscode == "000" ){

                    $data=$result['data']->Data[0];
                    $icici->bankName=$data->bankname;
                    $icici->bank_response_message=$data->bankresponsemessage;
                    $icici->bank_code=$data->bankcode;
                    $icici->bank_message=$data->bankmessage;
                    $icici->txn_from=$data->txnfrom;
                    $icici->routeType=$data->routetype;
                    if($data->refunddate!=""){
                        $icici->refundDate=$data->refunddate;
                    }
                    $icici->remarks=$data->remarks;
                    $icici->checkAEPSTxnStatus_response=$data;
                    $icici->checkAEPSTxnStatus_response_at=date("Y-m-d H:i:s");
                    $icici->save();
                    return response()->json(['response'=>true,'message'=>'Checked completed.']);
                }else{
                    return response()->json($result);
                }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function walletUpdatedFromICICIAEPS($transactionData, $updateWallet){
        try {
            $aepsICICI=ICICIAEPSTransaction::find($transactionData->id);
            $aepsICICI->isWalletUpdate=true;
            $aepsICICI->walletReferenceNo=$updateWallet->id;
            $aepsICICI->save();
            return $aepsICICI;
        }catch (\Exception $exception){
            logger($exception);
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function getMyTransactionSummary(Request $request){
        try {

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function myAepsTransaction(Request $request){
        try {
            $input=json_decode($request->getContent(),true);
            $startDate=date("Y-m-d",strtotime($input['startDate']));
            $endDate=date("Y-m-d",strtotime($input['endDate']));
            if(in_array(Auth::user()->role,config('constants.admin'))){
                $query= ICICIAEPSTransaction::select('*');
            }else{
                $query= ICICIAEPSTransaction::where('userId','=', Auth::user()->id);
            }

                if(isset($input['type']) && isset($input['status'])){
                    if($input['type']!="all"){
                        $query->where('txnType',$input['type']);
                    }
                    if($input['status']!="all"){
                        $query->where('status',$input['status']);
                    }
                }
            $aeps=$query
                ->whereBetween(DB::raw('date(`created_at`)'), [$startDate, $endDate])
                ->orderby('id','desc')
                ->simplePaginate()
            ;
            //return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$data]);
            if(count($aeps)>0){
                return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$aeps]);
            }else{
                return response()->json(['response'=>false,'message'=>'No transaction found.'],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function eventTest($transactionId){
        try {
            $icici= ICICIAEPSTransaction::findOrFail($transactionId);
            event(New CheckTransactionStatusEvent($icici));
            return response()->json(['response'=>true,'message'=>'Event Generated at ' . now()]);

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
