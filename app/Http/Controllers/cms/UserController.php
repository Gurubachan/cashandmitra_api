<?php

namespace App\Http\Controllers\cms;

use App\Http\Controllers\Controller;

use App\Models\cms\UserAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function markAttendance(Request $request){
        try {
            $input= json_decode($request->getContent(), true);
            $validator=Validator::make($input, [
                'inTime' =>'required',
                'coords' =>'required|json',
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
}
