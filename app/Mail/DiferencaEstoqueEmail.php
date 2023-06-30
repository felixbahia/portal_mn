<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

class DiferencaEstoqueEmail extends Mailable{
    use Queueable, SerializesModels;

    public $attachments_send = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($attachments_send = []){
        $this->attachments_send = $attachments_send->attachments_send;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email = $this->from('portal@tecidosmn.com.br')
            ->view('mails.diferenca_estoque')
            ->text('mails.diferenca_estoque')
            ->subject("Relátorio de diferenças de estoque");
        foreach($this->attachments_send as $filePath => $fileOptions){
            chmod(storage_path("app/{$filePath}"), 777);
            $email->attach(storage_path("app/{$filePath}"), $fileOptions);
        }
        return $email;
    }
}
