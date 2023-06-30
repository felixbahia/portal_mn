<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetrinApi extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'tipo', 'url', 'access_token', 'cpf_cnpj', 'uf', 'servico','created_by', 'updated_by', 'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at', 'created_at', 'updated_at'];



    public function netrinApiRetorno(){
        return $this->hasOne('App\NetrinApiRetorno', 'id_netrin_api', 'id');
    }











}
