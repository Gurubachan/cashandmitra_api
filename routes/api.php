<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\bank\IFSCController;
use App\Http\Controllers\cms\BusinessTypeController;

use App\Http\Controllers\cms\CommonController;
use App\Http\Controllers\cms\EmailController;
use App\Http\Controllers\cms\LeadController;
use App\Http\Controllers\cms\CallingController;
use App\Http\Controllers\cms\MasterDataController;
use App\Http\Controllers\cms\PinCodeController;
use App\Http\Controllers\cms\SMSController;
use App\Http\Controllers\cms\TaskController;
use App\Http\Controllers\cms\UserController;
use App\Http\Controllers\services\AepsController;

use App\Http\Controllers\services\RBPController;
use App\Http\Controllers\services\ServiceController;
use App\Http\Controllers\services\WalletController;
use App\Http\Controllers\services\WalletSettlementController;
use App\Http\Controllers\verification\VerifyController;
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
    Route::post('login',[AuthController::class,'login']);
    Route::delete('logout',[AuthController::class,'logout'])->name('logout');
    Route::post('register',[AuthController::class,'register'])->name('register');
    Route::post('request-password',[AuthController::class,'requestPassword'])->name('request-password');
    Route::put('reset-password',[AuthController::class,'resetPassword'])->name('reset-password');
    Route::post('verify',[SMSController::class,'verifyOtp']);
});
Route::group(['prefix'=>'user', 'middleware'=>'auth:api'], function () {
    Route::post('attend',[UserController::class,'markAttendance']);
    Route::get('group',[UserController::class,'getUserGroup']);
    Route::post('type',[UserController::class,'getUserType']);
    Route::post('update',[UserController::class,'update']);
    Route::post('referral',[UserController::class,'referral']);
});
Route::group(['middleware'=>'auth:api'], function (){
    Route::get('user',[AuthController::class,'getUser'])->name('user');
    Route::post('users',[UserController::class,'getUsers']);
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
    Route::post('about',[UserController::class,'about']);
    Route::post('office',[UserController::class,'office']);
    Route::post('bank',[UserController::class,'bank']);
    Route::post('getOTP',[SMSController::class,'sendOTP']);
    Route::post('verify',[SMSController::class,'verifyOtp']);
    Route::post('getEmailOTP',[EmailController::class,'sendEmailOtp']);
    Route::post('verifyEmail',[EmailController::class,'verifyEmail']);
});

Route::group(['prefix'=>'services', 'middleware'=>'auth:api'], function (){
    Route::get('service/{userId}',[ServiceController::class,'index']);
    Route::get('myService',[ServiceController::class,'getUserServices']);
    Route::post('service',[ServiceController::class,'assignService']);
    Route::post('iciciKyc',[AepsController::class,'iciciKyc']);
    Route::post('checkKyc',[AepsController::class,'iciciKYCStatusCheck']);
    Route::post('initTransaction',[AepsController::class,'initTransaction']);
    Route::post('transaction',[AepsController::class,'myAepsTransaction']);
    Route::get('onboarded',[AepsController::class,'getBCOnboarded']);


});
Route::group(['prefix'=>'ICICIAeps'], function (){
    Route::get('CheckStatus',[AepsController::class,'checkStatus']);
    Route::get('UpdateStatus',[AepsController::class,'updateStatus']);
    Route::get('checkTxnStatus/{transactionId}',[AepsController::class,'checkAePSTxnStatus']);
    Route::get('eventTest/{transactionId}',[AepsController::class,'eventTest']);
});


Route::group(['prefix'=>'wallet','middleware'=>'auth:api'], function(){
    Route::post('myBalance',[WalletController::class,'checkBalance']);
    Route::post('statement',[WalletController::class,'walletTransaction']);
    Route::post('initSettlement',[WalletController::class,'initWalletSettlement']);
    Route::get('bankSettlement',[WalletSettlementController::class,'getLastSettlement']);
    Route::post('getPayout',[WalletController::class,'getPayout']);
    Route::post('verifyAccount',[WalletController::class,'beneVerification']);

});

Route::group(['prefix'=>'admin','middleware'=>['admin','auth:api']], function (){
    Route::get('wallet',[WalletController::class,'walletBalance']);
    Route::get('userWallet',[WalletController::class,'userWiseBalance']);
    Route::get('todayBusiness',[WalletController::class,'todayBusiness']);
});


Route::get('pinCode/{code}',[PinCodeController::class,'fetch']);
Route::get('type',[BusinessTypeController::class,"index"]);
Route::post('type',[BusinessTypeController::class,"store"]);
Route::get('state',[PinCodeController::class,'getState']);
Route::post('district',[PinCodeController::class,'getDistrict']);
Route::get('callStatus',[PinCodeController::class,'getCallStatus']);
Route::post('getBank',[IFSCController::class,'getBankDetails']);


/*RBP FINIVIS*/
Route::group(['prefix'=>'rbp','middleware'=>'auth:api'],function (){
    Route::get('rbpAuth',[RBPController::class,'authorisation']);
    Route::get('bank',[RBPController::class,'bankIIN']);
    Route::get('state',[RBPController::class,'state']);
    Route::post('district',[RBPController::class,'district']);
    Route::post('merchantRegistration',[RBPController::class,'registration']);
    Route::post('merchantStatus',[RBPController::class,'status']);
    Route::post('customer',[RBPController::class,'customer_registration']);
    Route::post('aepsTransaction',[RBPController::class,'transaction']);
});

/*Pichan Verification*/
Route::group(['prefix'=>'verify','middleware'=>'auth:api'], function (){
    Route::post('pan',[VerifyController::class,'verifyPan']);
    Route::post('aadhaar',[VerifyController::class,'verifyAadhaar']);
    Route::post('account',[VerifyController::class,'accountVerify']);
});

