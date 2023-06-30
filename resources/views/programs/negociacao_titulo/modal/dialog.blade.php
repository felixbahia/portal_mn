@extends('layouts.page-dialog')

@section('content')
<ul class="nav nav-tabs">
	<li class="nav-item">
		<a class="nav-link active" id='renegociacao-titulo-detalhes-tab' data-toggle="tab" href="#renegociacao_titulo_detalhes" role="tab" aria-controls="renegociacao_titulo_detalhes" aria-selected="true">Detalhes</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" id="renegociacao-titulo-avalistas-tab" data-toggle="tab" href="#renegociacao_titulo_avalistas" role="tab" aria-controls="renegociacao_titulo_avalistas" aria-selected="false">Avalistas</a>
	</li>
</ul>
<form action="" name="form_modal_importacao" id="form_modal_importacao" onsubmit="return false;">
    <div class="tab-content pt-3" id="container">
        <div class="tab-pane show active" id="renegociacao_titulo_detalhes" role="tabpanel" aria-labelledby="dados-tab">
            @if (!empty($dados['motivo']))
            <div class="alert alert-danger" role="alert">
                Motivo: {!!$dados['motivo']!!}</p> 
            </div>
            @endif
            <div class="col-lg-12">			
                <div class='pedido_detalhes_content'>
                    <div class="row">
                        <div class="col-sm-11">
                            <b>Cliente:</b><br>
                            {!! $dados['cliente'] !!}
                        </div>
                        <div class="col-sm-1 float-right mt-3">
                            <a href="{{ $dados['link_documentacao'] }}" target="_blank" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="right" title="Informações dos cálculos" style="color: black"></a><br/>
                        </div>
                    </div>
            
                    <div class="row">
                        <div class="col-sm-12">
                            <b>Data:</b><br>
                            {!! $dados['data'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-2">
                            <b>Valor Títulos Renegociados:</b><br>
                            {!! $dados['total_titulo_antigo'] !!}
                        </div>
                        <div class="col-sm-2">
                            <b>Juros por Mês Atualização:</b><br>
                            {!! $dados['juros_atualizacao'] !!}
                        </div>
                        <div class="col-sm-3">
                            <b>Valor Títulos Renegociado Atualizados:</b><br>
                            {!! $dados['total_titulo_antigo_atualizado'] !!}
                        </div>
                        <div class="col-sm-2">
                            <b>Juros por Mês Novo:</b><br>
                            {!! $dados['juros_mes'] !!}
                        </div>
                        <div class="col-sm-3">
                            <b>Valor Títulos Novos:</b><br>
                            {!! $dados['valor_total_juros'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <b>Quantidade de Parcelas:</b><br>
                            {!! $dados['quantidade_parcela'] !!}
                        </div>
                        <div class="col-sm-4">
                            <b>Período:</b><br>
                            {!! $dados['periodo'] !!}
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <b>Titulos Renegociados:</b><br>
                        </div>
                        <div class="content-dialog-table">
                            <div class="content-table">
                                <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                                    <thead>
                                        <tr>
                                            <th>Estabelecimento</th>
                                            <th>Título</th>
                                            <th>Parcela</th>
                                            <th>Data Emissão</th>
                                            <th>Data Vencimento</th>
                                            <th>Novo Vencimento</th>
                                            <th>Nº Dias</th>
                                            <th>Valor Original</th>
                                            <th>Nota</th>
                                            <th>Taxa Encargo</th>
                                            <th>Valor Encargo/dia</th>
                                            <th>Valor Enc. Período</th>
                                            <th>Tar. Banc. MN</th>
                                            <th>Valor Atualizado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($dados['titulos'] as $titulo)
                                            <tr>
                                                <td>{!! $titulo['estabelecimento'] !!}</td>
                                                <td>{!! $titulo['titulo'] !!}</td>
                                                <td class="tb_number">{!! $titulo['parcela'] !!}</td>
                                                <td class="tb_date">{!! $titulo['data_emissao'] !!}</td>
                                                <td class="tb_date">{!! $titulo['data_vencimento'] !!}</td>
                                                <td class="tb_date">{!! $titulo['novo_vencimento'] !!}</td>
                                                <td class="tb_number">{!! $titulo['intervalo_total_dias'] !!}</td>
                                                <td class="tb_number">{!! $titulo['valor_original'] !!}</td>
                                                <td>{!! $titulo['nota'] !!}</td>
                                                <td class="tb_number">{!! $titulo['taxa_encargo'] !!}</td>
                                                <td class="tb_number">{!! $titulo['valor_encargo_diario'] !!}</td>
                                                <td class="tb_number">{!! $titulo['valor_encargo'] !!}</td>
                                                <td class="tb_number">{!! $titulo['tarifa_bancaria'] !!}</td>
                                                <td class="tb_number">{!! $titulo['valor_atualizado'] !!}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if(!empty($dados['titulo_novos'] && isset($dados['titulo_novos'][0]['parcela_sem_honorario'])))
                        <div class="row">
                            <div class="col-sm-12">
                                <b>Titulos Novos:</b><br/>
                                <b>Encargos: </b>{{ $dados['encargos'] }}
                            </div>
                            <div class="content-dialog-table">
                                <div class="content-table">
                                    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                                        <thead>
                                            <tr>
                                                <th rowspan="2">Estabelecimento</th>
                                                <th rowspan="2">Parcela</th>
                                                @if(!empty($dados['titulo_novos'][0]['titulo']))
                                                    <th rowspan="2">Título</th>
                                                @endif
                                                <th rowspan="2">Parc. S/ Enc.</th>
                                                <th rowspan="2">Data Acordo</th>
                                                <th rowspan="2">Data Vencimento</th>
                                                <th rowspan="2">Nº Dias</th>
                                                <th rowspan="2">Parc. S/ Hono.</th>
                                                <th colspan="2">Juros MN</th>
                                                <th colspan="3">Juros Ragazzi</th>
                                                <th rowspan="2">Encargo Total</th>
                                                <th rowspan="2">Valor</th>
                                            </tr>
                                            <tr>
                                                <th>Encargo/Dia</th>
                                                <th>Encargo/Periodo</th>
                                                <th class="border-left">Encargo</th>
                                                <th>Encargo/Dia</th>
                                                <th>Encargo/Periodo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dados['titulo_novos'] as $titulo)
                                                <tr>
                                                    @if(!isset($titulo['titulo']))
                                                        <td>{!! $titulo['estabelecimento'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['parcela'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['parcela_sem_encargos'] !!}</td>
                                                        <td class="tb_date">{!! $titulo['data_renegociacao'] !!}</td>
                                                        <td class="tb_date">{!! $titulo['data_vencimento'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['intervalo_dias'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['parcela_sem_honorario'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_dia'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_periodo'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_ragazzi'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_dia_ragazzi'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_periodo_ragazzi'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_total'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['valor_parcela'] !!}</td>
                                                    @else
                                                        <td>{!! $titulo['estabelecimento'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['parcela'] !!}</td>
                                                        <td>{!! $titulo['titulo'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['parcela_sem_encargos'] !!}</td>
                                                        <td class="tb_date">{!! $titulo['data_renegociacao'] !!}</td>
                                                        <td class="tb_date">{!! $titulo['data_vencimento'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['intervalo_dias'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['parcela_sem_honorario'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_dia'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_periodo'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_ragazzi'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_dia_ragazzi'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_periodo_ragazzi'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['encargo_total'] !!}</td>
                                                        <td class="tb_number">{!! $titulo['valor_parcela'] !!}</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @elseif(!empty($dados['titulo_novos'] && !isset($dados['titulo_novos'][0]['parcela_sem_honorario'])))
                        <div class="row">
                            <div class="col-sm-12">
                                <b>Titulos Novos:</b><br>
                                <b>Encargos: </b>{{ $dados['encargos'] }}
                            </div>
                            <div class="content-dialog-table">
                                <div class="content-table">
                                    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                                        <thead>
                                            <tr>
                                                <th>Estabelecimento</th>    
                                                <th>Parcela</th>
                                                <th>Título</th>
                                                <th>Vencimento</th>
                                                <th>Juros</th>
                                                <th>Tarifa Bancária</th>
                                                <th>Encargos</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dados['titulo_novos'] as $titulo)
                                                <tr>
                                                    <td>{!! $titulo['estabelecimento'] !!}</td>
                                                    <td class="tb_number">{!! $titulo['parcela'] !!}</td>
                                                    <td class="tb_number">{!! $titulo['titulo'] !!}</td>
                                                    <td class="tb_date">{!! $titulo['data_vencimento'] !!}</td>
                                                    <td class="tb_number">{!! $dados['juros_mes'] !!}</td>
                                                    <td class="tb_number">{!! $dados['tarifa_bancaria_renegociacao'] !!}</td>
                                                    <td class="tb_number">{!! $dados['encargos'] !!}</td>
                                                    <td class="tb_number">{!! $titulo['valor_parcela'] !!}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-sm-12">
                                <b>Titulos Novos:</b><br>
                                <b>Encargos: </b>{{ $dados['encargos'] }}
                            </div>
                            <div class="content-dialog-table">
                                <div class="content-table">
                                    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                                        <thead>
                                            <tr>
                                                <th>Parcela</th>
                                                <th>Vencimento</th>
                                                <th>Juros</th>
                                                <th>Tarifa Bancária</th>
                                                <th>Encargos</th>
                                                <th>Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($dados['datas'] as $parcela)
                                                <tr>
                                                    <td class="tb_number">{!! $parcela['numero'] !!}</td>
                                                    <td class="tb_date">{!! $parcela['data'] !!}</td>
                                                    <td class="tb_number">{!! $dados['juros_mes'] !!}</td>
                                                    <td class="tb_number">{!! $dados['tarifa_bancaria_renegociacao'] !!}</td>
                                                    <td class="tb_number">{!! $dados['encargos'] !!}</td>
                                                    <td class="tb_number">{!! $parcela['valor'] !!}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="tab-pane show" id="renegociacao_titulo_avalistas" role="tabpanel" aria-labelledby="dados-tab">
            <div class="content-dialog-table">
                <div class="content-table">
                    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-projetos-itens">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>E-mail</th>
                                <th>Endereço</th>
                                <th>Signatario</th>
                                <th>Estado Civil</th>
                                <th>Nome Vênia</th>
                                <th>CPF Vênia</th>
                                <th>Data Assinatura</th>
                                <th>Status</th>
                                <th>Contrato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dados['avalistas'] as $index => $avalista)
                                <tr>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{!! $avalista['nome'] !!}'>{!! $avalista['nome'] !!}</div></div></td>
                                    <td>{!! $avalista['cpf'] !!}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{!! $avalista['email'] !!}'>{!! $avalista['email'] !!}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{!! $avalista['endereco'] !!}'>{!! $avalista['endereco'] !!}</div></div></td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{!! $avalista['signatario'] !!}'>{!! $avalista['signatario'] !!}</div></div></td>
                                    <td>{!! $avalista['estado_civil'] !!}</td>
                                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{!! $avalista['nome_venia_conjugal'] !!}'>{!! $avalista['nome_venia_conjugal'] !!}</div></div></td>
                                    <td>{!! $avalista['cpf_venia_conjugal'] !!}</td>
                                    <td>{!! $avalista['data_assinatura'] !!}</td>
                                    <td class="tb_date"><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{!! $avalista['assinatura'] !!}'>{!! $avalista['assinatura'] !!}</div></div></td>
                                    <td>
                                        @if(!empty($avalista['documento_assinado']))
                                            <a href="{{ asset($avalista['documento_assinado']) }}" target="_blank"><i class="btn-pdf"></i></a>
                                        @else
                                            {!! $avalista['documento_assinado'] !!}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection