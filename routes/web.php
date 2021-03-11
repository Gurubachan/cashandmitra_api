<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
//    return view('welcome');
    return response('<h1 style="text-align: center">CASHAND Server Running Properly</h1>');
});

//Clear Cache facade value:
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('cache:clear');
    return '<h1>Cache facade value cleared</h1>';
});

//Reoptimized class loader:
Route::get('/optimize', function() {
    $exitCode = Artisan::call('optimize');
    return '<h1>Reoptimized class loader</h1>';
});

//Route cache:
Route::get('/route-cache', function() {
    $exitCode = Artisan::call('route:cache');
    return '<h1>Routes cached</h1>';
});

//Clear Route cache:
Route::get('/route-clear', function() {
    $exitCode = Artisan::call('route:clear');
    return '<h1>Route cache cleared</h1>';
});

//Clear View cache:
Route::get('/view-clear', function() {
    $exitCode = Artisan::call('view:clear');
    return '<h1>View cache cleared</h1>';
});

//Clear Config cache:
Route::get('/config-cache', function() {
    $exitCode = Artisan::call('config:cache');
    return '<h1>Clear Config cleared</h1>';
});
//Clear Config cache:
Route::get('/config-clear', function() {
    $exitCode = Artisan::call('config:clear');
    return "<h1>Clear Config date('Y-m-d H:i:s')</h1>";
});
Route::get('/storage-link', function (){
    $exitCode= Artisan::call('storage:link');
    return '<h1>Storage link created</h1>';
});
Route::get('/clear-compiled', function (){
    $exitCode= Artisan::call('clear-compiled');
    return '<h1>Compiled services and packages files removed!
</h1>';
});
Route::get('/dump-autoload', function (){
    exec('composer dump-autoload');
    return '<h1>Dump autoload executed
</h1>';
});
Route::get('/time', function (){
    return date('Y-m-d H:i:s');
});
Route::get('/test', function (){
    $time= strtotime("2021-01-22 21:30:00");
    $ctime=strtotime("2021-01-22 22:00:00");
    return $data=['time'=>$time,
        'now'=>$ctime,
        'ntime'=>date("Y-m-d H:i:s"),
        'diff'=>$time-$ctime
        ];
});
