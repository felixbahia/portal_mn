<?php

namespace App;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ResetSenha extends Model
{
    use SoftDeletes;
    //
    public $fillable = ['user', 'hash', 'recuperado'];

    public function usuario(){
        return $this->hasOne('App\User', 'username', 'user');
    }
}
