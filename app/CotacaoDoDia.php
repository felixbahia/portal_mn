<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CotacaoDoDia extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBMOE1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['COD_REF', 'ANO_REF', 'MES_REF', 'DIA_REF'];

    protected $fillable = ['COD_REF', 'ANO_REF', 'MES_REF', 'DIA_REF', 'NOME_REF', 'VAL_REF', 'FLAG_IAD'];

}
