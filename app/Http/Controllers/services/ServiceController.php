<?php

namespace App\Http\Controllers\services;

use App\Http\Controllers\Controller;
use App\Models\services\Service;

class ServiceController extends Controller
{
    public function index(){
        try {
            $service=Service::all();
            return response()->json(['response'=>true,'message'=>'Record fetched','data'=>$service],200);
        }catch (\Exception $exception){
            return response()->json(['response'=>false,'message'=>$exception->getMessage()],500);
        }
    }
    public function assignService(){
        try {

        }catch (\Exception $exception){
            return response()->json([
                'response'=>false,
                'message'=>$exception->getMessage()
            ],500);
        }
    }
}
