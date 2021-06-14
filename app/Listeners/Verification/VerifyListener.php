<?php

namespace App\Listeners\Verification;

use App\Http\Controllers\cms\UserController;
use App\Http\PostCaller;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

class VerifyListener implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)    {
        try {
            $response= curl($event->url,'POST', json_encode($event->postData), $event->header);

            if($response['response']){
                if(gettype($response['data'])=="object"){
                    $data=json_decode(json_encode($response['data']), true);
                    $post= new PostCaller(UserController::class,'testRequest',Request::class,$data);
                    $post->call();
                }
            }
        }catch (\Exception $exception){
            logger($exception->getMessage());
        }
    }

}
