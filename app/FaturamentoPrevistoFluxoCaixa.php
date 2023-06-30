<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaturamentoPrevistoFluxoCaixa extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'mes_ano', 'valor', 'created_by', 'updated_by', 'deleted_by'
    ];
    protected $dates = ['mes_ano', 'deleted_at'];

}
