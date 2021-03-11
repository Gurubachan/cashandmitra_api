<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\Commission;
use App\Models\services\ICICIAEPSTransaction;
use App\Models\services\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                return response()->json(['response'=>true,'message'=>"Balance Fetched",'data'=>['balance'=>0]]);
            }


        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function walletTransaction(){
        try {

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function walletOperationaeps($transactionData){
        try {
            //get user transaction details
            if($transactionData->txnType == "CW" && $transactionData->status == "SUCCESS"){
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
                if(count($wallet)==1){
                   $updateWallet->previous_balance=$wallet[0]->closing_balance;
                   $updateWallet->transacting_amount=$transactionData->amount;
                   $updateWallet->closing_balance=$wallet[0]->closing_balance + $transactionData->amount;
                   $updateWallet->save();

                   $aepsICICI=ICICIAEPSTransaction::find($transactionData->id);
                    $aepsICICI->isWalletUpdate=true;
                    $aepsICICI->walletReferenceNo=$updateWallet->id;
                    $aepsICICI->save();
                   return response()->json(['response'=>true,'message'=>'Wallet updated','data'=>$updateWallet]);
               }else{
                   $updateWallet->previous_balance=0.00;
                   $updateWallet->transacting_amount=$transactionData->amount;
                   $updateWallet->closing_balance=0 + $transactionData->amount;
                   $updateWallet->save();

                    $aepsICICI=ICICIAEPSTransaction::find($transactionData->id);
                    $aepsICICI->isWalletUpdate=true;
                    $aepsICICI->walletReferenceNo=$updateWallet->id;
                    $aepsICICI->save();
                    return $updateWallet;
                   // return response()->json(['response'=>true,'message'=>'Wallet updated','data'=>$updateWallet]);
                }
           }else{
                return false;
                //return response()->json(['response'=>false,'message'=>'Operation execute if transaction is CW']);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function walletAepsCommission($wallet){
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
                $walletUpdate->transaction_type="Aeps Commission";
                $walletUpdate->transaction_reference=$wallet->transaction_reference;
                $walletUpdate->description=$commission[0]->serviceName;
                $walletUpdate->transaction_date=$wallet->transaction_date;
                $walletUpdate->status="success";
                $walletUpdate->previous_balance=$wallet->closing_balance;
                $walletUpdate->transacting_amount=$commission[0]->commission;
                $walletUpdate->closing_balance=$wallet->closing_balance + $commission[0]->commission;
                $walletUpdate->save();
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }


}
