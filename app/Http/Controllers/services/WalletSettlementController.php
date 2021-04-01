<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\WalletSettelment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WalletSettlementController extends Controller
{
    public function getLastSettlement(Request $request){
        try {
            $settlement= WalletSettelment::where('user_id',Auth::user()->id)
                ->orderBy('id','DESC')
                ->limit(1)
                ->get();
            if(count($settlement)==1){
                return response()->json(['response'=>true,
                    'message'=>'Last Transaction found','data'=>$settlement]);
            }else{
                return response()->json(['response'=>false,
                    'message'=>'No Transaction found'],404);
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
                'txnType'=>'required|string',
                'amount'=>'required|integer',
                'bankname'=>'required|string',
                'ifsc'=>'required|string',
                'accountNo'=>'required|string',
                'txnMedium'=>'required|string',
                'walletReferenceNo'=>'required|integer'
            ]);
            if($validator->fails()){
                return ['response'=>false,'message'=>$validator->errors()];
            }
            $settle= new WalletSettelment();
            $settle->user_id=$data['user_id'];
            $settle->service_id=$data['service_id'];
            $settle->txnType=$data['txnType'];/*Bank settlement*/
            $settle->txnTime=now();
            $settle->amount=$data['amount'];
            $settle->bankName=$data['bankname'];
            $settle->ifsc=$data['ifsc'];
            $settle->bene_account=$data['accountNo'];
            $settle->txnMedium=$data['txnMedium'];
            $settle->status="initiated";
            $settle->isWalletUpdate=true;
            $settle->walletReferenceNo=$data['walletReferenceNo'];
            $settle->save();
            return ['response'=>true,'message'=>'success','data'=>$settle];

        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }
    public function update(int $id,array $data){
        try {
            $validator=Validator::make($data,[
                'txnId'=>'required|string',
                'response'=>'required',
                'response_at'=>'required|date',
                'remark'=>'required|string',
                'description'=>'required|string',
                'status'=>'required|string'
            ]);
            if($validator->fails()){
                return ['response'=>false,'message'=>$validator->errors()];
            }
            $updateSettlement = WalletSettelment::find($id);
            $updateSettlement->txnId=$data['txnId'];
            $updateSettlement->response=$data['response'];
            $updateSettlement->response_at=$data['response_at'];
            $updateSettlement->remark=$data['remark'];
            $updateSettlement->description=$data['description'];
            $updateSettlement->status=$data['status'];
            $updateSettlement->save();
            return ['response'=>true,'message'=>'Updated successfully','data'=>$updateSettlement];
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }
}
