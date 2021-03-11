<?php

namespace App\Listeners\ICICI;

use App\Http\Controllers\services\AepsController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckTransactionStatusListener
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
        $transaction=$event->ICICIAEPSTransaction;
        $icici= new AepsController();
        $response=$icici->checkAePSTxnStatus($transaction->id);
        logger($response);
    }
}
