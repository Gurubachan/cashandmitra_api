<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use App\Models\cms\Lead;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeadController extends Controller
{
    public function index(Request $request){
        try{
            if(isset($request['leadId'])){
                //$request->validate(['leadId'=>'required|string']);
                $lead=$this->getLead($request['leadId']);
            }else{
                $lead=$this->getLead();
            }


            /*if(isset($request['leadId'])){
                $validator= Validator::make($request,['leadId'=>'required|integer']);
                if($validator->fails()){
                    return response()->json(['response'=>false,'message'=>$validator->errors()],400);
                }
                $lead=$this->getLead($request['leadId']);
            }else{
                $lead=$this->getLead();
            }*/
            return response()->json([
                'response'=>true,
                "message"=>" lead found",
                "data"=>$lead
            ],200);
            /*if(count($lead)>0){
                return response()->json([
                    'response'=>true,
                    "message"=>count($lead)." lead found",
                    "data"=>$lead
                ],200);
            }else{
                return response()->json(['response'=>false,
                    'message'=>"No lead found"],404);
            }*/
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function store(Request $request){
        try{
            $input=json_decode($request->getContent(),true);
            /*$validator=Validator::make($input,[
                'name'=>"required|string",
                'contact'=>"required|integer|digits:10|unique:leads,contact",
                'email'=>"nullable|email",
                'address'=>"required|string",
                'pinCode'=>'required|integer',
                'leadSource'=>"required|integer",
                'visit_date'=>"required|date",
                'businessType'=>"required|integer",
                'isInterested'=>"required|integer|min:0|max:3",
                'dealSize'=>"required|integer",
                'interestedIn'=>"nullable|integer"
            ]);*/


            if(isset($input['id']) && is_int($input['id']) && $input['id']!= null){
                $validator=Validator::make($input,[
                    'name'=>"required|string",
                    'email'=>"nullable|email",
                    'address'=>"required|string",
                    'pinCode'=>'required|integer',
                    'leadSource'=>"required|integer",
                    'visit_date'=>"required|date",
                    'businessType'=>"required|integer",
                    'isInterested'=>"required|integer|min:0|max:3",
                    'dealSize'=>"required|integer",
                    'interestedIn'=>"nullable|integer"
                ]);
                $lead=Lead::find($input['id']);
            }else{
                $validator=Validator::make($input,[
                    'name'=>"required|string",
                    'contact'=>"required|integer|digits:10|unique:leads,contact",
                    'email'=>"nullable|email",
                    'address'=>"required|string",
                    'pinCode'=>'required|integer',
                    'leadSource'=>"required|integer",
                    'visit_date'=>"required|date",
                    'businessType'=>"required|integer",
                    'isInterested'=>"required|integer|min:0|max:3",
                    'dealSize'=>"required|integer",
                    'interestedIn'=>"nullable|integer"
                ]);

                $lead= new Lead();
                $lead->contact=$input['contact'];
            }
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $lead->name=$input['name'];

            $lead->email=($input['email']!=null)?$input['email']:null;
            $lead->address=$input['address'];
            $lead->pinCodeId=$input['pinCode'];
            $lead->leadSource=$input['leadSource'];
            $lead->entryBy=Auth::user()->id;
            $lead->visit_date=date("Y-m-d H:s:i",strtotime($input['visit_date']));
            $lead->businessType=$input['businessType'];
            $lead->isInterested=$input['isInterested'];
            $lead->interestedIn=$input['interestedIn'];
            $lead->dealSize=$input['dealSize'];
            $lead->entryLocation=isset($input['entryLocation'])?$input['entryLocation']:null;
            $lead->entryAddress=isset($input['entryAddress'])?$input['entryAddress']:null;
            $lead->save();

            return response()->json([
                'response'=>true,
                'message'=>'New lead created',
                'data'=>$this->getLead($lead->id)
            ]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function leadSource(){
        $source=config('constants.leadSource');
        return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$source]);
    }

    public function getLead($id=null){
        try{
            /*$lead=DB::table('leads')
                ->leftjoin('tbl_pin_code',"leads.pinCodeId","=","tbl_pin_code.id")
                ->leftjoin("tbl_business_type","tbl_business_type.id","=","leads.businessType")
                ->leftjoin('users','users.id','=','leads.entryBy')
                ->select("leads.id as leadId", "leads.name", "leads.contact",
                    "leads.email","leads.address","leads.pinCodeId","leads.entryBy",
                    "leads.visit_date","leads.businessType","leads.isInterested",
                    "leads.leadClosed","leads.leadSource","leads.isActive","leads.interestedIn",
                    "leads.dealSize","leads.leadStage","leads.leadType","leads.callingDate",
                    "leads.callingId",
                    "entryLocation",
                    "tbl_pin_code.village","tbl_pin_code.poName","tbl_pin_code.pinCode",
                    "tbl_pin_code.subDistrict","tbl_pin_code.district","tbl_pin_code.state",
                    "tbl_business_type.type",
                    'users.fname',
                    'users.lname');*/
            $lead= Lead::select("leads.id as leadId", "leads.name", "leads.contact",
                "leads.email","leads.address","leads.pinCodeId","leads.entryBy",
                "leads.visit_date","leads.businessType","leads.isInterested",
                "leads.leadClosed","leads.leadSource","leads.isActive","leads.interestedIn",
                "leads.dealSize","leads.leadStage","leads.leadType","leads.callingDate",
                "leads.callingId",
                "entryLocation",
                "tbl_pin_code.village","tbl_pin_code.poName","tbl_pin_code.pinCode",
                "tbl_pin_code.subDistrict","tbl_pin_code.district","tbl_pin_code.state",
                "tbl_business_type.type",
                'users.fname',
                'users.lname')
                ->leftjoin('tbl_pin_code',"leads.pinCodeId","=","tbl_pin_code.id")
                ->leftjoin("tbl_business_type","tbl_business_type.id","=","leads.businessType")
                ->leftjoin('users','users.id','=','leads.entryBy');
            if(isset($id) && $id!=null){
                return $lead
                    ->where('leads.id',$id)
                    ->orWhere('leads.contact',$id)
                    ->orderBy('leads.id','desc')
                    ->simplePaginate();
            }else{
                if(in_array(Auth::user()->role,[5,6,7])){
                    return  $lead
                        ->where("entryBy",Auth::user()->id)
                        ->orderBy('leads.id','desc')
                        ->simplePaginate(4);
                }else{
                    return  $lead
                        ->orderBy('leads.id','desc')
                        ->simplePaginate(4);
                }

            }
        }catch (\Exception $exception){
            return $exception->getMessage();
        }


    }

    public function leadCount(){
        try {
            if(in_array(Auth::user()->role,[5,6,7])){
                $visits=DB::select("select isInterested,count(*) as today_visit
                            from leads where
                            isActive=true and date(visit_date) = :date
                            and entryBY = :entryBy
                            group by isInterested
                            ",
                    ['date'=>date("Y-m-d"),'entryBy'=>Auth::user()->id]);
                return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$visits],200);
            }else{
                $visits=DB::select("select isInterested,count(*) as today_visit
                            from leads
                            where isActive=true and date(visit_date) = ?
                            group by isInterested
                            ",
                    [date("Y-m-d")]);
                return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$visits],200);
            }



        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function webLeads(Request $request){
        try {
            $input= json_decode($request->getContent(), true);
            $validator= Validator::make($input,[
                'name'=>'required|min:5',
                'email'=>'nullable|email',
                'contact'=>"required|integer|digits:10|unique:leads,contact",
            ]);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }


}
