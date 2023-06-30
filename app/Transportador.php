<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Transportador extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBTRA1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODTRAN';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'CODTRAN', 'VIATRAN', 'NOME', 'ENDERECO', 'BAIRRO', 'CIDADE', 'ESTADO', 'CEP', 'FONE', 'FAX', 'CGC', 'IEST', 'CODREGIAO', 'CODAREA', 'CODZONA', 'COLETA', 'ESTABEL', 'FLAG_IAD', 'FANTASIA', 'EMAIL'
    ];
}
