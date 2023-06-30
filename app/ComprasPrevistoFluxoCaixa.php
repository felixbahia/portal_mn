<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComprasPrevistoFluxoCaixa extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'mes_ano', 'valor', 'mes_atual', 'mes_1','mes_2','mes_3','mes_4','mes_5','mes_6','mes_7','mes_8','mes_9','mes_10','mes_11', 'created_by', 'updated_by', 'deleted_by'
    ];
    protected $dates = ['mes_ano', 'deleted_at'];
}
