<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportacaoNotaFornecedorNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'importacao.vw_importacaonota_fornecedor';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'id', 
        'fornecedor'
    ];
}
