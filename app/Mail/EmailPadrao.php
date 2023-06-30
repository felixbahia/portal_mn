<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmailPadrao extends Mailable
{
    use Queueable, SerializesModels;

    public $attachments_send = [];
    public $body = "";
    public $from = [];
    public $subject = "";
    public $template_email = "padrao";

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email = []){
        $this->attachments_send = $email->attachments_send;
        $this->body = $email->body;
        $this->from = $email->from;
        $this->subject = $email->subject;
		$this->template_email = $email->template_email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
		if($this->template_email === 'satisfacao'){
        	$email = $this->from($this->from)->view('mails.email_padrao.satisfacao')->with(["body" => $this->body]);
		}else{
        	$email = $this->from($this->from)->view('mails.email_padrao.email')->with(["body" => $this->body]);
		}
        foreach($this->attachments_send as $filePath => $fileOptions){
            $email->attach(storage_path("app/{$filePath}"), $fileOptions);
        }
        $email->subject($this->subject);
        return $email;
    }
}
