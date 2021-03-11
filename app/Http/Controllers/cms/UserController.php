<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Controller;

use App\Models\cms\UserAttendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function markAttendance(Request $request){
        try {
            $input= json_decode($request->getContent(), true);
            $validator=Validator::make($input, [
                'inTime' =>'required',
                'coords' =>'required|array',
                'location'=>'required|string',
            ]);
            if($validator->fails()){
                return response()->json(['response'=>true,'message'=>$validator->errors()],400);
            }
            $attendance= new UserAttendance();
            $attendance->inTime=date("Y-m-d H:i:s", strtotime($input['inTime']));
            $attendance->coords=$input['coords'];
            $attendance->location=$input['location'];
            $attendance->userId=Auth::user()->id;
            $attendance->save();

            return response()->json(['response'=>true,'message'=>'Attendance marked'],200);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getUserGroup(Request $request){
        try {
            $userGroup=DB::table('usergroup')
                ->select('id','groupName')
                ->where('isActive','=',1)
                ->where('isDeleted','=',0)
                ->get();
            if(count($userGroup)>0){
                return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$userGroup]);
            }else{
                return response()->json(['response'=>false,'message'=>'Unable to fetch record'],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getUserType(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $validator= Validator::make($input,['group'=>'nullable|array']);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $usertype=DB::table('user_types')
                ->select('id','types')
                ->where('isActive','=',1)
                ->where('isDeleted','=',0)
                ->whereIn('userGroup',$input['group'])
                ->get();
            if(count($usertype)>0){
                return response()->json(['response'=>true,'message'=>'Record fetched', 'data'=>$usertype]);
            }else{
                return response()->json(['response'=>false,'message'=>'Record not found'],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getUsers(Request $request){
        try {
            $where=[
                ['users.isActive',true],
            ];
            $input= json_decode($request->getContent(),true);



            $user = User::select('users.*',
                DB::raw("CONCAT(usergroup.groupName, '_',user_types.types) as userRole"),
                'usergroup.id as userGroup',
                'tbl_pin_code.village',
                'tbl_pin_code.poName','tbl_pin_code.PinCode',
                'tbl_pin_code.SubDistrict','tbl_pin_code.district',
                'tbl_pin_code.state')
                ->leftjoin('tbl_pin_code', 'tbl_pin_code.id','=', 'users.pincode')
                ->leftjoin('user_types','user_types.id','users.role')
                ->leftjoin('usergroup','usergroup.id','user_types.userGroup');
            if(isset($input) && isset($input['key']) && isset($input['value']) ){
                if(is_array($input['value'])){
                    $user->whereIn('users.'.$input['key'],$input['value']);
                }else{
                    array_push($where, ['users.'.$input['key'],$input['value']]);
                    $user->where($where);
                }

            }
            $user->orderby('users.id', 'DESC');
            if(isset($input['groupname'])){
                $user->whereIn('usergroup.id',$input['groupname']);
            }
            if(isset($input['pagination']) && $input['pagination']== false){
                $data=$user->get();
            }else{
                $data=$user
                    ->simplePaginate();
            }

            if(count($data)>0){
                return response()->json(['response'=>true,
                    'message'=>'User data fetched',
                    'data'=>$data],200);
            }else{
                return response()->json([
                    'response'=>false,
                    'message'=>'Record not found',
                    ],404);
            }
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
            }
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
                $auth= new AuthController();
                $userResponse=$auth->getUser();
                //$data=$userResponse->getContent();
                $data=json_decode($userResponse->getContent(),true);
                return response()->json(['response'=>true,'message'=>'Basic info updated', 'data'=>$data['data']]);


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
                $auth= new AuthController();
                $userResponse=$auth->getUser();
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
                $auth= new AuthController();
                $userResponse=$auth->getUser();
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

    public function update(Request $request){
        try {
            $input=json_decode($request->getContent(),true);
            $user=User::find($input['id']);
            if(isset($input['loginAllowed'])){
                $user->loginAllowed=$input['loginAllowed'];
            }
            if(isset($input['role'])){
                $user->role=$input['role'];
            }
            if(isset($input['parentId'])){
                $user->parentId=$input['parentId'];
            }
            $user->save();
            $auth= new AuthController();
            $userResponse=$auth->getUser();
            $data=json_decode($userResponse->getContent(), true);
            return response()->json(['response'=>true,'message'=>'Record updated','data'=>$data]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
