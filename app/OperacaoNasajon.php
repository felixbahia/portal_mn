<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OperacaoNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.operacoes';
    public $timestamps = false;
    public $incrementing = false;
    public $primaryKey = 'operacao';
    protected $keyType = 'string';

    public $guarded = ['operacao', 'codigo', 'descricao', 'sinal', 'afetacustodosprodutos', 'grupodeoperacao', 'usatabeladepreco', 'associardocumento', 'id_documento', 'associarproduto', 'finalidade', 'ativa', 'tipooperacao', 'requisicao', 'simularimpostos', 'simularfrete', 'objetivodaoperacao', 'emitirnfe', 'nropedidoexterno', 'gerafinanceiro', 'formagerafinanceiro', 'cfoppadrao_estadual', 'cfoppadrao_interestadual', 'cfoppadrao_exterior', 'modalidadefrete', 'id_markup', 'gerar_ra', 'usamodulocompras', 'processarautomaticamente', 'layoutdanfe', 'layoutvisualizacao', 'semfatura_semtitulo', 'exibeqtdassociadarestante', 'modoexibicaoproduto', 'exibenumseriedescproddanfe', 'aprovaitens', 'parcelabaseadaemissaodoc', 'comportamentooperacao', 'idoperacaopadraofaturamento', 'idoperacaopadraoremessa', 'cfoppadrao_interestadualst', 'cfoppadrao_estadualst', 'associacaoobrigatoriadocumentos', 'diretoriocopiaxmlemitido', 'usatabeladeprecoporitem', 'codigonumericochaveacesso', 'id_grupo_empresarial', 'id_empresa', 'gerar_ordem_producao', 'usaultimoprecopraticado', 'calcular_ibpt', 'exigirchavereferencia', 'validarchavereferencia', 'textoobsnumeroexterno', 'naturezabehavior', 'natureza', 'permitireditarnatureza', 'interno', 'lastupdate', 'tenant', 'situacaonumerosdeseries', 'gerarnumeroautomaticamentepedido', 'diversos_participantes', 'controla_triangulacao', 'faturarapenasimpostos', 'geraprojetopcp', 'habilitarrateioentrega', 'faturartotaldocumento', 'permitir_grupo_inventario_mercadoria', 'permitir_grupo_inventario_materia_prima', 'permitir_grupo_inventario_produto_intermediario', 'permitir_grupo_inventario_material_embalagem', 'permitir_grupo_inventario_prod_acabado_manufaturado', 'permitir_grupo_inventario_prod_fase_fabricacao', 'permitir_grupo_inventario_bens_terceiros', 'permitir_grupo_inventario_ativo_permanente', 'permitir_grupo_inventario_uso_e_consumo', 'permitir_grupo_inventario_outros_sem_inv'];


}
