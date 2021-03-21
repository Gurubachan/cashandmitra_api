<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\Commission;
use App\Models\services\ICICIAEPSTransaction;
use App\Models\services\PayoutBankResponse;
use App\Models\services\Wallet;
use App\Models\services\WalletSettelment;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            if($transactionData->txnType == "CW" && $transactionData->status == "SUCCESS" && $transactionData->isWalletUpdate == false){
           $wallet= Wallet::where('user_id','=',$transactionData->userId)
               ->limit(1)
               ->orderBy('id','DESC')
                ->get();
           //logger($wallet);
            $updateWallet= new Wallet();
                $updateWallet->user_id=$transactionData->userId;
                $updateWallet->service_id=$transactionData->serviceId;
                $updateWallet->transaction_type="Aeps";
                $updateWallet->transaction_reference=$transactionData->id;
                $updateWallet->description="ICICI Aeps Cash Withdrawal";
                $updateWallet->transaction_date=$transactionData->created_at;
                $updateWallet->status="success";
                $updateWallet->wallet_operation="cr";
                if(count($wallet)==1){
                   $updateWallet->previous_balance=$wallet[0]->closing_balance;
                   $updateWallet->transacting_amount=$transactionData->amount;
                   $updateWallet->closing_balance=$wallet[0]->closing_balance + $transactionData->amount;
               }else{
                   $updateWallet->previous_balance=0.00;
                   $updateWallet->transacting_amount=$transactionData->amount;
                   $updateWallet->closing_balance=0 + $transactionData->amount;
                   // return response()->json(['response'=>true,'message'=>'Wallet updated','data'=>$updateWallet]);
                }
                $updateWallet->save();

                return $updateWallet;
           }else{
                return false;
                //return response()->json(['response'=>false,'message'=>'Operation execute if transaction is CW']);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
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
                $walletUpdate= new Wallet();
                $walletUpdate->user_id=$wallet->user_id;
                $walletUpdate->service_id=$wallet->service_id;
                $walletUpdate->transaction_type=$transaction_type;
                $walletUpdate->transaction_reference=$wallet->transaction_reference;
                $walletUpdate->description=$commission[0]->serviceName;
                $walletUpdate->transaction_date=$wallet->transaction_date;
                $walletUpdate->status="success";
                $walletUpdate->previous_balance=$wallet->closing_balance;
                $walletUpdate->transacting_amount=$commission[0]->commission;
                $walletUpdate->wallet_operation=$wallet_operation;
                if($wallet_operation == "cr"){
                    $walletUpdate->closing_balance=$wallet->closing_balance + $commission[0]->commission;
                }
                if ($wallet_operation == "dr"){
                    $walletUpdate->closing_balance=$wallet->closing_balance - $commission[0]->commission;
                }


                $walletUpdate->save();
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
            if($data['response']){
                if($input['amount']>=5000 && $input['amount']<=$data['data']['balance']-10){
                    /*Deduct amount from wallet and insert record*/
                    $deductWallet= new Wallet();
                    $deductWallet->user_id=Auth::user()->id;
                    $deductWallet->previous_balance=$data['data']['balance'];
                    $deductWallet->transacting_amount=$input['amount'];
                    $deductWallet->closing_balance=$data['data']['balance'] - $input['amount'];
                    $deductWallet->transaction_type="Settlement";
                    $deductWallet->description=$input['amount'] ."settle to bank on " .now();
                    $deductWallet->wallet_operation="dr";
                    $deductWallet->service_id=9;
                    $deductWallet->transaction_date=now();
                    $deductWallet->status="initiated";
                    $deductWallet->save();

                    /*Wallet deduction operation close*/

                    /*Wallet Settlement operation start*/
                    $settle= new WalletSettelment();
                    $settle->user_id=Auth::user()->id;
                    $settle->service_id=9;
                    $settle->txnType="BS";/*Bank settlement*/
                    $settle->txnTime=now();
                    $settle->amount=$input['amount'];
                    $settle->bankName=Auth::user()->bankname;
                    $settle->ifsc=Auth::user()->ifsc;
                    $settle->bene_account=Auth::user()->accountNo;
                    $settle->txnMedium=$input['txnMedium'];
                    $settle->status="initiated";
                    $settle->isWalletUpdate=true;
                    $settle->walletReferenceNo=$deductWallet->id;
                    $settle->save();
                    /*Data post for open money*/
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
                    /*End data preparation*/
                    $url=config('keys.openBank.url');
                    $token="Bearer ". config('keys.openBank.apikey').":".config('keys.openBank.secret');
                    $response=curl($url,"POST",$payout_data,$token);
                    if($response['response']){
                        $data=$response['data'];
                        /*Payout status find from response*/
                        $payout_status = PayoutBankResponse::find($data->transaction_status_id);
                        /*Find complete*/

                        /*Wallet settlement operation started*/
                        $updateSettlement = WalletSettelment::find($data->merchant_ref_id);
                        $updateSettlement->txnId=$data->open_transaction_ref_id;
                        $updateSettlement->response=$data;
                        $updateSettlement->response_at=now();
                        $updateSettlement->remark=$payout_status->status_message;
                        $updateSettlement->description=$payout_status->interpreted_message;
                        $updateSettlement->status=strtolower($payout_status->status_message);
                        $updateSettlement->save();
                        /*Settlement complete*/

                        /*Update User Wallet*/
                        $updateWallet= Wallet::find($updateSettlement->walletReferenceNo);
                        $updateWallet->status=strtolower($payout_status->status_message);
                        $updateWallet->transaction_reference=$data->merchant_ref_id;
                        $updateWallet->remark=$payout_status->status_message;
                        $updateWallet->save();
                        /*User wallet updating complete*/
                        $this->walletCommission($updateWallet,"Bank Settlement","dr");
                        DB::commit();
                        return response()->json(['response'=>true,'message'=>'Transaction initiated','data'=>$payout_data]);
                    }else{
                        /*Initiate Refund operation*/
                        DB::rollback();
                        return response()->json(['response'=>false,'message'=>'Transaction initiated fail. Try after some time']);
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




}
