<?php

namespace App\Listeners\Wallet;

use App\Http\Controllers\services\AepsController;
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
        logger($event->ICICIAEPSTransaction);
        $transaction=$event->ICICIAEPSTransaction;
        $wallet=new WalletController();
        $walletResult=$wallet->walletOperationaeps($transaction);
        //$result=json_decode($walletResult->getContent());
        logger($walletResult);
        if($walletResult!=false){
            $aeps=new AepsController();
            $aeps->walletUpdatedFromICICIAEPS($transaction, $walletResult);
            $commissionResponse=$wallet->walletCommission($walletResult, "Aeps Commission","cr");
            logger($commissionResponse);
        }


    }
}
