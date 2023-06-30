@extends('layouts.page-dialog')

@section('content')
@if(isset($motivo_reprovacao))
<div class="alert alert-danger" role="alert">
	<p>Esta etapa permanece pendente após análise.<br>
	Motivo: {{$motivo_reprovacao}}</p> 
</div>
@endif
<form action="#" id='form-aprovar-devolucao' onsubmit="return false;" @if($status==3)enctype="multipart/form-data"@endif>
    @csrf
    {!! Form::hidden('id', $id, ['id' => 'id_modal']) !!}
    <div class="row mt-2">
        <div class="col-sm-4">
            <b>Estabelecimento</b><br>
            {!! $estabelecimento !!}
        </div>
        <div class="col-sm-4">
            <b>Cliente</b><br>
            {!! $cliente !!}
        </div>
        <div class="col-sm-4">
            <b>Número da Nota</b><br>
            {{ $nota_fiscal }}
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-4">
            <b>Motivo</b><br>
            {!! $motivos[$motivo] !!}
        </div>
        <div class="col-sm-4">
            <b>Tipo de venda</b><br>
            {!! $tipo_venda !!}
        </div>
        <div class="col-sm-4">
            <b>Data de emissão</b><br>
            {!! $emissao !!}
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-4">
            <b>Valor</b><br>
            {!! $valor !!}
        </div>
        <div class="col-sm-4">
            <b>Tipo de Devolução</b><br>
            <span class='mt-3'>{{ $valor_parcial }}</span>
        </div>
        @if($status == 15)
        <div class="col-sm-4">
            <b>Valor da Devolução</b><br>
            <span class='mt-3'>{{ $valor_devolucao }}</span>
        </div>
        @endif
    </div>

    <div class="row mt-2">
        <div class="col"><b>Contato com o cliente:</b></div>
    </div>
    <div class="row">
        @if($status==2)
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('nome_contato_modal', 'Nome', ['class'=>'input-label']) !!}
            {!! Form::text('nome_contato', $nome_contato, ['id' => 'nome_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('telefone_contato_modal', 'Telefone', ['class'=>'input-label']) !!}
            {!! Form::text('telefone_contato', $telefone_contato, ['id' => 'telefone_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('email_contato_modal', 'E-mail', ['class'=>'input-label']) !!}
            {!! Form::text('email_contato', $email_contato, ['id' => 'email_contato_modal', 'class' => 'form-control']) !!}
        </div>
        @else
        <div class="col">
            <b>Nome:</b><br>
            {!! $nome_contato !!}
        </div>
        <div class="col">
            <b>Telefone:</b><br>
            {!! $telefone_contato !!}
        </div>
        <div class="col">
            <b>Email:</b><br>
            {!! $email_contato !!}
        </div>
        @if(!empty($nota_cliente_numero))
        <div class="col">
            <b>Número da Nota do cliente:</b><br>
            {!! $nota_cliente_numero !!}
        </div>
        @endif
        @endif
    </div>
    <div class="row mt-2">
        <div class="col-sm-4">
            @if($status==2)
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> Responsabilidade do Frete
            <div class="row mt-2">
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'textil', false, ['id' => 'responsabilidade_frete_textil', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_textil', 'Têxtil MN', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'cliente', false, ['id' => 'responsabilidade_frete_cliente', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_cliente', 'Cliente', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'representante', false, ['id' => 'responsabilidade_frete_representante', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_representante', 'Representante', ['class'=>'input-label']) !!}
                </div>
            </div>
            @elseif(isset($responsabilidade_frete_exibir) && !empty($responsabilidade_frete_exibir))
            <b>Responsabilidade do frete</b><br>
            {{ $responsabilidade_frete_exibir }}
            @endif
        </div>
        <div class="col-sm-4">
            <p>
                <b>Valor do Frete</b><br>
                {!! $frete_valor !!}
            </p>
        </div>
        @if(isset($transportador) && !empty($transportador))
        <div class="col-sm-4">
            <b>Transportadora</b><br>
            {!! $transportador !!}
        </div>
        @endif
        @if(isset($transportador_email) && !empty($transportador_email))
        <div class="col-sm-4">
            <b>E-mail da transportadora</b><br>
            {!! $transportador_email !!}
        </div>
        @endif
    </div>
    @if($status == 1)
    <div class="row mt-2">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('laudo_imagem', 'Laudo técnico') !!}
            {!! Form::file('laudo_imagem', ['class' => 'form form-control']) !!}
        </div>
    </div>
    @endif
    @if( ( (isset($laudo_tecnico) && !empty($laudo_tecnico)) || (isset($arquivo) && !empty($arquivo)) || (isset($nota_cliente_arquivo) && !empty($nota_cliente_arquivo)) || (isset($romaneio_arquivo) && !empty($romaneio_arquivo)) || (isset($notas_devolucao) && !empty($notas_devolucao)) ) || $status == 14)
    <div class="row mt-1">
        <div class="col">
            <b>Arquivos:</b>
            <div class="row">
                @if(isset($laudo_tecnico) && !empty($laudo_tecnico))
                    <div class="col">
                        <a href="{{ $laudo_tecnico }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i> Laudo técnico</a> 
                    </div>
                @endif

                @if(isset($arquivo) && !empty($arquivo))
                    <div class="col">
                        <a href="{{ $arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Imagem do produto</a>
                    </div>
                @endif

                @if(isset($nota_cliente_arquivo) && !empty($nota_cliente_arquivo))
                    <div class="col">
                        <a href="{{ $nota_cliente_arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Nota do cliente</a>
                    </div>
                @elseif($status == 14 && (!isset($nota_cliente_arquivo) || empty($nota_cliente_arquivo)))
                    <div class="col">
                        <span class="campo_obrigatorio">Nota do cliente não encontrada</span>
                    </div>
                @endif

                @if(isset($romaneio_arquivo) && !empty($romaneio_arquivo))
                    <div class="col">
                        <a href="{{ $romaneio_arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Romaneio</a>
                    </div>
                @endif

                @if(in_array($estabelecimento_codigo, ['03','04']) && (isset($nota_remessa_id) && !empty($nota_remessa_id)))
                    <div class="col">
                        <b>Downloads da nota de remessa:</b>
                            <li>
                                <a href='#' onclick="downloadDanfe($nota_remessa_id)"><i class='btn-nota-pdf'></i>PDF</a>
                            </li>
                            <li>
                                <a href='#' onclick="downloadXML($nota_remessa_id)"><i class='btn-download'></i>XML</a>
                            </li>
                    </div>
                @elseif(in_array($estabelecimento_codigo, ['03','04']) && $status == 14 && (!isset($nota_remessa_id) || empty($nota_remessa_id)))
                    <div class="col">
                        <span class="campo_obrigatorio">Nota de devolução não encontrada</span>
                    </div>
                @endif
            </div>

            @if(isset($notas_devolucao) && !empty($notas_devolucao))
                <div class='col'>Notas de devolução:
                    <div class="row">

                        @foreach ($notas_devolucao as $nota)
                            <div class="col">
                                <ul>
                                    <li>{{ $nota['numero'] }}
                                        <ul>
                                            <li><a href='#' onclick="downloadXMLDevolucao('{{ $nota['id'] }}', '{{ $nota['numero'] }}')"><i class='btn-download'></i>XML</a></li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
    @endif

    @if($status == 3)
    <div class="row">
        <div class="col-lg-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {{ Form::label('transportador', 'Transportadora', []) }}
            <div class="input-group" id="transporadora_group">
                {{ Form::text('transportador', '', ['id' => 'transportador', 'class' => 'form-control input-label']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('transportador_email', 'E-mail da transportadora') !!}
            {!! Form::text('transportador_email', '', ['id' => 'transportador_email', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('nota_cliente_numero', 'Número da nota do cliente') !!}
            {!! Form::text('nota_cliente_numero', '', ['id' => 'nota_cliente_numero', 'class' => 'form-control']) !!}
        </div>
        <div class="col-sm-6">
            Arquivo da nota<br>
            {!! Form::file('nota_cliente_arquivo', ['class' => 'form-control']) !!}
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-md-12">
            <strong>Documentos</strong>
        </div>
        <div class="col-md-12">
            @if(!empty($documentos_diversos))
                @foreach ($documentos_diversos as $key => $documentos_diversos)
                    <div class="row m-2 col-6 diversos{{ $key }}">
                        <div class="col-md-9 m-1">
                            <a href="{{  $documentos_diversos['caminho'] }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i>{{ $documentos_diversos['descricao'] }}</a> 
                        </div>
                        <div class="col-lg-6 m-1 float-right pr-2">
                            <input type="button" onclick="removerDocumentoDiversos('{{ $documentos_diversos['id'] }}','{{$key}}')" class="form-group btn btn-danger" value="Remover">
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="col-md-12">
            <div class="adicionar-elemento">
            </div>
        </div>
        <div class="col-md-12">
            {{ Form::button('Anexar Documento', ['class' => 'btn btn-success', 'id' => 'btn_adicionar_documento']) }}
        </div>
    </div>

    @if($status == 4)
    <div class="row mt-2">
        <div class="col-sm-6">
            Romaneio<br>
            {!! Form::file('romaneio_arquivo', ['class' => 'form-control']) !!}
        </div>
        @if($mostrar_botao_devolucao)
        <div class="col">
            {!! Form::button('Confirmar Recebimento da devolução', ['id' => 'btn_recebimento', 'class' => 'btn btn-primary mt-4', 'onclick' => 'receber()']) !!}
        </div>
        @endif
    </div>
    @endif


    @if($status == 6 && in_array($estabelecimento_codigo, ['03','04']))
    <div class="row">
        <div class="col-lg-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('nota_remessa', 'Número da nota de remessa') !!}
            {!! Form::text('nota_remessa', '', ['id' => 'nota_remessa', 'class' => 'form-control']) !!}
        </div>
    </div>
    @endif

    @if($status == 15)
        @if(!empty($titulos['titulos_pagos']))
            <div class="row">
                <div class="col-sm-12">
                    <b>Titulos Pagos:</b><br>
                </div>
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped table-not-edit" id="table-filters-titulos_pagos">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Parcela</th>
                                    <th>Data Emissão</th>
                                    <th>Data Vencimento</th>
                                    <th>Data Pagamento</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($titulos['titulos_pagos'] as $titulo)
                                    <tr>
                                        <td>{!! $titulo['titulo'] !!}</td>
                                        <td class="tb_number">{!! $titulo['parcela'] !!}</td>
                                        <td class="tb_date">{!! $titulo['data_emissao'] !!}</td>
                                        <td class="tb_date">{!! $titulo['data_vencimento'] !!}</td>
                                        <td class="tb_date">{!! $titulo['data_pagamento'] !!}</td>
                                        <td class="tb_number">{!! $titulo['valor'] !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td class="tb_number"></td>
                                    <td class="tb_date"></td>
                                    <td class="tb_date"></td>
                                    <td class="tb_number">Total:</td>
                                    <td class="tb_number">{!! $titulos['total_titulos_pagos'] !!}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        @if(!empty($titulos['titulos_abertos']))
            <div class="row">
                <div class="col-sm-12">
                    <b>Titulos Abertos:</b><br>
                </div>
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped table-not-edit" id="table-filters-titulos_abertos">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Parcela</th>
                                    <th>Data Emissão</th>
                                    <th>Data Vencimento</th>
                                    <th>Valor</th>
                                    <th>Abatimento</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($titulos['titulos_abertos'] as $titulo)
                                    <tr>
                                        <td>{!! $titulo['titulo'] !!}</td>
                                        <td class="tb_number">{!! $titulo['parcela'] !!}</td>
                                        <td class="tb_date">{!! $titulo['data_emissao'] !!}</td>
                                        <td class="tb_date">{!! $titulo['data_vencimento'] !!}</td>
                                        <td class="tb_number">{!! $titulo['valor'] !!}</td>
                                        <td class="tb_number">{!! $titulo['valor_baixado'] !!}</td>
                                        <td class="tb_number">{!! $titulo['saldo'] !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td class="tb_number"></td>
                                    <td class="tb_date"></td>
                                    <td class="tb_number">Total:</td>
                                    <td class="tb_number">{!! $titulos['total_titulos_abertos']['valor'] !!}</td>
                                    <td class="tb_number">{!! $titulos['total_titulos_abertos']['valor_baixado'] !!}</td>
                                    <td class="tb_number">{!! $titulos['total_titulos_abertos']['saldo'] !!}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif
        @if(!empty($titulos['titulo_credito']))
            <div class="row">
                <div class="col-sm-12">
                    <b>Titulo Crédito:</b><br>
                </div>
                <div class="content-dialog-table">
                    <div class="content-table">
                        <table class="table table-striped table-not-edit" id="table-filters-titulo_credito">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Parcela</th>
                                    <th>Data Emissão</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($titulos['titulo_credito'] as $titulo)
                                    <tr>
                                        <td>{!! $titulo['titulo'] !!}</td>
                                        <td class="tb_number">{!! $titulo['parcela'] !!}</td>
                                        <td class="tb_date">{!! $titulo['data_emissao'] !!}</td>
                                        <td class="tb_number">{!! $titulo['valor'] !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <div class="row">
        @foreach($aprovadores as $aprovador)
        <div class="col-sm-4">
            <b>{{ $aprovador['status'] }}</b><br>
            {{ $aprovador['aprovador'] }} - {{ $aprovador['data'] }}
        </div>
        @endforeach
    </div>
    
    <div class="row">
        <div class="col">
            {!! Form::label('mensagem_modal', 'Observação') !!}
            {!! Form::textarea('mensagem', '', ['class' => 'form form-control', 'id' => 'mensagem_modal', 'col' => '5', 'rows' => '4', 'maxlength' => '254']) !!}
        </div>
    </div>
    
    <div class="row @if(empty($produtos))d-none @endif valor-div">
        <div class="content-dialog-table">
            <table class="table table-striped table-filter-dialog" id="table-filters-dialog-produtos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Grupo</th>
                        <th>Descrição</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_number">Devolvida</th>
                        @if($status == 4 || $mostrar_quantidades_devolvidas)
                        <th @if($mostrar_quantidades_devolvidas) class="tb_number"@endif>Recebida</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($produtos as $produto)
                    <tr>
                        <td>{{ $produto['codigo'] }}</td>
                        <td>{{ $produto['grupo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
                        <td>{{ $produto['quantidade'] }}</td>
                        <td>{{ $produto['quantidade_devolvida'] }}</td>
                        @if($status == 4)
                        <td>{!! Form::text('quantidade_recebida', '', ['data-id' => $produto['id'], 'class' => 'text-right form-control form-float quantidade_recebida', 'size' => '10']) !!}</td>
                        @elseif($mostrar_quantidades_devolvidas)
                        <td>{!! $produto['quantidade_recebida'] !!}</td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>        
    </div>

    <div class="row mt-2">
        @if($status==4)
        <div class="col-sm">
            {!! Form::checkbox('marcar-devolucao-ok', 'ok', false, ['id' => 'marcar-devolucao-ok']) !!}
            {!! Form::label('marcar-devolucao-ok', 'Marcar todos os itens como recebidos') !!}
        </div>
        @endif
        <div class="col-sm text-right" id='enviar-div'>
            {!! Form::button('Aprovar', ['id' => 'btn_enviar', 'class' => 'btn btn-success']) !!}
        </div>
    </div>
</form>

<script>
    $(document).ready(function(){
        $(document).find('.valor_parcial_radio').on('click', function(){
            if($(this).val() == 1){
                $(document).find('.valor-div').removeClass('d-none');
            }
            else{
                $(document).find('.valor-div').addClass('d-none');
            }
        })

        $(document).find('#btn_enviar').on('click', function(){
            aprovarModal();
        })

        var options_telefone =  {
            onKeyPress: function(telefone, e, field, options_telefone) {
                var masks = ['(00) 0000-00009', '(00) 00000-0000'];
                var mask = (telefone.length>14) ? masks[1] : masks[0];
                $(document).find('#telefone_contato_modal').mask(mask, options_telefone);
            }
        };
        
        @if(strlen($telefone_contato) <= 14)
            $(document).find("#telefone_contato_modal").mask('(00) 0000-0000', options_telefone);
        @else
            $(document).find("#telefone_contato_modal").mask('(00) 00000-0000', options_telefone);
        @endif

        $(document)
            .find('.form-float')
            .maskMoney({thousands:'.', decimal:',', allowZero: true});

        $(document).find("#transportador").autocomplete(optionsAutoCompleteTransportador());
        $(document).find("#bt-view-transportadora").off("click");
        $(document).find("#bt-view-transportadora").on("click", function(event){
            event.stopPropagation();
            modalTransportador();
            return false;
        });

        $(document).find("#marcar-devolucao-ok").on('click', function(){
            if($(this).is(':checked')){
                $(document).find('.quantidade_recebida').each(function (i,e){
                    $(this).val($(this).parent().parent().find('td').eq(4).html());
                    $(this).prop('disabled', true);
                })
            }
            else{
                $(document).find('.quantidade_recebida').each(function (i,e){
                    $(this).val('');
                    $(this).prop('disabled', false);
                })
            }
        });

        var x = 1;
		var max_fields = 20;

        $('#btn_adicionar_documento').click (function(e){
			e.preventDefault(); 

            $estabelecimento = $(document).find('.estabelecimento_class option:selected').val();

            if($estabelecimento == ''){
                message('Atenção','Selecione o estabelecimento');
                return false;
            }
			if (x < max_fields)
			{
                var anterior = 0;
                
                var conteudo = 
                '<div id="adicionar-documento-div-'+x+'" class="remove'+x+'">'+
                    '<div class="row border border-dark rounded m-1">'+
                        '<div class="form-group col-md-2">'+
                            '{{ Form::label("descricao_documento_label", "Descrição") }}'+
                            '<input type="text" id="descricao_documento['+x+']" name="descricao_documento['+x+']" class="form-control campo_descricao'+x+'"  placeholder="Descreva o Documento" maxlength="30">'+
                        '</div>'+
                        '<div class="form-group col-md-2">'+
                            '{{ Form::label("label_documento", "Anexo") }}'+
                            '<input type="file" id="documento['+x+']" name="documento['+x+']" class="form-control campo_arquivo'+x+'">'+
                        '</div>'+
                        '<div class="form-check col-lg-2 mt-4">'+
                            '<input type="radio" class="form-check-input" name="tipo_documento['+x+']" id="nf_devolucao" value="nf_devolucao" checked/>'+
                            '<label class="form-check-label" for="nf_devolucao">NF de Devolução</label>'+
                        '</div>'+
                        '<div class="form-check col-lg-2 mt-4">'+
                            '<input type="radio" class="form-check-input" name="tipo_documento['+x+']" id="nf_remessa" value="nf_remessa"/>'+
                            '<label class="form-check-label" for="nf_remessa">NF de Remessa</label>'+
                        '</div>'+
                        '<div class="form-check col-lg-2 mt-4">'+
                            '<input type="radio" class="form-check-input" name="tipo_documento['+x+']" id="carta_correcao_devolucao" value="carta_correcao_devolucao"/>'+
                            '<label class="form-check-label" for="carta_correcao_devolucao">Carta de Correção Devolução</label>'+
                        '</div>'+
                        '<div class="form-check col-lg-2 mt-4">'+
                            '<input type="radio" class="form-check-input" name="tipo_documento['+x+']" id="carta_correcao_remessa" value="carta_correcao_remessa"/>'+
                            '<label class="form-check-label" for="carta_correcao_remessa">Carta de Correção Remessa</label>'+
                        '</div>'+
                        '<div class="form-group col-lg-2">'+
                            '<br>'+
                            '<input type="button" id="remove'+x+'" class="form-group btn btn-danger remove_documento" value="Remover">'+
                        '</div>'+
                    '</div>'+
                '</div>';

				if(x > 1){
                    anterior = x - 1;
                    for(var i = 1; i < x; i++){
                        var descricao = $(".campo_descricao"+i).val();
                        var documento = $(".campo_arquivo"+i).val();
                        
                        if(descricao == '' || documento == ''){
                            message("Atenção", "Adicione documentos para adicionar mais campos!");
                            return false;
                        }
                    }
                    $(document).find("#remove"+anterior).hide();
                }
				
				$('.adicionar-elemento').append( conteudo );
				x++;
			}else{
				message('Alerta','Limite Máximo de Documentos Atingido');
			}
		});
		
		$('.adicionar-elemento').on("click",".remove_documento",function(e) {
			e.preventDefault();
			var anterior = x - 2;
            $(document).find("#remove"+anterior).show();
            if(x > 1){
                x --; 
            }
			var id = $(this).attr('id');
			$('.'+ id).remove();
		});
    });

    table_filters_dialog_produtos = $(document).find('#table-filters-dialog-produtos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "paging": false,
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
            @if($status==4)
            { "targets": -1, "width": '75px'},
            @endif
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", "width": '1%'},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    });

    function aprovarModal(){

        var form = $(document).find('#form-aprovar-devolucao');
        var dados = new FormData($(document).find('#form-aprovar-devolucao')[0]);        

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        @if($status==4)

        if(!$(document).find("#marcar-devolucao-ok").is(':checked')){
            $(document)
                .find("#form-aprovar-devolucao")
                .find('.quantidade_recebida')
                .each(function(i, e){

                    var id = $(this).data('id');
                    var valor = $(this).val();

                    dados.append('produtos['+i+'][id]', id);
                    dados.append('produtos['+i+'][quantidade_recebida]', valor);
                });
        }
        else{
            dados.append('conferencia_devolucao_completa', true);
        }
        @endif
        $.ajax({
            url: '{{ route("devolucao_nota_aprovacao.aprovar") }}',
            method: 'POST',
            data: dados,
            processData: false,
            contentType: false,
            success: function(data){
                message('Atenção', 'Devolução aprovada com sucesso')
                $(document).find('#aprovar-devolucao-modal').modal('hide');
                buscarNotas();
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputsModalAprovar(form, field, errors[field]);
                }
            }
        })
    }

    function showErrorsInputsModalAprovar(form, input, message){
        var inputexplode = input.split(".");
		if(inputexplode.length > 1){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
			message = message;
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
		}else if(input.match(/produtos/i) != null){
            $input = $(document).find(".valor_parcial:not([disabled])").eq(input.split('.')[1]);
        }
        else if(input == 'valor_parcial'){
            $input = form.find(".valor_parcial_radio").parent().parent();
        }else{
            var $input = form.find("input[name='"+input+"'], select[name='"+input+"'], textarea[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function createCheckbox($id){
        var html = "<input type=\"checkbox\" name=\"produtos[]\" value=\"" + $id + "\" class='checkbox_produto''>";

        return html;
    }

    function createCampo($id){
        var html = "<input type=\"text\" name=\"valor_" + $id + "\" class=\"valor_parcial text-right form-control\" disabled>";

        return html;
    }

    function habilitarCampo($elemento){

        if($elemento.prop('checked') == true){
            $(document).find('[name=valor_'+$elemento.val()+']').prop('disabled', false);
        }
        else{
            $(document).find('[name=valor_'+$elemento.val()+']').prop('disabled', true);
        }
    }

    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message").remove();
        return {
           source: function (request, response) {
               request._token = "{{ csrf_token() }}";
               request.estabelecimento = {{ $estabelecimento_codigo }};
               $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#aprovar-devolucao-modal').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_nome").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportador").val(ui.item.label);
                returnEmailTransportador(ui.item.value);
                return false;
            }
        };
    }

    function modalTransportador(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', estabelecimento: {{ $estabelecimento_codigo }}},
            success: function(data){
				$(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
							returnDadosTransportador($(this), modal);
                        });
                    });
                });
            }
        });
	}

	function returnDadosTransportador($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find("#transportador").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
        returnEmailTransportador($dados.find("td:eq(0)").text())
		modal.modal('hide');
	}

    function returnEmailTransportador($codigo){
        $.ajax({
            url: '{{ Route('transportador.dados_transportador') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', codigo: $codigo},
            success: function(data){
                $(document).find('#aprovar-devolucao-modal').find('#transporador_email').val(data.response.email);
            }
        });
    }
	
    function downloadDanfe($id){

        var erro = false;

        $.ajax({
            url: '{{ route('notas_nasajon.testar_danfe') }}',
            data: {
                '_token': '{{ csrf_token() }}',
                'id': $id
            },
            type: 'POST',
            error: function(){
                message('Atenção', 'DANFE não disponível!')
                erro = true;
            },
            success: function(){
                $('<form action="{{ route('notas_nasajon.modal.documentos.pdf') }}" method="POST" target="_blank">\
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                    <input type="hidden" name="id" value="$id">\
                </form>').appendTo('body').submit().remove();
            }
        });
    }

    function downloadXML($id){

        var erro = false;

        $.ajax({
            url: '{{ route('notas_nasajon.testar_xml') }}',
            data: {
                '_token': '{{ csrf_token() }}',
                'id': $id
            },
            type: 'POST',
            error: function(){
                message('Atenção', 'XML não disponível!')
                erro = true;
            },
            success: function(){
                $('<form action="{{ route('notas_nasajon.modal.documentos.xml') }}" method="POST" target="_blank">\
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                    <input type="hidden" name="id" value="$id">\
                </form>').appendTo('body').submit().remove();
            }
        });
    }
    
    function downloadXMLDevolucao($id, $numero){

        $('<form action="{{ route('devolucao_nota.download_xml') }}" method="POST" target="_blank">\
            <input type="hidden" name="_token" value="{{ csrf_token() }}">\
            <input type="hidden" name="id" value="'+$id+'">\
            <input type="hidden" name="numero" value="'+$numero+'">\
        </form>').appendTo('body').submit().remove();

    }

    @if(Auth::user()->hasPermissionTo("action App\DevolucaoNotaAprovacao expedição") || in_array(Auth::id(), [863, 576]) || Auth::id() == 9334  || Auth::user()->tipo_usuario_id == 1)

    function receber(){

        var $title = "Recebimento da Devolução";
        var $text = "Confirma o recebimento da devolução?";
        var $name_option_ok = "receber";
        var $class = "dialog_option_recebimento";

        $(document).off("receber");
        $(document).on("receber", function(){

            $.ajax({
                url: "{{ route('devolucao_nota_aprovacao.recebimento') }}",
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: '{{ $id }}'
                },
                method: 'POST',
                success: function(data){
                    $(document).find('#aprovar-devolucao-modal').modal('hide');
                    buscarNotas();
                },
                error: function(callback){
                    message("Atenção", "Ocorreu uma instabilidade, tente novamente!", 'message-erro-receber');
                }
            })

        });

        $(document).off("cancelar");
        $(document).on("cancelar", function(){
            return null
        });

        message_option($title, $text, $class, $name_option_ok, '', "cancelar");

    }
    @endif

</script>
@endsection
