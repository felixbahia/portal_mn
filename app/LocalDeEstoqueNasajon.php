<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LocalDeEstoqueNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.locaisdeestoques';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'localdeestoque';
    protected $fillable = [
        'localdeestoque', 'estabelecimento', 'codigo', 'nome', 'tipo', 'tipologradouro', 'logradouro', 'numero', 'complemento', 'cep', 'bairro', 'referencia', 'ibge', 'cidade', 'uf', 'cnpj', 'enderecodiferente', 'lastupdate', 'tenant'
    ];
}
