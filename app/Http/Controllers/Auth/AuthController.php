<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\cms\SMSController;
use App\Http\Controllers\Controller;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function index(){
        try {
            return response()->json("Unauthorised access",401);
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()]);
        }
    }

    public function getUser(){
        try {
            $user = User::where('users.id', Auth::user()->id)
                ->where('users.isActive',true)
                ->leftjoin('tbl_pin_code', 'tbl_pin_code.id','=', 'users.pincode')
                ->leftjoin('user_types','user_types.id','users.role')
                ->leftjoin('usergroup','usergroup.id','user_types.userGroup')
                ->select('users.*',
                    DB::raw("CONCAT(usergroup.groupName, '_',user_types.types) as userRole"),
                    'usergroup.id as userGroup',
                    'tbl_pin_code.village',
                    'tbl_pin_code.poName','tbl_pin_code.PinCode',
                    'tbl_pin_code.SubDistrict','tbl_pin_code.district',
                    'tbl_pin_code.state'
                )
                ->get();
            if(count($user)>0){
                return response()->json(['response'=>true,
                    'message'=>'Record found',
                    'data'=>$user],200);
            }else{
                return response()->json([
                    'response'=>false,
                    'message'=>'Record not found',
                    'data'=>''],404);
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function login(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs,[
                'contact'=>'required|integer|digits:10',
                'password'=>'required|string|min:6'
            ]);
            if($validator->fails()){
                return $returnData=array('response'=>false, 'message'=>$validator->errors());
            }
            //return $validator->validated();
            if(!auth()->attempt(
                [
                    'contact'=>$inputs['contact'],
                    'password'=>$inputs['password'],
                    'isActive'=>1,
                    'loginAllowed'=>1
                ]
            )){
                return response()->json(['response'=>false,
                    'message'=>'Invalid Credential or User not active',
                    'errors'=>['Invalid Credential or User not active'],
                ],400);
            }

            $accessToken =auth()->user()->createToken('authToken')->accessToken;
            $userResponse=$this->getUser();
            //$data=$userResponse->getContent();
            $data=json_decode($userResponse->getContent(),true);
            $returnData=array('response'=>true,
                'message'=>'User login successfully',
                'data'=>['token'=>$accessToken,
                    'user'=>$data['data'][0]
                ]);
            //$returnData=array('response'=>true, 'token'=>$accessToken);
            return response()->json($returnData);
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()]);
        }
    }

    public function register(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs,[
                'fname'=>'required|string|min:3|max:25',
                'mname'=>'string',
                'lname'=>'required|string|min:2|max:20',
                'contact'=>'required|integer|digits:10|unique:users',
                'email'=>'required|email',
                'password'=>'required|string|min:6|confirmed'
            ]);
            if($validator->fails()){
                 $returnData=array('response'=>false,
                     'message'=>$validator->errors(),
                     'data'=>['user'=>$inputs,'token'=>'']);
                return response()->json($returnData);
            }
            $inputs['password']=Hash::make($inputs['password']);
            $inputs['loginAllowed']=1;
            $inputs['isActive']=true;
            $user=User::create($inputs);
            $accessToken=$user->createToken('authToken')->accessToken;

            $data=array('user'=>$user,'token'=>$accessToken);
            $sms= new SMSController();
            $message="Dear $user->fname,
Welcome to CASHAND family, your userid is $user->contact.
Login using your id and password and start transaction today.
care: 8093454700/01
mail: customercare@cashand.in ";
            $sms->sendSMS($inputs['contact'],$message);
            $returnData=array('response'=>true,
                'message'=>'User Created Successfully.',
                'data'=>$data );
            return response()->json($returnData,200);
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function requestPassword(Request $request)
    {
        try {
            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs,[
                'contact'=>'required|integer|digits:10'
            ]);
            if($validator->fails()){
                $returnData=array('response'=>false, 'message'=>$validator->errors());
                return response()->json($returnData,400);
            }
            $user=User::where('contact',$inputs['contact'])
                ->where('isActive',true)
                ->limit(1)
                ->get();
            $sms= new SMSController();
            if(count($user)==1){
                return $response=$sms->SendOTP($request);
                //return response()->json(['response'=>true,'message'=>'Unable to find user details, Please contact admin', 'data'=>json_encode($userContact)]);
            }else{
                return response()->json(['response'=>false,'message'=>'Unable to find user details, Please contact admin'],404);
            }


        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }
    public function resetPassword(Request $request){
        try {

            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs,[
                'contact'=>'required|integer|digits:10',
                'password'=>'required|string|min:6|confirmed'
            ]);
            if($validator->fails()){
                 $returnData=array('response'=>false, 'message'=>$validator->errors());
                return response()->json($returnData,400);
            }
            //get user details and set password
            $user=User::where('contact',$inputs['contact'])
                ->where('isActive', true)
                ->update(['password'=>Hash::make($inputs['password'])]);
            if($user){
                return response(['response'=>true,'message'=>'Password changed successfully']);
            }else{
                return response()->json(['response'=>false,'message'=>'Unable to change password']);
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()]);
        }
    }



    public function logout(){
        try {
            Auth::logout();
            return response()->json('Successfully logged out');
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }




}
