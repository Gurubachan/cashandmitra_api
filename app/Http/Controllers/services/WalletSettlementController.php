<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\WalletSettelment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                    'message'=>'Last Transaction found'],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
