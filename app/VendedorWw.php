<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendedorWw extends Model
{
    protected $connection = 'srv_ww';
    protected $table = 'WWUSU';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODWEB';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'CODWEB', 'TIPO', 'CODPROT', 'CNPJ_CPF', 'EMAIL', 'FONE', 'CELULAR', 'EQUIPE', 'COMISSAO', 'ULTORC', 'NOME'
    ];
}
