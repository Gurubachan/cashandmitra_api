<?php

namespace App\Http\Controllers\verification;

use App\Events\Verification\VerifyEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class VerifyController extends Controller
{
    protected $baseUrl;
    protected $org_id;
    protected $apikey;
    protected $header=array();
    public function __construct(){
        $this->baseUrl=config('keys.piChain.baseurl');
        $this->org_id=config('keys.piChain.org_id');
        $this->apikey=config('keys.piChain.apikey');
        $this->header=array('apikey:'.$this->apikey,'org-id:'.$this->org_id);
    }

    public function verifyPan(Request $request){
        try {
            $user=User::find(Auth::user()->id);
            $panData=array(
                'name'=>$user->fname.' '.$user->mname.' '.$user->lname,
                'dob'=>date("d/m/Y", strtotime($user->dob)),
                'number'=>$user->panNo,
                'type'=>'PAN'
            );
            $url=$this->baseUrl.'document_verification';
            event(new VerifyEvent($panData, $this->header,$url));
            return response()->json([
                'response'=>true,
                'message'=>'Pan details submitted for verification',
                'data'=>$panData
            ]);
            //return curl($url,'POST',json_encode($panData),$this->header);
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }
    }

    public function verifyAadhaar(Request $request){
        try {
            $user=User::find(Auth::user()->id);
            $aadhaarData=array(
                'number'=>"$user->aadhaar",
                'type'=>'Aadhaar'
            );
            $url=$this->baseUrl.'document_verification';
            event(new VerifyEvent($aadhaarData, $this->header,$url));
            return response()->json([
                'response'=>true,
                'message'=>'aadhaar document submitted for verification',
                'data'=>$aadhaarData
            ]);
            //return curl($url,'POST',json_encode($panData),$this->header);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function accountVerify(Request $request){
        try {
            $user=User::find(Auth::user()->id);
            $accountVerificationData=array(
                'account_number'=>$user->accountNo,
                'name'=>$user->fname.' '.$user->mname.' '.$user->lname,
                'ifsc'=>$user->ifsc,
                'type'=>'account'
            );
            $url=$this->baseUrl.'account_verification';
            event(new VerifyEvent($accountVerificationData,$this->header,$url));
            return response()->json([
                'response'=>true,
                'message'=>'Account data submitted for verification',
                'data'=>$accountVerificationData
            ]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}


