<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FracoesNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.vw_fracoes';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'fracao';
    
    protected $fillable = [
        'fracao', 
        'codigo', 
        'produto',
        'estabelecimento',
        'tipo_proprietario',  
        'proprietario',
        'tipo_detentor', 
        'detentor',
        'localdeestoque',
        'tipodefracao',
        'quantidade',
        'situacao',
        'ultima_docfis',
        'numero_ultima_docfis',
        'dataentrada',
        'fracao_origem',
        'ultima_quantidade',
        'lastupdate',
        'proprietario_nome',
        'detentor_nome',
        'le_codigo',
        'le_nome',
        'produto_codigo',
        'produto_especificacao',
        'unidade',
        'unidade_codigo',
        'tipodefracao_codigo',
        'localdeestoqueendereco',
        'endereco',
        'codigo_pai',
    ];

    public function estabelecimentoProprietarioDetalhes(){
        return $this->hasOne('App\NasajonEstabelecimento', 'estabelecimento', 'proprietario');
    }
}
