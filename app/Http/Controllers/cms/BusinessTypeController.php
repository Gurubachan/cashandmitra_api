<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use App\Models\cms\BusinessType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessTypeController extends Controller
{
    public function index(){
        try {
            $business=BusinessType::where("isActive",1)
                ->get();
            if(count($business)>0){
                $data['businessType']=$business;
                $data['leadSource']=config('constants.leadSource');
                $data['leadPotential']=config('constants.leadPotential');
                $data['interestIn']=config('constants.interestIn');
                $data['leadStages']=config('constants.leadStages');
                return response()->json(['response'=>true,"message"=>count($business)." record found","data"=>$data]);
            }else{
                return response()->json(['response'=>false,"message"=>"No record found"],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,"message"=>$exception->getMessage()]);
        }
    }

    public function store(Request $request){
        try {
            $input=json_decode($request->getContent(),true);
            $validator=Validator::make($input,[
                'businessType'=>"required|string",
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,"message"=>$validator->errors()],400);
            }
            $type= new BusinessType();
            $type->type=$input['businessType'];
            $type->isActive=true;
            $type->save();
            return response()->json(['response'=>true,"message"=>"New business type created","data"=>$type]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,"message"=>$exception->getMessage()]);
        }
    }
}
