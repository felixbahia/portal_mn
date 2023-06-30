<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComissaoAlteradaCampanhaZeraEstoque extends Model
{
    use SoftDeletes;
    protected $connection = 'pgsql';
    protected $table = 'comissao_alterada_campanha_zera_estoque';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'nota_uuid', 'nota_numero', 'comissao_anterior', 'comissao_atual'
    ];
}
