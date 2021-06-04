<?php

namespace App\Listeners\Verification;

use App\Http\Controllers\cms\UserController;
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
    public function handle($event)
    {
        try {
            $response= curl($event->url,'POST', json_encode($event->postData), $event->header);
            $request= new Request();
            $request->setMethod('POST');
            $request->request->add($response);
            $user=new UserController();
            $user->testRequest($request);
        }catch (\Exception $exception){
            logger($exception->getMessage());
        }


    }
}
