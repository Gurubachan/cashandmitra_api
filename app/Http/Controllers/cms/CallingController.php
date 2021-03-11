<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use App\Models\cms\Lead;
use App\Models\cms\LeadCalling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CallingController extends Controller
{
    public function index(Request $request){
        try{

            $lead=null;

            if(isset($request['leadId'])){
                $lead=$this->getLead($request['leadId']);
            }else{
                $lead=$this->getLead();
            }
            //$lead=$this->getLead();


            if($lead['response']){
                return response()->json([
                    'response'=>true,
                    "message"=>'New lead Found',
                    "data"=>$lead['data']
                ],200);
            }else{
                return response()->json(['response'=>false,'message'=>$lead['message']],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function getLead($id=null){
        try{
            $lead=Lead::select(
                'tbl_lead_calling.id as leadCallingId','leads.id',
                "leads.name",'leads.contact','leads.email','leads.address',
                "leads.isInterested","leads.leadClosed","leads.leadSource",
                "leads.isActive","leads.interestedIn","leads.dealSize","leads.leadType",
                "leads.callingDate","leads.callingId","leads.entryBy","leads.entryLocation",
                "tbl_pin_code.*",
                "tbl_business_type.*",
                'users.fname','users.lname')
                ->join('tbl_lead_calling','leads.id','=','tbl_lead_calling.leadId')
                ->leftJoin('tbl_pin_code',"leads.pinCodeId","=","tbl_pin_code.id")
                ->leftjoin("tbl_business_type","tbl_business_type.id","=","leads.businessType")
                ->leftjoin('users','users.id','=','leads.entryBy')
                ;
            if(isset($id) && $id!=null){
                $data= $lead->where('tbl_lead_calling.assignTo', Auth::user()->id)
                    ->where('leads.id',$id)
                    ->orderBy('leads.id','desc')
                    ->simplePaginate();
                if(count($data)>0){
                    return ['response'=>true,'data'=>$data];
                }else{
                    return ['response'=>false,'message'=>'no record found'];
                }
            }else{
                if(Auth::user()->role == 13){
                     $data= $lead->where("tbl_lead_calling.assignTo",Auth::user()->id)
                        ->where('tbl_lead_calling.callingOn',date("Y-m-d"))
                         ->where('tbl_lead_calling.isCalled','=',false)
                        ->orderBy('tbl_lead_calling.id','asc')
                        ->simplePaginate(1);
                    if(count($data)>0){
                        return ['response'=>true,'data'=>$data];
                    }else{
                        return ['response'=>false,'message'=>'No record found'];
                    }
                }else{
                    $data= $lead->orderBy('leads.id','asc')
                        ->simplePaginate();

                    if(count($data)>0){
                        return ['response'=>true,'data'=>$data];
                    }else{
                        return ['response'=>false,'message'=>'no record found'];
                    }
                }
            }
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>$exception->getMessage()];
        }


    }

    public function leadAssign(Request $request){
        try {
            $input=json_decode($request->getContent(),true);
            $validator= Validator::make($input,[
                'record'=>'required|integer',
                'assignTo'=>'required|integer',
                'callingDate'=>'required'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            /*
             * Fetch Record from leads table which are not assigned
             * */
            $leads= Lead::select('id')
                ->whereNotIn('id',LeadCalling::select('leadId')->get()->toArray())
                ->limit($input['record'])
                ->get();
            if(count($leads)>0){
                $data=array();
                foreach ($leads as $l){
                    $data[]=array('leadId'=>$l->id,
                        'assignTo'=>$input['assignTo'],
                        'assignBy'=>Auth::user()->id,
                        'created_at'=>date("Y-m-d H:i:s"),
                        'callingOn'=>date("Y-m-d", strtotime($input['callingDate'])));
                }
                if(count($data)>0){
                    $response=LeadCalling::insert($data);
                    return response()->json(['response'=>true,'message'=>'Record assigned','data'=>$response]);
                }else{
                    return response()->json(['response'=>false,'message'=>'Record assignment fail']);
                }

            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function leadCallingUpdate(Request $request){
        DB::beginTransaction();
        try {
            $input=json_decode($request->getContent(),true);

            $validator= Validator::make($input,[
                'callStatus'=>'required|integer',
                'callRemark'=>'required|string',
                'callInitTime'=>'required',
                'callEndTime'=>'required',
            ]);
            if(isset($input['id']) && is_int($input['id'])){
                $validator= Validator::make($input,[
                    'id'=>'required|integer'
                ]);
                $callingUpdate = LeadCalling::find($input['id']);
            }else{
                $validator= Validator::make($input,[
                    'leadId'=>'required|integer'
                ]);
                $callingUpdate = new LeadCalling();
                $callingUpdate->leadId = $input['leadId'];
                $callingUpdate->assignTo = Auth::user()->id;
                $callingUpdate->assignBy = Auth::user()->id;
            }
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->errors()],400);
            }
            $callingUpdate->callStatus = $input['callStatus'];
            $callingUpdate->callRemark = $input['callRemark'];
            $callingUpdate->callInitTime = date("Y-m-d H:i:s",strtotime($input['callInitTime']));
            $callingUpdate->callEndTime = date("Y-m-d H:i:s",strtotime($input['callEndTime']));
            $callingUpdate->updated_at = date("Y-m-d H:i:s");
            $callingUpdate->isCalled =true;
            $callingUpdate->save();

            if($callingUpdate->leadId != null){
                $lead= Lead::find($callingUpdate->leadId);
                $lead->callingDate = date("Y-m-d H:i:s");
                $lead->callingId = $callingUpdate->id;
                $lead->save();

                if(in_array($input['callStatus'],[6,7])){
                    $validator = Validator::make($input,[
                        'callReminder'=>'required'
                    ]);
                    if($validator->fails()){
                        return response()->json(['response'=>false,
                            'message'=>$validator->errors()],400);
                    }

                    $taskData= array(
                        'taskName'=>'Call Back',
                        'taskDescription'=>'Call back to '.$lead->name .' on '.$lead->contact,
                        'taskReminder'=>date("Y-m-d H:i:s",strtotime($input['callReminder'])),
                        'taskAssignTo'=>Auth::user()->id,
                        'taskAssignedBy'=>Auth::user()->id,
                        'parentId'=>null,
                        'taskStatus'=>'open',
                        'taskRelatedTo'=>json_encode(['leadId'=>$lead->id,'action'=>'callback'])
                    );
                    $task = new TaskController();
                    $taskResponse=$task->createTask($taskData);
                    if($taskResponse['response']){
                        DB::commit();
                        return response()->json(['response'=>true,
                            'message'=>'Call updated and task created','data'=>$callingUpdate]);
                    }else{
                        DB::rollBack();
                        return response()->json(
                            ['response'=>false,
                            'message'=>'Unable to save this calling, due to task creation failed. Please try again with proper call reminder time'
                            ],500);
                    }
                }
                DB::commit();
                return response()->json(['response'=>true,'message'=>'Call updated','data'=>$callingUpdate]);

            }else{
                DB::rollBack();
                return response()->json(['response'=>false,'message'=>'Unable to save this calling please try again'],500);
            }


        }catch (\Exception $exception){
            DB::rollback();
            return response()->json(['response'=>false,'message'=>"Calling error ".$exception->getLine()],500);
        }
    }

    public function leadToBeAssignCount(){
        try {
            $toBeAssign = DB::select("
select count(*) as count from leads
where leads.id not in (
select tbl_lead_calling.leadId from tbl_lead_calling
)
");
            return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$toBeAssign],200);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }

    public function leadCallingCount(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $callingCount=DB::table('tbl_lead_calling')
                ->join('users','tbl_lead_calling.assignTo','=','users.id')
                ->select('users.fname', 'users.lname',
                    DB::raw('date(tbl_lead_calling.callingOn) as calledOn'),
                    DB::raw('count(if(tbl_lead_calling.isCalled,1,null)) as called'),
                    DB::raw('count(tbl_lead_calling.id) as assigned')
                );
            if(Auth::user()->role == 13){
                $callingCount->where('tbl_lead_calling.assignTo','=',Auth::user()->id);
            }
            if(isset($input['callingDate'])){
                $callingCount->having('calledOn','=',date("Y-m-d", strtotime($request['callingDate'])));
            }
            $data=$callingCount
                ->groupBy('tbl_lead_calling.assignTo','tbl_lead_calling.callingOn', 'users.fname','users.lname')
                ->orderByDesc('tbl_lead_calling.callingOn')
                ->simplePaginate();

            if(count($data)>0){
                return response()->json(['response'=>true,'message'=>'Record found','data'=>$data]);
            }else{
                return response()->json(['response'=>false,'message'=>'No record found'],404);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
