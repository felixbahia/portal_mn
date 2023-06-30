<?php

namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Laudo extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    //protected $table = 'laudos';

    protected $fillable = [
        'produto_grupos_id', 'caracteristicas', 'tamanho_pecas', 'origem', 'pdf_laudo',  'created_by', 'updated_by', 'deleted_by'
       ,'status'
       ,'gramatura_linear'
       ,'rendimento'
       ,'encolhimento'
       ,'titulo_trama'
        ,'titulo_urdume'
       ,'informacao_adicional'
       ,'img_instrucoes_lavagem'
       ,'thumb_instrucoes_lavagem'
       ,'ligamento'
       ,'construcao'
    ];
    


    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    public function grupo(){
        return $this->hasOne('App\ProdutoGrupo', 'id','produto_grupos_id');
    }

    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
