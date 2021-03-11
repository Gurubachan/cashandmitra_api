<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use App\Mail\Verification;
use App\Models\cms\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    public function sendEmailOtp(){
        try {
            if(Auth::user()->id){
                $user=User::find(Auth::user()->id);
                if($user->email !=null && $user->email_verified_at==null){
                    $newTime=strtotime(date('Y-m-d H:i:s'));
                    $email = new EmailVerification();
                    $email->id='Cashand'.$newTime;
                    $email->emailId=$user->email;
                    $email->otp=rand(100000,999999);
                    $email->expiryTime=$newTime+300;
                    $email->save();

                    /*
                     * Send mail
                     * */
                    $data['user']=$user;
                    $data['email']=$email;
                    Mail::to($user->email)
                        ->send( new Verification($data));

                    if(count(Mail::failures())>0){
                        return response()->json([
                            'response'=>false,
                            'message'=>Mail::failures()]);
                    }

                    return response()->json([
                        'response'=>true,
                        'message'=>'Email sent successfully']);
                }
            }else{
                redirect('login');
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function verifyEmail(Request $request){
        try {
            $inputs=json_decode($request->getContent(), true);
            $validator=Validator::make($inputs,[
                'otp'=>'required|integer|digits:6',
                'email'=>'required|email'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $verificationUpdate=EmailVerification::where('emailId',$inputs['email'])
                ->where('otp',$inputs['otp'])
                ->where('expiryTime','>=',strtotime(date("Y-m-d H:i:s")))
                ->update([
                    'isVerified'=>1,
                    'verifiedTime'=>date("Y-m-d H:i:s"),
                    'updated_at'=>date("Y-m-d H:i:s")
                ]);
            if($verificationUpdate){
                $user=User::where('email',$inputs['email'])
                    ->where('isActive',1)
                    ->update(['email_verified_at'=>date('Y-m-d H:i:s')]);
                return response()->json(['response'=>true,'message'=>'User email verified']);

            }else{
                return response()->json(['response'=>false,'message'=>'Unable to verify email, please try again']);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
