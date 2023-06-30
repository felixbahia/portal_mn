<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ItensOrcamento extends Model
{
    protected $connection = 'srv_ww';
    protected $table = 'WWORI';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['CODWEB', 'NUMORC', 'CODPRD'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'CODWEB', 'NUMORC', 'CODPRD', 'QTDPED', 'PU_LIQ', 'QTDPEND', 'PRECO_ABC', 'APROVACAO_PRC', 'COMISSAO', 'OK_DO_PRECO', 'DH_OK_PRECO', 'DESCONTO'
    ];

    public function orcamento(){
        return $this->belongsTo(Orcamentos::class, 'CODWEB', 'CODWEB')->where('NUMORC',$this->NUMORC);
    }

    public function produto(){
        return $this->hasOne('App\Produto', 'CODPRD', 'CODPRD');
    }
}
