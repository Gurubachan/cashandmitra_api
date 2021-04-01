<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\Commission;
use App\Models\services\ICICIAEPSTransaction;
use App\Models\services\PayoutBankResponse;
use App\Models\services\Wallet;
use App\Models\services\WalletSettelment;
use App\Models\User;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    /*
     * Check user wallet balance
     * */
    public function checkBalance(Request $request){
        try {
            $wallet=Wallet::where('user_id','=',Auth::user()->id)
                ->limit(1)
                ->orderby('id','DESC')
                ->get();
            if(count($wallet)>0){
                return response()->json(['response'=>true,'message'=>"Balance Fetched",'data'=>['balance'=>$wallet[0]->closing_balance]]);
            }else{
                return response()->json(['response'=>false,'message'=>"Balance Fetched",'data'=>['balance'=>0]]);
            }


        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function walletTransaction(Request $request){
        try {
            $input= json_decode($request->getContent(), true);
            $transaction=Wallet::where('user_id', Auth::user()->id)
                ->orderBy('id','DESC')
                ->simplePaginate();
            return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$transaction]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function walletOperationaeps($transactionData){
        try {
            //get user transaction details
            if(($transactionData->txnType == "CW" || $transactionData->txnType == "AP")
                && $transactionData->status == "SUCCESS"
                && $transactionData->isWalletUpdate == false
            ){
           $wallet= Wallet::where('user_id','=',$transactionData->userId)
               ->limit(1)
               ->orderBy('id','DESC')
                ->get();
                if(count($wallet)==1){
                    $previous_balance=$wallet[0]->closing_balance;
                }else{
                    $previous_balance=0.00;
                }
           //logger($wallet);
                $transaction_type="Aeps";
                $description="ICICI Aeps Cash Withdrawal";
                $wallet_operation="cr";
                if($transactionData->txnType == "AP"){
                    $transaction_type="Aadhaar Pay";
                    $description="ICICI Aadhaar Pay Cash Withdrawal";
                    $wallet_operation="cr";
                }
                $walletData=array(
                    'user_id'=>$transactionData->userId,
                    'service_id'=>$transactionData->serviceId,
                    'transaction_type'=>$transaction_type,
                    'transaction_reference'=>$transactionData->id,
                    'description'=>$description,
                    'transaction_date'=>$transactionData->created_at,
                    'status'=>"success",
                    'wallet_operation'=>$wallet_operation,
                    'previous_balance'=>$previous_balance,
                    'transacting_amount'=>$transactionData->amount,
                );

                $walletStore=$this->store($walletData);
                if($walletStore['response']){
                    return $walletStore['data'];
                }else{
                    return false;
                }
           }else{
                return false;
                //return response()->json(['response'=>false,'message'=>'Operation execute if transaction is CW']);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function store(array $data){
        try {
            $validator=Validator::make($data,[
                'user_id'=>'required|integer',
                'service_id'=>'required|integer',
                'transaction_type'=>'required|string',
                'transaction_reference'=>'nullable|integer',
                'description'=>'required|string',
                'transaction_date'=>'required|date',
                'previous_balance'=>'required|integer',
                'transacting_amount'=>'required|integer',
                'wallet_operation'=>'required|string',

            ]);
            if($validator->fails()){
                return ['response'=>false,'message'=>$validator->errors()];
            }
            $walletUpdate= new Wallet();
            $walletUpdate->user_id=$data['user_id'];
            $walletUpdate->service_id=$data['service_id'];
            $walletUpdate->transaction_type=$data['transaction_type'];
            $walletUpdate->transaction_reference=isset($data['transaction_reference'])?$data['transaction_reference']:null;
            $walletUpdate->description=$data['description'];
            $walletUpdate->transaction_date=$data['transaction_date'];
            $walletUpdate->status="success";
            $walletUpdate->previous_balance=$data['previous_balance'];
            $walletUpdate->transacting_amount=$data['transacting_amount'];
            $walletUpdate->wallet_operation=$data['wallet_operation'];
            if($data['wallet_operation'] == "cr"){
                $walletUpdate->closing_balance=$data['previous_balance'] + $data['transacting_amount'];
            }
            if ($data['wallet_operation'] == "dr"){
                $walletUpdate->closing_balance=$data['previous_balance'] - $data['transacting_amount'];
            }
            $walletUpdate->save();
            return ['response'=>true,'message'=>'Transaction saved','data'=>$walletUpdate];
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }

    public function update(int $id, array $data){
        try {
            $updateWallet= Wallet::find($id);
            $updateWallet->status=strtolower($data['status_message']);
            $updateWallet->transaction_reference=$data['merchant_ref_id'];
            $updateWallet->remark=$data['status_message'];
            $updateWallet->save();
            return ['response'=>true,'message'=>'Update successfully','data'=>$updateWallet];
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }
    public function walletCommission(Wallet $wallet, $transaction_type, $wallet_operation){
        try {
            $commission=Commission::where('service_id','=',$wallet->service_id)
                ->where('min_amount','<=',$wallet->transacting_amount)
                ->where('max_amount','>=',$wallet->transacting_amount)
                ->where('wef','<=',date("Y-m-d"))
                ->limit(1)
                ->get();
            //logger($commission);
            if(count($commission)==1){
                $transacting_amount=$commission[0]->commission;
                if($commission[0]->isPercentage){
                   $transacting_amount= bcdiv($wallet->transacting_amount*$commission[0]->commission/100,1,2);
                }
                $walletData=array(
                    'user_id'=>$wallet->user_id,
                    'service_id'=>$wallet->service_id,
                    'transaction_type'=>$transaction_type,
                    'transaction_reference'=>$wallet->transaction_reference,
                    'description'=>$commission[0]->serviceName,
                    'transaction_date'=>$wallet->transaction_date,
                    'status'=>"success",
                    'wallet_operation'=>$commission[0]->txnType,
                    'previous_balance'=>$wallet->closing_balance,
                    'transacting_amount'=>$transacting_amount,
                );
                $this->store($walletData);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    /*Wallet settlement use cases
        1- Get the current user balance
        2- Choose Payout mode (IMPS/NEFT)
        3- Verify payout amount, account, and otp
        4- Insert data into tbl_wallet_settlement
        5- Update reference to user_wallet
        6- Deduct settlement charges from wallet
        7- if payout rejected or failed or canceled amount
            and settlement charge reverse to wallet
    */
    public function initWalletSettlement(Request $request){
        try {
            DB::beginTransaction();
            $input=json_decode($request->getContent(), true);

            $wallet=$this->checkBalance($request);
            $data=json_decode($wallet->getContent(), true);
            $settlement= new WalletSettlementController();
            if($data['response']){
                if($input['amount']>=5000 && $input['amount']<=$data['data']['balance']-10){
                    /*Deduct amount from wallet and insert record*/
                    $deductData=array(
                        'user_id'=>Auth::user()->id,
                        'service_id'=>9,
                        'transaction_type'=>"Settlement",
                        'description'=>$input['amount'] ." settle to bank on " .now(),
                        'transaction_date'=>now(),
                        'status'=>"initiated",
                        'wallet_operation'=>"dr",
                        'previous_balance'=>$data['data']['balance'],
                        'transacting_amount'=>$input['amount'],
                    );
                    $walletResponse=$this->store($deductData);
                    if($walletResponse['response']){
                        $deductWallet=$walletResponse['data'];
                        $settleData=array(
                            'user_id'=>Auth::user()->id,
                            'service_id'=>9,
                            'txnType'=>"BS",
                            'amount'=>$input['amount'],
                            'bankname'=>Auth::user()->bankname,
                            'ifsc'=>Auth::user()->ifsc,
                            'accountNo'=>Auth::user()->accountNo,
                            'txnMedium'=>$input['txnMedium'],
                            'walletReferenceNo'=>$deductWallet->id
                        );

                        $settleResponse=$settlement->store($settleData);
                        if($settleResponse['response']){
                            $settle=$settleResponse['data'];
                            $payout_data=array(
                                'bene_account_number'=> $settle->bene_account,
                                'ifsc_code'=> $settle->ifsc,
                                'recepient_name'=> Auth::user()->fname,
                                'email_id'=> Auth::user()->email,
                                'mobile_number'=> Auth::user()->contact,
                                'debit_account_number'=> config('keys.openBank.account'),
                                'transaction_types_id'=> $settle->txnMedium,
                                'amount'=> $input['amount'],
                                'merchant_ref_id'=> $settle->id,
                                'purpose'=> 'Wallet settlement'
                            );
                            $url=config('keys.openBank.url')."payouts";
                            $token="Bearer ". config('keys.openBank.apikey').":".config('keys.openBank.secret');
                            $response=curl($url,"POST",json_encode($payout_data),$token);
                            logger($response);
                            if($response['response']){
                                $data=$response['data'];
                                /*Payout status find from response*/
                                $payout_status = PayoutBankResponse::find($data->data->transaction_status_id);
                                /*Find complete*/

                                /*Wallet settlement operation started*/

                                $settlementUpdateData=array(
                                    'txnId'=>$data->data->open_transaction_ref_id,
                                    'response'=>$data,
                                    'response_at'=>now(),
                                    'remark'=>$payout_status->status_message,
                                    'description'=>$payout_status->interpreted_message,
                                    'status'=>strtolower($payout_status->status_message)
                                );
                                $responseSettlement=$settlement->update($data->data->merchant_ref_id,$settlementUpdateData);
                                /*Settlement complete*/

                                /*Update User Wallet*/
                                if($responseSettlement['response']){
                                    $updateSettlement=$responseSettlement['data'];
                                    $updateWalletData=array(
                                        'status'=>$payout_status->status_message,
                                        'transaction_reference'=>$data->data->merchant_ref_id,
                                        'remark'=>$payout_status->status_message
                                    );
                                    $updateWalletResponse=$this->update($updateSettlement->walletReferenceNo, $updateWalletData);
                                    if($updateWalletResponse['response']){
                                        $updateWallet=$updateWalletResponse['data'];
                                        $this->walletCommission($updateWallet,"Bank Settlement","dr");
                                        DB::commit();
                                        return response()->json(['response'=>true,'message'=>'Transaction initiated','data'=>$payout_data]);
                                    }else{
                                        DB::rollBack();
                                        return response()->json(['response'=>false,'message'=>'Unable to process wallet commission']);
                                    }
                                }else{
                                    DB::rollBack();
                                    return response()->json(['response'=>false,'message'=>'Unable to update settlement']);
                                }
                        } else{
                            /*Initiate Refund operation*/
                            DB::rollback();
                            return response()->json(['response'=>false,
                                'message'=>'Transaction initiated fail. Try after some time',
                                "errors"=>$response]);
                        }
                    }else{
                            DB::rollBack();
                            return response()->json(['response'=>false,'message'=>'Unable to process settlement']);
                        }
                }else{
                        DB::rollBack();
                        return response()->json(['response'=>false,'message'=>'Unable to deduct wallet']);
                    }
            }else{
                    return response()->json(['response'=>false,'message'=>'Invalid settlement amount.'],422);
                }
        }else{
                return response()->json(['response'=>false,'message'=>'Wallet have not sufficient fund']);
            }
        }catch (\Exception $exception){
            DB::rollBack();
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function getPayout(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $url=config('keys.openBank.url')."payouts/".$input['merchant_ref_id'];
            $token="Bearer ". config('keys.openBank.apikey').":".config('keys.openBank.secret');
            $response=curl($url,"GET",null,$token);
            if($response['response']){
                $data=$response['data']->data;
                $walletSettlement=WalletSettelment::find($data->merchant_ref_id);
                $wallet=Wallet::find($walletSettlement->walletReferenceNo);
                $bankResponse=PayoutBankResponse::find($data->transaction_status_id);

                $walletSettlement->status=strtolower($bankResponse->status_message);
                $walletSettlement->remark=$bankResponse->status_message;
                $walletSettlement->description=$bankResponse->interpreted_message;
                $walletSettlement->update_response=$response['data'];
                $walletSettlement->update_response_at=now();
                $walletSettlement->save();

                $wallet->remark=$bankResponse->interpreted_message;
                $wallet->status=strtolower($bankResponse->status_message);
                $wallet->save();
                return response()->json(['response'=>true,'message'=>'Check complete', 'data'=>$walletSettlement]);
            }else{
                return response()->json($response['response'], $response['response_code']);
            }
            //return $response;
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function adminWallet(){
        try {
            $query="select u.id, u.fname, u.lname, (select uw.closing_balance from user_wallet uw where uw.user_id = u.id order by uw.id desc limit 1) as balance from users u where u.role=4 having balance is not null";
            return $data= DB::select($query);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function beneVerification(Request $request){
        try {
            redirect('login');
            if(isset(Auth::user()->id)){
                $wallet=$this->checkBalance($request);
                $walletContent=json_decode($wallet->getContent(), true);
                if($walletContent['response'] && $walletContent['data']['balance']>4){
                    $walletDeduction = new Wallet();

                }
                $accountVerificationData=array(
                    'bene_account_number'=>Auth::user()->accountNo,
                    'ifsc_code'=>Auth::user()->ifsc,
                    'recepient_name'=>Auth::user()->fname. ' '. Auth::user()->lname,
                    'email_id'=>Auth::user()->email,
                    'mobile_number'=>Auth::user()->contact,
                    'merchant_ref_id'=>Auth::user()->id
                );
                $url=$url=config('keys.openBank.url')."bank_account/verify";
                $token="Bearer ". config('keys.openBank.apikey').":".config('keys.openBank.secret');
                $response=curl($url,"POST",json_encode($accountVerificationData),$token);
                logger($response);

            }else{
                redirect('login');
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

}
