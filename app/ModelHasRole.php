<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelHasRole extends Model
{
    protected $fillable = [
        'role_id', 'model_type', 'model_id'
    ];

    public function detalhesRoles(){
        return $this->hasOne('App\Role', 'id', 'role_id');
    }
}
