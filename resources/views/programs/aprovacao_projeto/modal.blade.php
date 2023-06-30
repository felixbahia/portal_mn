@extends('layouts.page-dialog')

@section('content')

    <div id="tudo">
        <div class="border-bottom" id="info-sintese" >
            <div class="row">
                <div class="row mx-1 border-bottom titulo-bootstrap">
                    <div class="col-lg-12"><b>Projeto:</b> {!! $return['projeto'] !!} - <b>Cliente:</b> {!! $return['cliente'] !!} - <b>Valor:</b> R$ {!! $return['valor'] !!} - <b>Código do Vendedor:</b> {!! $return['vendedor']??'Sem código definido' !!}</div>
                </div>
                <hr>
                @if($return['mostrar_botao'] === true)
                <div class="row-botoes-aprovacao-pedidos">
                    <div class="bt-reprove" data-toggle="tooltip" data-trigger='hover' title="Reprovar" onclick="recusarProjeto('{{ $return['id_aprovacao'] }}')"></div>
                    <div class="bt-aprove" data-toggle="tooltip" data-trigger='hover' title="Aprovar" onclick="aprovarProjeto('{{ $return['projeto'] }}')"></div>
                </div>
                <hr>
                @endif
                <div class="col-lg-6 my-6" id='posicao_financeira'>
                    <div class="row">
                        <div class="col-lg-12">
                            <h5 class='titulo-credito'>Posição Financeira Atual <button id='btn-posicao-sintetica' type='button' class="btn btn-primary btn-xs ml-5" onclick="posicaoSinteticaModal()">Análise sintética</button> </h5>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Médio de atraso</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['media_atraso'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Maior atraso</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['maior_atraso'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Inatividade</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['inatividade'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Limite de Crédito</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['limite_credito'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Crédito válido ate</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['data_limite_credito'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Limite de Crédito Disponível</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['limite_disponivel'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Pedidos em aberto</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['pedidos_em_aberto'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Duplicatas em aberto</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['duplicatas_em_aberto'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Duplicatas vencidas (dias)</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['duplicatas_vencidas']['dias'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Último atraso</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['ultimo_atraso'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Cheques em aberto - Lucros e perdas</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['lucros_e_perdas'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Cheques em negociação</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['negociacao'] !!}
                        </div>
                    </div>	
                    @if (!empty($return['notas_credito']))
                    <div class="row">
                        <div class="col-lg-6">
                            <b>Notas de crédito</b>
                        </div>
                        <div class="col-lg-6">
                            {!! $return['notas_credito'] !!}
                        </div>
                    </div>	
                    @endif
                    @if (!empty($return['alerta']))
                    <div class="row">
                        <div class="col-lg-12 alert-message mt-3">
                            <p>{!! $return['alerta'] !!}</p>
                        </div>
                    </div>
                    @endif
                </div>
                <div class="col-lg-6 my-6">
                    <div class="row border-bottom">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class='titulo-credito'>Preços e condições</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Comissão</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['comissao'] !!}</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Condição de Pagamento</b> 
                                </div>
                                <div class="col-lg-4">
                                    {!! $return['condicao_pagamento'] !!}
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Preço Médio de Venda</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['preco_medio_venda'] !!}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Valor do Pedido</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['valor'] !!}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Custo Tecido</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['total_tecido'] !!}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Custo Insumo</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['total_insumo'] !!}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Custo Mão de Obra</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['total_faccao'] !!}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Frete Adicional</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['frete_adicional'] !!}</div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Custo Total do Pedido</b> 
                                </div>
                                <div class="col-lg-4">
                                    <div class="text-right">{!! $return['custo_total'] !!}</div>
                                </div>
                            </div>

                            @if (isset($return['desconto']))
                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Desconto</b> 
                                </div>
                                <div class="col-lg-4">
                                    {!! $return['desconto_em_nota'] !!}
                                </div>
                            </div>
                            @endif
                        
                        </div>
                    </div>
                    <div class="row my-2">
                        <div class="col-lg-12 border-bottom">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class='titulo-credito'>Informações Adicionais</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Tipo de Frete</b>
                                </div>
                                <div class="col-lg-8">
                                    {!! $return['tipo_frete'] !!}
                                </div>					
                            </div>
                        
                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Vendedor</b>
                                </div>
                                <div class="col-lg-8">
                                    {!! $return['usuario'] !!}
                                </div>					
                            </div>

                            @if (!empty($return['erro_integracao']))
                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Erro de integração</b>
                                </div>
                                <div class="col-lg-8">
                                    {!! $return['erro_integracao'] !!}
                                </div>					
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="row my-2">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-12">
                                    <h5 class='titulo-credito'>Informações de prazo</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-4">
                                    <b>Prazo médio do pedido</b>
                                </div>
                                <div class="col-lg-8">
                                    {!! $return['prazo_pedido'] !!}
                                </div>
                            </div>						
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-3">
        </div>
        <div class="content-dialog-table" style="overflow: auto">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" id='projeto-header-tab' data-toggle="tab" href="#projeto_header" role="tab" aria-controls="projeot_header" aria-selected="true">Itens do Projeto</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="projeto-itens-tab" data-toggle="tab" href="#projeto_detalhes" role="tab" aria-controls="projeto_detalhes" aria-selected="false">Detalhes de Produção</a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="ProjetoHeaderContainer">
                <div class="tab-pane show active" id="projeto_header" role="tabpanel" aria-labelledby="dados-tab">
                    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-pedidos-itens">
                        <thead>
                            <tr>
                                <th>Descrição do Produto</th>
                                <th>Detalhe de Produção</th>
                                <th class="tb_number">Quantidade</th>
                                <th class="tb_number">Preço de Venda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($return['produtos'] as $produto)
                            <tr>
                                <td>{{ $produto['nome'] }}</td>
                                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['detalhes'] }}'>{{ $produto['detalhes'] }}</div></div></td>
                                <td class="tb_number">{{ $produto['quantidade'] }}</td>
                                <td class="tb_number">{{ $produto['preco_venda'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="tab-pane" id="projeto_detalhes" role="tabpanel" aria-labelledby="dados-tab">
                        @if(!empty($return['tecidos']))
                        <label>Tecidos</label>
                        <table class="table table-striped table-not-edit" id="table-dialog-tecidos">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descrição</th>
                                    <th>Ref. Produto</th>
                                    <th class="tb_number">Consumo por Peça</th>
                                    <th class="tb_number">Consumo Total</th>
                                    <th class="tb_number">Custo Unitário</th>
                                    <th class="tb_number">Custo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($return['tecidos'] as $tecido)
                                <tr>
                                    <td>{{ $tecido['codigo'] }}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['descricao'] }}'>{{ $tecido['descricao'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $tecido['referencia_produto'] }}'>{{ $tecido['referencia_produto'] }}</div></div></td>
                                    <td class="tb_number">{{ $tecido['consumo_por_peca'] }}</td>
                                    <td class="tb_number">{{ $tecido['consumo_total'] }}</td>
                                    <td class="tb_number">{{ $tecido['custo_unitario'] }}</td>
                                    <td class="tb_number">{{ $tecido['custo_total'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                            </tfoot>
                        </table>
                        <br>
                    @endif
            
                    @if(!empty($return['insumos']))
                        <label>Insumos/Acessórios</label>
                        <table class="table table-striped table-not-edit table-not-view" id="table-dialog-insumos">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descrição</th>
                                    <th>Ref. Produto</th>
                                    <th class="tb_number">Consumo por Peça</th>
                                    <th class="tb_number">Consumo Total</th>
                                    <th class="tb_number">Custo Unitário</th>
                                    <th class="tb_number">Custo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($return['insumos'] as $insumo)
                                <tr>
                                    <td>{{ $insumo['codigo'] }}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['descricao'] }}'>{{ $insumo['descricao'] }}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $insumo['referencia_produto'] }}'>{{ $insumo['referencia_produto'] }}></div></div></td>
                                    <td class="tb_number">{{ $insumo['consumo_por_peca'] }}</td>
                                    <td class="tb_number">{{ $insumo['consumo_total'] }}</td>
                                    <td class="tb_number">{{ $insumo['custo_unitario'] }}</td>
                                    <td class="tb_number">{{ $insumo['custo_total'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                            </tfoot>
                        </table>
                        <br>
                    @endif
            
                    @if(!empty($return['faccoes']))
                        <label>Facções Serviços</label>
                        <table class="table table-striped table-not-edit table-not-view" id="table-dialog-faccoes">
                            <thead>
                                <tr>
                                    <th>Ref. Produto</th>
                                    @if(!empty($return['com_faccao']))
                                        <th>CNPJ</th>
                                        <th>Facção</th>
                                    @endif
                                    <th>Tipo de Serviço</th>
                                    <th class="tb_number">Custo Unitário</th>
                                    <th class="tb_number">Quantidade</th>
                                    <th class="tb_number">Custo Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($return['faccoes'] as $faccao)
                                <tr>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['referencia_produto'] }}'>{{ $faccao['referencia_produto'] }}</div></div></td>
                                    @if(!empty($return['com_faccao']))
                                        <td>{{ $faccao['cnpj'] }}</td>
                                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['faccao'] }}'>{{ $faccao['faccao'] }}</div></div></td>
                                    @endif
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $faccao['tipo_de_servico'] }}'>{{ $faccao['tipo_de_servico'] }}</div></div></td>
                                    <td class="tb_number">{{ $faccao['custo_unitario'] }}</td>
                                    <td class="tb_number">{{ $faccao['quantidade'] }}</td>
                                    <td class="tb_number">{{ $faccao['custo_total'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                            </tfoot>
                        </table>
                        <br>
                    @endif
                </div>
            </div>
        </div>
    </div>

<script>
	
	table_filters_itens = $('#table-filters-itens').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		'paging': false,
		"language": {
			"decimal":        ".",
			"emptyTable":     "Nenhum registro encontrado",
			"infoPostFix":    "",
			"thousands":      ",",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum registro encontrado",
			"paginate": {
				"first":      "<<",
				"last":       ">>",
				"next":       ">",
				"previous":   "<"
			}
		},
		"columnDefs": [
			{
				"class": "tb_number", 
				"targets": "tb_number"
			},
		],
		"order": [[ 2, 'asc' ]]
	});

	function posicaoSinteticaModal(){

		$.ajax({
			url: '{{ route('cliente.posicao_sintetica.modal') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				id_encriptada: '{{ $return['id_encriptada'] }}'
			},

		})
		.done(function(data) {
			$id = 'modal_analise_cliente';
			$title = 'Posição sintética do cliente';
			$body = data;
			$class = 'modal-lg';

			createModal($id, $title, $body, $class);
		});
	}
	function addCredito(){

		$.ajax({
			url: '{{ route('cliente.limite.modal.adicionar_aprovacao') }}',
			type: 'POST',
			data: {
				_token: '{{ csrf_token() }}',
				cpf_cnpj: '{{ $return['cpf_cnpj'] }}'
			},

		})
		.done(function(data) {
			$id = 'modal_add_credito_cliente';
			$title = 'Revisar crédito cliente';
			$body = data;
			$class = '';

			createModal($id, $title, $body, $class);
		});

	}

</script>
@endsection