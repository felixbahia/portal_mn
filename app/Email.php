<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use SoftDeletes;
    protected $fillable = ['estabelecimento', 'title_view', 'token_email', 'email_sender', 'emails_send', 'emails_cc', 'emails_bcc', 'subject', 'body', 'template_email', 'variaveis_template', 'created_by', 'updated_by', 'deleted_by'];

    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
