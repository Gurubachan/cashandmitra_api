<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Verification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;

    public function __construct($data)
    {
        $this->data=$data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        try {
            return $this->from('customercare@cashand.in','Cashand Care')
                ->subject('Verification')
                ->view('email.verification')
                ->with('data',$this->data);
        }catch (\Exception $exception){
            return ['response'=>false, 'error'=>$exception->getMessage()];
        }

    }
}
