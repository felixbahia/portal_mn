<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VendedorComissaoNota extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_df_vendedores';
    public $timestamps = false;
    public $incrementing = false;

    protected $guarded = [
        'df_vendedor',
        'id_docfis',
        'vendedor',
        'vendedor_codigo',
        'vendedor_nome',
        'percentual_comissao'
    ];

    public function nota(){
        return $this->hasOne('App\NotaVendaNasajon', 'id_nota', 'id_docfis')->where(function ($query){
            $query->where('grupodeoperacao', 'VENDA')
            ->orWhereNull('grupodeoperacao');
        });
    }

    public function usuario(){
        return $this->hasOne('App\User', 'codigo_representante', 'vendedor_codigo');
    }
}
