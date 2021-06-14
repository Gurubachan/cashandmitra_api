<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\Service;
use App\Models\services\UserWiseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index($userId){
        try {
            $service=Service::select('*')
                ->where('isActive',1)
                ->get();
            $userServices=array();
            $userWiseService=UserWiseService::where('userId',$userId)->get();
            if(count($userWiseService)>0){
                foreach ($service as $s){
                    $userServices[$s->id]=array(
                        'serviceId'=>$s->id,
                        'service'=>$s->service,
                        'assigned'=>false,
                        'userId'=>$userId,
                        'id'=>null
                    );
                    foreach ($userWiseService as $us){
                        if ($s->id == $us->serviceId){
                            $userServices[$s->id]['assigned']=$us->isActive;
                            $userServices[$s->id]['id']=$us->id;
                        }
                    }
                }
            }else{
                foreach ($service as $s){
                    $userServices[$s->id]=array(
                        'serviceId'=>$s->id,
                        'service'=>$s->service,
                        'assigned'=>false,
                        'userId'=>$userId,
                        'id'=>null
                    );
                }
            }
            $userServices=base64_encode(json_encode(array_values($userServices)));
            return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$userServices],200);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getUserServices(){
        try {
            $myService=UserWiseService::select(
                'tbl_services.service','tbl_services.onBoardRequired',
                'tbl_user_wise_services.onBoardReferance',
                'tbl_user_wise_services.remark',
                'tbl_user_wise_services.onboarded',
                'tbl_user_wise_services.onboardStatus',
                'tbl_user_wise_services.serviceId',
                'tbl_user_wise_services.created_at',
                'tbl_user_wise_services.updated_at',
                'tbl_services.onBoardRequired'
            )
                ->join('tbl_services','tbl_user_wise_services.serviceId','=','tbl_services.id')
                ->where('tbl_user_wise_services.userId','=',Auth::user()->id)
                ->where('tbl_user_wise_services.isActive','=',true)
                ->get();

            if(count($myService)>0){
                return response()->json(['response'=>true,'message'=>'Service Fetched','data'=>$myService]);
            }else{
                return response()->json(['response'=>true,'message'=>'No Service Fetched'],404);
            }

        }catch (\Exception $exception){
            return response()->json([
                'response'=>false,
                'message'=>$exception->getMessage()
            ],500);
        }
    }
    public function assignService(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $validator=Validator::make($input,[
                'userId'=>'required|integer',
                'services'=>'required|array'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $result=array();
            for ($i=0; $i<count($input['services']); $i++){
                $userService=array(
                    'serviceId'=>$input['services'][$i]['serviceId'],
                    'isActive'=>$input['services'][$i]['assigned'],
                    'userId'=>$input['userId'],
                    'created_at'=>now()
                );
                $result[]=UserWiseService::updateOrCreate(
                    ['userId'=>$input['userId'],
                        'serviceId'=>$input['services'][$i]['serviceId'],
                        'id'=>$input['services'][$i]['id']],
                    $userService);
            }

            if(count($result)>0){
                return response()->json(['response'=>true,'message'=>'Service Assigned','data'=>base64_encode(json_encode($result))]);
            }else{
                return response()->json(['response'=>false,'message'=>'Some Error Occurred']);
            }
        }catch (\Exception $exception){
            return response()->json([
                'response'=>false,
                'message'=>$exception->getMessage()
            ],500);
        }
    }

    public function updateService(Request $request){
        try {
            $input=json_decode($request->getContent(),true);
            $validation=Validator::make($input,[
                'serviceId'=>'required|number',
                'userId'=>'required|number',
                'isActive'=>'required|boolean',
                'onBoardReferance'=>'nullable|string',
                'remark'=>'nullable|string'
            ]);
            if($validation->fails()){
                return response()->json(['response'=>false,'message'=>$validation->errors()],400);
            }
            $myService=UserWiseService::where('serviceId','=',$input['serviceId'])
                ->where('userId','=',$input['userId'])
                ->where('isActive','=', true)
                ->get();
            if(count($myService)>0){
                $serviceData=UserWiseService::find($myService[0]->id);
            }
            return response()->json(['response'=>true,'message'=>'Service Updated','data'=>$myService]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getuserwiseService($userId, $serviceId=null,$onboarded=false){
        try {
            $uws=UserWiseService::where('userId',$userId);
            if($serviceId!=null){
                $uws->where('serviceId',$serviceId);
            }
            if($onboarded){
                $uws->where('onboarded',1);
            }
            return $data=$uws->get();
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage(),'errorLine'=>$exception->getLine()],500);
        }
    }
}
