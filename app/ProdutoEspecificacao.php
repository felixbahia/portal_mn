<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutoEspecificacao extends Model{
    
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'pgsql';
    protected $primaryKey = 'codigo_produto';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'codigo_produto', 
        'marca', 
        'linha', 
        'grupo', 
        'subgrupo', 
        'descricao', 
        'unidade', 
        'procedencia', 
        'peso', 
        'ativo', 
        'data_de_cadastro',
		'industrializado',
        'segmentos_id',
        'produto_grupos_id',
        'updated_by'
    ];

    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    
    public function produtoPrologos(){
        return $this->hasOne('App\Produto', 'CODPRD', 'codigo_produto');
    }

    public function produtoNasajon(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'codigo_produto');
    }

    public function custos(){
        return $this->hasMany('App\ProdutosCusto', 'produto_codigo', 'codigo_produto')->orderBy('estabelecimento');
    }
    
    public function informacoes_adicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'codigo_produto');
    }

    public function preco(){
        return $this->hasOne('App\Preco', 'codigo_produto', 'codigo_produto');
    }
    
    public function estoque(){
        return $this->hasMany('App\ProdutosEstoque', 'codigo_produto', 'codigo_produto');
    }

    public function promocoes(){
        return $this->hasMany('App\ProdutoPromocional', 'grupo', 'grupo')->where('data_expiracao', '>=', date('Y-m-d'));
    }

    public function produtoNovo(){
        return $this->hasOne('App\ProdutoNovo', 'codigo_produto', 'codigo_produto');
    }

    public function produtoProjeto(){
        return $this->belongsTo('App\LancamentoProjetoProduto', 'codigo_produto', 'codigo_produto')->where(function ($query){
            $query->whereHas('projeto_detalhes', function($query){
                $query->where('status', '>', 0);
            });
        });;
    }

    public function foto(){
        return $this->hasOne('App\ProdutoFoto', 'codigo_produto', 'codigo_produto');
    }

    public function ficha_tecnica(){
        return $this->hasOne('App\FichaTecnicaProduto', 'codigo_produto', 'codigo_produto');
    }

    public function movimentacao(){
        return $this->hasMany('App\Movimentacao', 'produto_codigo', 'codigo_produto');
    }

    public function entradas(){
        return $this->hasMany('App\NotaEntrada', 'codigo_produto', 'codigo_produto');
    }

    public function compras(){
        return $this->hasMany('App\ComprasNasajon', 'cod_produto', 'codigo_produto');
    }

    public function vendas(){
        return $this->hasMany('App\NotasVenda', 'codigo_produto', 'codigo_produto');
    }

    public function produtoGrupo(){
        return $this->hasOne('App\ProdutoGrupo', 'id', 'produto_grupos_id');
    }

    public function reserva(){
        return $this->hasMany('App\PedidosReservaProdutoNasajon', 'codigo_produto', 'codigo_produto');
    }

    public function itemBookVirtual(){
        return $this->hasOne('App\ItensBookVirtual', 'cod_produto', 'codigo_produto');
    }

    public function segmento(){
        return $this->hasOne('App\Segmento', 'id', 'segmentos_id');
    }

    public function produtoUsoConsumoNasajon(){
        return $this->hasOne('App\ProdutoUsoConsumoNasajon', 'codigo', 'codigo_produto');
    }

    public function produtoCompletoNasajon(){
        return $this->hasOne('App\ProdutosCompletosNasajon', 'codigo', 'codigo_produto');
    }
}
