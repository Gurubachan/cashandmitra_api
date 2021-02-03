<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\cms\SMSController;
use App\Http\Controllers\Controller;

use App\Mail\Verification;
use App\Models\cms\EmailVerification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
                ->join('user_types','user_types.id','users.role')
                ->join('usergroup','usergroup.id','user_types.userGroup')
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
            if(!auth()->attempt(['contact'=>$inputs['contact'],'password'=>$inputs['password'],'isActive'=>1])){
                return response()->json(['response'=>false,'message'=>'Invalid Credential or User not active'],400);
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
            $user=User::create($inputs);
            $accessToken=$user->createToken('authToken')->accessToken;
            $data=array('user'=>$user,'token'=>$accessToken);
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

    public function getUsers(Request $request){
        try {
           $where=[
                ['users.isActive',true],
            ];
           $input= json_decode($request->getContent(),true);
            if(isset($input)){
                array_push($where, ['users.'.$input['key'],$input['value']]);
            }


            $user = User::select('users.*',
                DB::raw("CONCAT(usergroup.groupName, '_',user_types.types) as userRole"),
                'usergroup.id as userGroup',
                'tbl_pin_code.village',
                'tbl_pin_code.poName','tbl_pin_code.PinCode',
                'tbl_pin_code.SubDistrict','tbl_pin_code.district',
                'tbl_pin_code.state')
                ->leftjoin('tbl_pin_code', 'tbl_pin_code.id','=', 'users.pincode')
                ->join('user_types','user_types.id','users.role')
                ->join('usergroup','usergroup.id','user_types.userGroup')
                ->where($where)
                ->get();
            if(count($user)>0){
                return response()->json(['response'=>true,
                    'message'=>'User data fetched',
                    'data'=>$user],200);
            }else{
                return response()->json([
                    'response'=>false,
                    'message'=>'Record not found',
                    'data'=>$where],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
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

    public function about(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);

            $validator=Validator::make($inputs,[
                'fname'=>'required|string',
                'mname'=>'nullable|string',
                'lname'=>'required|string',
                'email'=>'required|email',
                'contact'=>'required|integer|digits:10',
                'dob'=>'required|date',
                'gender'=>'required|string|min:1|max:1',
                'whatsapp'=>'required|integer|digits:10',
                'myPic'=>'nullable|string',
                'address'=>'nullable|string|min:10|max:100',
                'pinCode'=>'nullable|integer'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }else{
                $user=User::find(Auth::user()->id);

                $user->fname=$inputs['fname'];
                $user->mname=isset($inputs['mname'])?$inputs['mname']:null;
                $user->lname=$inputs['lname'];
                $user->email=$inputs['email'];
                //$user->contact=$inputs['contact'];
                $user->dob=date("Y-m-d", strtotime($inputs['dob']));
                $user->gender=$inputs['gender'];
                if(isset($inputs['myPic']) && $inputs['myPic']!=null){
                    $image=$this->imageUpload($inputs['myPic'],Auth::user()->id,'images');
                    if($image!=false){
                        $user->myPic=$image;
                    }
                }
                $user->address=isset($inputs['address'])?$inputs['address']:null;
                $user->pincode=isset($inputs['pinCode'])?$inputs['pinCode']:null;
                $user->whatsapp=$inputs['whatsapp'];
                $user->save();
                $userResponse=$this->getUser();
                //$data=$userResponse->getContent();
                $data=json_decode($userResponse->getContent(),true);
                return response()->json(['response'=>true,'message'=>'Basic info updated', 'data'=>$data['data']]);
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function office(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs,[
                'doj'=>'required|date',
                'pannumber'=>'required|string|min:10|max:10',
                'aadhaarnumber'=>'required|integer|digits:12',
                'panimage'=>'nullable|string',
                'aadhaarimage'=>'nullable|string',
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }else{
                $user=User::find(Auth::user()->id);


                $user->panNo=$inputs['pannumber'];
                $user->aadhaar=$inputs['aadhaarnumber'];
                $image=$this->imageUpload($inputs['panimage'],$inputs['pannumber'],'panCard');
                if($image!=false){
                    $user->panCardPic=$image;
                }

                $image=$this->imageUpload($inputs['aadhaarimage'],$inputs['aadhaarnumber'],'aadhaarCard');
                if($image!=false){
                    $user->aadhaarCardPic=$image;
                }
                $user->onboardDate=date("Y-m-d", strtotime($inputs['doj']));

                $user->save();
                $userResponse=$this->getUser();
                //$data=$userResponse->getContent();
                $data=json_decode($userResponse->getContent(),true);
                return response()->json(['response'=>true,'message'=>'KYC info updated', 'data'=>$data['data']]);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function bank(Request $request){
        try {
            $inputs=json_decode($request->getContent(),true);
            $validator=Validator::make($inputs,[
                'account'=>'required|integer',
                'bankname'=>'required|string',
                'ifsccode'=>'required|string|min:11|max:12',
                'accountImage'=>'nullable|string'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }else{
                $user=User::find(Auth::user()->id);
                $user->accountNo=$inputs['account'];
                $user->bankname=$inputs['bankname'];
                $user->ifsc=$inputs['ifsccode'];
                $user->accountImage=$this->imageUpload($inputs['accountImage'],$inputs['account'],'bank');
                $user->save();
                $userResponse=$this->getUser();
                //$data=$userResponse->getContent();
                $data=json_decode($userResponse->getContent(),true);
                return response()->json(['response'=>true,'message'=>'Bank info updated', 'data'=>$data['data']]);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false, 'message'=>$exception->getMessage()],500);
        }
    }

    public function imageUpload(  $inputs,  String $name, String  $path){
        try {
            if($inputs!=null && $inputs!=""){
                $storageUrl=null;
                $uploadDate=explode(',',$inputs);
                $image=base64_decode($uploadDate[1], true);
                $storagePath=Storage::disk('local')->put('public/'.$path.'/'.base64_encode($name).".png",$image);

                if($storagePath){
                    if(request()->getHost()=="localhost" || request()->getHost()=="127.0.0.1" ){
                        $storageUrl=asset(Storage::url($path.'/'.base64_encode($name).".png"));
                    }else{
                        $storageUrl=asset('laravel/public'.Storage::url($path.'/'.base64_encode($name).".png"));
                    }

                }else{
                    return false;
                }
               return $storageUrl;
            }else{
                return false;
            }
        }catch (\Exception $exception){
            return false;
        }
    }


}
