<?php

namespace App\Listeners\Wallet;

use App\Http\Controllers\services\WalletController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class WalletOperationListener
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
        $wallet=new WalletController();
        $walletResult=$wallet->walletOperationaeps($transaction);
        //$result=json_decode($walletResult->getContent());
        logger($walletResult);
        if($walletResult!=false){
            $commissionResponse=$wallet->walletAepsCommission($walletResult);
            logger($commissionResponse);
        }


    }
}
