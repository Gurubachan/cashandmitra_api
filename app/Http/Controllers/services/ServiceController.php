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
    public function index(){
        try {
            $service=Service::
            select('tbl_services.*',
                'tbl_user_wise_services.isActive',
                'tbl_user_wise_services.onboarded',
                'tbl_user_wise_services.onboardStatus',
            )
            ->leftjoin('tbl_user_wise_services','tbl_user_wise_services.serviceId','=','tbl_services.id')
            ->get()
            ;
            return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$service],200);
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
           /* if(isset($request) && is_array($request)){
                if(isset($request['userId'])){

                }
            }else{
                return ['response'=>false,'message'=>'invalid data'];
            }*/
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
            /*
             * Get User services
             */
            /*for ($i=0; $i<count($input['services']); $i++){
                $myService[]=$input['services'][$i]['id'];
            }
            $services=UserWiseService::where('userId',$input['userId'])
                ->whereIn('serviceId',$myService)
                ->get();
            $updateServices=array();
            $insertServices=array();
            if(count($services)>0){
                foreach ($services as $us){
                    for ($i=0; $i<count($input['services']); $i++){
                        if($us->serviceId == $input['services'][$i]['id']){
                            $updateServices[]=array(
                                'isActive'=>$input['services'][$i]['isActive'],
                                'updated_at'=>now()
                            );
                            break;
                        }else{

                        }
                    }
                }
            }*/
            $userService=array();
            for ($i=0; $i<count($input['services']); $i++){
                $userService[]=array(
                    'serviceId'=>$input['services'][$i]['id'],
                    'isActive'=>$input['services'][$i]['isActive'],
                    'userId'=>$input['userId'],
                    'created_at'=>now()
                );
            }
            $result=UserWiseService::insert($userService);
            if($result){
                return response()->json(['response'=>true,'message'=>'Service Assigned']);
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
}
