<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\cms\BusinessTypeController;
use App\Http\Controllers\cms\CallingController;
use App\Http\Controllers\cms\EmailController;
use App\Http\Controllers\cms\LeadController;
use App\Http\Controllers\cms\PinCodeController;
use App\Http\Controllers\cms\SMSController;
use App\Http\Controllers\cms\TaskController;
use App\Http\Controllers\services\AepsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});*/

Route::get('login',[AuthController::class,'index'])->name('login');
Route::group(['prefix'=>'auth'],function (){
    Route::post('login',[AuthController::class,'login'])->name('login');
    Route::delete('logout',[AuthController::class,'logout'])->name('logout');
    Route::post('register',[AuthController::class,'register'])->name('register');
    Route::post('request-password',[AuthController::class,'requestPassword'])->name('request-password');
    Route::put('reset-password',[AuthController::class,'resetPassword'])->name('reset-password');
    Route::post('verify',[SMSController::class,'verifyOtp']);
});

Route::group(['middleware'=>'auth:api'], function (){
    Route::get('user',[AuthController::class,'getUser'])->name('user');
    Route::post('users',[AuthController::class,'getUsers']);
    Route::get('lead',[LeadController::class,'index']);
    Route::post('lead',[LeadController::class,'store']);
    Route::get('leadSource',[LeadController::class,'leadSource']);
    Route::get('leadCount',[LeadController::class,'leadCount']);
    Route::get('calling',[CallingController::class,'index']);
    Route::post('callAssign',[CallingController::class,'leadAssign']);
    Route::get('callRemainToAssign',[CallingController::class,'leadToBeAssignCount']);
    Route::post('callUpdate',[CallingController::class,'leadCallingUpdate']);
    Route::post('callingCount',[CallingController::class,'leadCallingCount']);
});

Route::group(['prefix'=>'task', 'middleware'=>'auth:api'], function(){
    Route::post('getTask',[TaskController::class,'index']);
    Route::post('create',[TaskController::class,'store']);
});
Route::group(["prefix"=>'profile', 'middleware'=>'auth:api'], function (){
    Route::post('about',[AuthController::class,'about']);
    Route::post('office',[AuthController::class,'office']);
    Route::post('bank',[AuthController::class,'bank']);
    Route::post('getOTP',[SMSController::class,'sendOTP']);
    Route::post('verify',[SMSController::class,'verifyOtp']);
    Route::post('getEmailOTP',[EmailController::class,'sendEmailOtp']);
    Route::post('verifyEmail',[EmailController::class,'verifyEmail']);
});

Route::group(['prefix'=>'services', 'middleware'=>'auth:api'], function (){
    Route::post('iciciKyc',[AepsController::class,'iciciKyc']);

});
Route::get('pinCode/{code}',[PinCodeController::class,'fetch']);
Route::get('type',[BusinessTypeController::class,"index"]);
Route::post('type',[BusinessTypeController::class,"store"]);
Route::get('state',[PinCodeController::class,'getState']);
Route::post('district',[PinCodeController::class,'getDistrict']);
Route::get('callStatus',[PinCodeController::class,'getCallStatus']);
