<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotivoFinanceiro extends Model
{
    use SoftDeletes;
    protected $table = 'motivos_financeiros';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'id','motivo'
    ];
}
