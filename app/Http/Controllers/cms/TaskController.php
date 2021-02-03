<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;
use App\Models\cms\Task;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\False_;

class TaskController extends Controller
{
    public function index(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            //return $input;
            if(isset($request['leadId'])){
                return response()->json($this->getTask($input));
            }else{
                return response()->json($this->getTask());
            }

        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);

        }
    }
    public function store(Request $request){
        try {
            $input=json_decode($request->getContent(), true);
            $validator= Validator::make($input,[
                'taskName'=>'required|string|min:3',
                'taskDescription'=>'required|string|min:5',
                'taskReminder'=>'required',
                'taskAssignTo'=>'required|integer',
                'parentId'=>'nullable|integer'
            ]);
            if($validator->fails()){
                return response()->json(['response'=>false,'message'=>$validator->fails()],400);
            }
            $taskReminderTime=strtotime($input['taskReminder']);
            $currentTime=strtotime(date("Y-m-d H:i:s"));

            if($taskReminderTime<$currentTime && ($taskReminderTime-$currentTime)<1800){
                return response()->json(['response'=>false,'message'=>'Task reminder time minimum 30 min greater then current time.','data'=>$taskReminderTime-$currentTime],400);
            }
            $input['taskAssignedBy']=Auth::user()->id;
            $input['taskStatus']='open';
            $data=array();
            if(isset($input['leadId'])){
                $data['leadId']=$input['leadId'];
            }
            if(isset($input['action'])){
                $data['action']=$input['action'];
            }
            if(count($data)>0){
                $input['taskRelatedTo']=$data;
            }
            $input['parentId']=null;
            $result=$this->createTask($input);
            if($result['response']){
                return response()->json(['response'=>true,'message'=>'Task created successfully'],200);
            }else{
                return response()->json(['response'=>false,'message'=>$result['message']],500);
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function createTask(Array $data){
        try {
            if(count($data)>0){

                $task = new Task();
                $task->taskName = $data['taskName'];
                $task->taskDescription = $data['taskDescription'];
                $task->taskReminder = $data['taskReminder'];
                $task->taskAssignTo = $data['taskAssignTo'];
                $task->taskAssignedBy = $data['taskAssignedBy'];
                $task->parentId = $data['parentId'];
                $task->taskStatus = $data['taskStatus'];
                $task->relatedTo=isset($data['taskRelatedTo'])?json_decode($data['taskRelatedTo']):null;
                $task->save();

                return array('response'=>true, 'data'=>$task);
            }else{
                return array('response'=>false,'message'=>'Empty array provided');
            }
        }catch (\Exception $exception){
            return ['response'=>false,'message'=>"Task error".$exception->getMessage()];
        }
    }

    public function getTask($where=null){
        try {

            $task=Task::select('*');
            if(isset($where['taskDate'])){
                $task
                    ->where(DB::raw('date(`taskReminder`)'),
                        date("Y-m-d", strtotime($where['taskDate'])));
            }
            if(isset($where['leadId'])){
                $task->where("relatedTo->leadId",'=',$where['leadId']);
            }
            if(Auth::user()->role == 13){
                $task->where('taskAssignTo',Auth::user()->id);
            }
            $task->orderBy('taskReminder','DESC');
            $data=$task->get();

            if(count($data)>0){
                return ['response'=>true,'message'=>'Record fetched successfully','data'=>$data];
            }else{
                return ['response'=>false,'message'=>'No record found', 'data'=>$task->toSql()];
            }
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
}
