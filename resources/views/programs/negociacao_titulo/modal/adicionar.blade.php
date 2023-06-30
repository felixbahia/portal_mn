@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_negociacao" id="form_negociacao" onsubmit="return false;">
    @csrf
    {!! Form::hidden('id', '', ['id' => 'id']) !!}
    {!! Form::hidden('titulos', $titulos, ["id" => 'titulos']) !!}
    {!! Form::hidden('cliente_codigo', $cliente_codigo, ["id" => 'cliente_codigo']) !!}
    {!! Form::hidden('cliente_codigo', $cliente_codigo, ["id" => 'cliente_codigo']) !!}
    {!! Form::hidden('socios', '', ['id' => 'socios']) !!}
    {!! Form::hidden('avalistas', '', ['id' => 'avalistas']) !!}
    {!! Form::hidden('maior_atraso', $maior_atraso, ['id' => 'maior_atraso']) !!}
    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link active" id='renegociacao-atualizacao-tab' data-toggle="tab" href="#renegociacao_atualizacao" role="tab" aria-controls="renegociacao_atualizacao" aria-selected="true">Atualização Títulos</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="renegociacao-socios-tab" data-toggle="tab" href="#renegociacao_socios" role="tab" aria-controls="renegociacao_socios" aria-selected="false">Sócios</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="renegociacao-fiadores-tab" data-toggle="tab" href="#renegociacao_fiadores" role="tab" aria-controls="renegociacao_fiadores" aria-selected="false">Fiadores</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="renegociacao-renegociar-tab" data-toggle="tab" href="#renegociacao_renegociar" role="tab" aria-controls="renegociacao_renegociar" aria-selected="false">Renegociação</a>
        </li>
    </ul>
    <div class="tab-content pt-3" id="RenegociacaoHeaderContainer">
        <div class="tab-pane show active" id="renegociacao_atualizacao" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    <div class="content-dialog-table">
                        <div class="content-table">
                            <table class="table table-striped" id="table-unidade_negocio-membros">
                                <thead>
                                    <th>Título</th>
                                    <th class="tb_number">Valor</th>
                                </thead>
                                <tbody>
                                    @foreach ($titulos_tabelas as $titulo)
                                        <tr>
                                            <td>{{ $titulo['titulo'] }}</td>
                                            <td class="tb_number">{{ $titulo['valor'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('juro_atualizacao', 'Juros para Atualização por Mês', []) }}
                    {{ Form::text('juro_atualizacao', '', ['id' => 'juro_atualizacao', 'class' => 'form-control  text-right decimal', 'placeholder' => 'Juros Atualização por Mês', 'maxlength' => '5']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('tarifa_bancaria_atualizacao', 'Tarifa Bancária MN Baixa', []) }}
                    {{ Form::text('tarifa_bancaria_atualizacao', '3,50', ['id' => 'tarifa_bancaria_atualizacao', 'class' => 'form-control  text-right decimal', 'placeholder' => 'Tarifa Bancária MN', 'maxlength' => '5']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('data_inicial_parcela', 'Data da Renegociação', []) }}
                    {{ Form::text('data_inicial_parcela', '', ['id' => 'data_inicial_parcela', 'class' => 'form-control data text-right', 'placeholder' => 'Data Inicial', 'maxlength' => '20']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('valor_total', 'Valor Total', []) }}
                    {{ Form::text('valor_total', $saldo_total, ['id' => 'valor_total', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor Total']) }}
                </div>
            </div>
            <div class="form-row">
                <div id="parcelas_mensagem_erro" name="parcelas_mensagem_erro">
                </div>
                </br>
                <div id="titulo" name="titulo"></div>
            </div>
            <div class="col-sm-12 mt-3" id="button-bottom">
                {{ Form::button('Ir para Renegociação >>', array('class' => 'btn btn-info troca-aba float-right', 'id' => 'bt_ir_para_renegociacao')) }}
            </div>
        </div>
        <div class="tab-pane" id="renegociacao_socios" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('nome_socio', 'Nome do Sócio', []) }}
                    {{ Form::text('nome_socio', '', ['id' => 'nome_socio', 'class' => 'form-control', 'placeholder' => 'Nome do Sócio', 'maxlength' => '250']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('cpf_socio', 'CPF', []) }}
                    {{ Form::text('cpf_socio', '', ['id' => 'cpf_socio', 'class' => 'form-control', 'placeholder' => 'CPF', 'maxlength' => '14']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('email_socio', 'E-mail', []) }}
                    {{ Form::text('email_socio', '', ['id' => 'email_socio', 'class' => 'form-control', 'placeholder' => 'E-mail', 'maxlength' => '250']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('endereco_socio', 'Endereço Completo', []) }}
                    {{ Form::text('endereco_socio', '', ['id' => 'endereco_socio', 'class' => 'form-control', 'placeholder' => 'Endereço Completo', 'maxlength' => '250']) }}
                </div>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom">
                {{ Form::button('Adicionar Sócio', array('class' => 'btn btn-success float-right', 'id' => 'btn-adicionar_socio')) }}
            </div> 
            <div class="content-dialog-table  mt-1">
                <div class="content-table">
                    <table class="table table-striped" id="table-socio">
                        <thead>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>E-mail</th>
                            <th class="td_acao"></th>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div id="messagem_error_socio"></div>
        </div>
        <div class="tab-pane" id="renegociacao_fiadores" role="tabpanel" aria-labelledby="dados-tab">
                <div class="form-row">
                    <div class="form-group col-sm-12"> 
                        {{ Form::label('nome_avalista', 'Nome do Fiador', []) }}
                        {{ Form::text('nome_avalista', '', ['id' => 'nome_avalista', 'class' => 'form-control', 'placeholder' => 'Nome do Fiador', 'maxlength' => '250']) }}
                    </div>
                </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('cpf_avalista', 'CPF', []) }}
                    {{ Form::text('cpf_avalista', '', ['id' => 'cpf_avalista', 'class' => 'form-control', 'placeholder' => 'CPF', 'maxlength' => '14']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('email_avalista', 'E-mail', []) }}
                    {{ Form::text('email_avalista', '', ['id' => 'email_avalista', 'class' => 'form-control', 'placeholder' => 'E-mail', 'maxlength' => '250']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('endereco_avalista', 'Endereço Completo', []) }}
                    {{ Form::text('endereco_avalista', '', ['id' => 'endereco_avalista', 'class' => 'form-control', 'placeholder' => 'Endereço Completo', 'maxlength' => '250']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('estado_civil', 'Estado Civil', []) }}
                    {{ Form::select('estado_civil', $estado_civil, '', ['id' => 'estado_civil', 'class' => 'form-control', 'placeholder' => 'Selecione Estado Civil']) }}
                </div>
            </div>
            <div class="hide-on-venia">
                <div class="form-row">
                    <div class="form-group col-sm-12"> 
                        {{ Form::label('nome_venia_conjugal', 'Nome Vênia Conjugal', []) }}
                        {{ Form::text('nome_venia_conjugal', '', ['id' => 'nome_venia_conjugal', 'class' => 'form-control', 'placeholder' => 'Nome Vênia Conjugal', 'maxlength' => '250']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-12"> 
                        {{ Form::label('cpf_venia_conjugal', 'CPF Vênia Conjugal', []) }}
                        {{ Form::text('cpf_venia_conjugal', '', ['id' => 'cpf_venia_conjugal', 'class' => 'form-control', 'placeholder' => 'CPF Vênia Conjugal', 'maxlength' => '14']) }}
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-sm-12"> 
                        {{ Form::label('email_venia_conjugal', 'E-mail Vênia Conjugal', []) }}
                        {{ Form::text('email_venia_conjugal', '', ['id' => 'email_venia_conjugal', 'class' => 'form-control', 'placeholder' => 'E-mail Vênia Conjugal']) }}
                    </div>
                </div>
            </div>
            <div class="col-sm-12 mt-1" id="button-bottom-fiador">
                {{ Form::button('Adicionar Fiador', array('class' => 'btn btn-success float-right', 'id' => 'btn-adicionar_avalista')) }}
            </div> 
            <div class="content-dialog-table  mt-1">
                <div class="content-table">
                    <table class="table table-striped" id="table-avalista">
                        <thead>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>E-mail</th>
                            <th class="td_acao"></th>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div id="messagem_error_avalista"></div>
        </div>
        <div class="tab-pane" id="renegociacao_renegociar" role="tabpanel" aria-labelledby="dados-tab">
            <div class="form-row">
                <div class="form-group col-sm-12"> 
                    {{ Form::label('valor_total_atualizado', 'Valor Total Atualizado', []) }}
                    {{ Form::text('valor_total_atualizado', $saldo_total, ['id' => 'valor_total_atualizado', 'class' => 'form-control decimal text-right', 'placeholder' => 'Valor Total', 'readonly']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-4"> 
                    {{ Form::label('juro_mes', 'Juros por Mês', []) }}
                    {{ Form::text('juro_mes', '', ['id' => 'juro_mes', 'class' => 'form-control  text-right decimal', 'placeholder' => 'Porcetagem do Juros', 'maxlength' => '5']) }}
                </div>
                <div class="form-group col-sm-4"> 
                    {{ Form::label('tarifa_bancaria_renegociacao', 'Tar. Banc. MN Ger.', []) }}
                    {{ Form::text('tarifa_bancaria_renegociacao', '3,50', ['id' => 'tarifa_bancaria_renegociacao', 'class' => 'form-control  text-right decimal', 'placeholder' => 'Tarifa Bancária MN', 'maxlength' => '5']) }}
                </div>
                <div class="form-group col-sm-4"> 
                    {{ Form::label('encargos', 'Encargos', []) }}
                    {{ Form::text('encargos', '', ['id' => 'encargos', 'class' => 'form-control  text-right decimal', 'placeholder' => 'Tarifa Bancária MN', 'maxlength' => '10']) }}
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-sm-6"> 
                    {{ Form::label('quantidade_parcela', 'Qtd. Parcela', []) }}
                    {{ Form::text('quantidade_parcela', '', ['id' => 'quantidade_parcela', 'class' => 'form-control quantidade text-right', 'placeholder' => 'Quantidade Parcela', 'maxlength' => '2']) }}
                </div>
                <div class="form-group col-sm-6"> 
                    {{ Form::label('intervalo_dias', 'Inter. Parc.(dias)', []) }}
                    {{ Form::text('intervalo_dias', '30', ['id' => 'intervalo_dias', 'class' => 'form-control quantidade text-right', 'placeholder' => 'Intervalo Parcela(dias)', 'maxlength' => '3']) }}
                </div>
            </div>
            <div id="parcelas" name="parcelas">
            </div>
            <div class="col-sm-12 mt-5" id="button-bottom">
                {{ Form::button('Salvar Renegociação', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar-renegociacao')) }}
                {{ Form::button('Enviar Prévia', array('class' => 'btn btn-success', 'id' => 'btn-previa')) }}
            </div>
        </div>
    </div>
</form>
<script>
    titulo_obj = {};
    titulo_array = [];
    @foreach ($titulos_tabelas as $titulo)
        titulo_obj["{{ $titulo['titulo'] }}"] = {
            valor : "{{ $titulo['valor'] }}", 
            vencimento: "{{ $titulo['vencimento'] }}"
        };

        titulo_array.push("{{ $titulo['titulo'] }}");
    @endforeach

    $(document).ready( function () {
        form_modal_negociacao = $(document).find("#form_negociacao");

        form_modal_negociacao.find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            startDate: "{{$data_inicial}}",
        });
        form_modal_negociacao.find('.data').mask('00/00/0000');
        form_modal_negociacao.find('#cpf_cliente').mask('000.000.000-00');

        form_modal_negociacao.find(".decimal").maskMoney({thousands:'.', decimal:','});
        form_modal_negociacao.find(".quantidade").mask('000');
        form_modal_negociacao.find("#valor_total").off("keyup");
        form_modal_negociacao.find("#valor_total").on('keyup', function(){
            geracaoParcela(form_modal_negociacao);
        });

        form_modal_negociacao.find("#juro_mes").off("keyup");
        form_modal_negociacao.find("#juro_mes").on('keyup', function(){
            geracaoParcela(form_modal_negociacao);
        });

        form_modal_negociacao.find("#quantidade_parcela").off("keyup");
        form_modal_negociacao.find("#quantidade_parcela").on('keyup', function(){
            geracaoParcela(form_modal_negociacao);
        });

        form_modal_negociacao.find("#intervalo_dias").off("keyup");
        form_modal_negociacao.find("#intervalo_dias").on('keyup', function(){
            geracaoParcela(form_modal_negociacao);
        });

        form_modal_negociacao.find("#valor_total").off("keyup");
        form_modal_negociacao.find("#valor_total").on('keyup', function(){
            geracaoParcela(form_modal_negociacao);
            form_modal_negociacao.find("#valor_total_atualizado").val(form_modal_negociacao.find("#valor_total").val());
        });

        form_modal_negociacao.find("#data_inicial_parcela").off("change");
        form_modal_negociacao.find("#data_inicial_parcela").on('change', function(){
            atualizacaoValores(form_modal_negociacao);
            geracaoParcela(form_modal_negociacao);
        });

        form_modal_negociacao.find('#btn-salvar-renegociacao').off('click');
        form_modal_negociacao.find('#btn-salvar-renegociacao').on('click', function(){
            modalConfirmarRenegociacao(form_modal_negociacao);
        });

        form_modal_negociacao.find('#btn-previa').off('click');
        form_modal_negociacao.find('#btn-previa').on('click', function(){
            modalRenegociacaoPreviaEmail(form_modal_negociacao);
        });

        form_modal_negociacao.find("#juro_atualizacao").off("keyup");
        form_modal_negociacao.find("#juro_atualizacao").on('keyup', function(){
            atualizacaoValores(form_modal_negociacao);
            geracaoParcela(form_modal_negociacao);
        });

        form_modal_negociacao.find("#tarifa_bancaria_atualizacao").off("keyup");
        form_modal_negociacao.find("#tarifa_bancaria_atualizacao").on('keyup', function(){
            atualizacaoValores(form_modal_negociacao);
            geracaoParcela(form_modal_negociacao);
        });
        
        form_modal_negociacao.find("#tarifa_bancaria_renegociacao").off("keyup");
        form_modal_negociacao.find("#tarifa_bancaria_renegociacao").on('keyup', function(){
            geracaoParcela(form_modal_negociacao);
        });

        $(document).find(".troca-aba").off("click");
        $(document).find(".troca-aba").on("click", function(e){
            e.preventDefault();
            if($(this).attr("id") === "bt_ir_para_renegociacao"){
                $(document).find("#renegociacao-renegociar-tab").tab("show");
            }

            $(document).find(".tooltip").each(function(index, el) {
                $(document).find("[aria-describedby="+$(this).attr('id')+"]").tooltip('hide');
            });
        });

        form_modal_negociacao.find("#encargos").off("keyup");
        form_modal_negociacao.find("#encargos").on('keyup', function(){
            geracaoParcela(form_modal_negociacao);
        });

        table_socios_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Sócio Adicionado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Sócio Adicionado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                }
                
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_socio = '';
        table_socio = form_modal_negociacao.find('#table-socio').DataTable(table_socios_options);
        table_socio.draw();

        table_socio.on('draw', function () {
            $(document).find(".bt-delete").off('click');
            $(document).find(".bt-delete").on('click', function(){
                event.stopPropagation();
                deletarSocio($(this));
            });
        });

        form_modal_negociacao.find("#btn-adicionar_socio").off('click');
        form_modal_negociacao.find("#btn-adicionar_socio").on('click', function(){
            adicionarSocio(form_modal_negociacao);
        });
        
        form_modal_negociacao.find('#cpf_socio').mask('000.000.000-00');

        table_avalista_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Fiador Adicionado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Fiador Adicionado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                }
                
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_avalista = '';
        table_avalista = form_modal_negociacao.find('#table-avalista').DataTable(table_avalista_options);
        table_avalista.draw();

        table_avalista.on('draw', function () {
            $(document).find(".bt-delete").off('click');
            $(document).find(".bt-delete").on('click', function(){
                event.stopPropagation();
                deletarAvalista($(this));
            });
        });
        
        form_modal_negociacao.find("#btn-adicionar_avalista").off('click');
        form_modal_negociacao.find("#btn-adicionar_avalista").on('click', function(){
            adicionarAvalista(form_modal_negociacao);
        });
        
        form_modal_negociacao.find('#cpf_avalista').mask('000.000.000-00');
        form_modal_negociacao.find('#cpf_venia_conjugal').mask('000.000.000-00');

        form_modal_negociacao.find('.hide-on-venia').hide();

        form_modal_negociacao.find('#estado_civil').off('change');
        form_modal_negociacao.find('#estado_civil').on('change', function(){
            if(form_modal_negociacao.find('#estado_civil').val() === 'casado' || form_modal_negociacao.find('#estado_civil').val() === 'uniao_estavel'){
                form_modal_negociacao.find('.hide-on-venia').show();
            }else{
                form_modal_negociacao.find('.hide-on-venia').hide();
            }
        });

    });

    function geracaoParcela(form_modal_negociacao){
        parcelas = form_modal_negociacao.find("#quantidade_parcela").val();
        juros = form_modal_negociacao.find("#juro_mes").val();
        valor_total_inicial = form_modal_negociacao.find("#valor_total").val();
        intervalo_dias = form_modal_negociacao.find("#intervalo_dias").val();
        data_inicial_parcela = form_modal_negociacao.find("#data_inicial_parcela").val();
        data_inicial_cobranca_juros = form_modal_negociacao.find("#data_inicial_parcela").val();
        tarifa_bancaria_renegociacao = form_modal_negociacao.find("#tarifa_bancaria_renegociacao").val();
        encargos = form_modal_negociacao.find("#encargos").val();
        form_modal_negociacao.find("#btn-previa").removeAttr('disabled');
        form_modal_negociacao.find("#btn-avalistas").removeAttr('disabled');

        if(parcelas == "" || valor_total_inicial == "" || intervalo_dias == "" || data_inicial_parcela == "" || $.isEmptyObject(parcelas) || $.isEmptyObject(valor_total_inicial) || $.isEmptyObject(intervalo_dias) || $.isEmptyObject(data_inicial_parcela)){
            form_modal_negociacao.find("#parcelas").html("");
        }else{
            valor_total_inicial = valor_total_inicial.replace(/\./g,"").replace(/\,/g, ".");
            valor_total_inicial = parseFloat(valor_total_inicial);

            data_inicial_cobranca_juros_split = data_inicial_cobranca_juros.split('/');

            dia_inicial_cobranca_juros = data_inicial_cobranca_juros_split[0]; 
            mes_inicial_cobranca_juros = data_inicial_cobranca_juros_split[1];
            ano_inicial_cobranca_juros = data_inicial_cobranca_juros_split[2]; 

            data_inicial_cobranca_juros = new Date(ano_inicial_cobranca_juros, mes_inicial_cobranca_juros - 1, dia_inicial_cobranca_juros);

            data_inicial_parcela_split = data_inicial_parcela.split('/');

            dia_inicial_parcela = data_inicial_parcela_split[0]; 
            mes_inicial_parcela = data_inicial_parcela_split[1];
            ano_inicial_parcela = data_inicial_parcela_split[2]; 

            data_inicial_parcela = new Date(ano_inicial_parcela, mes_inicial_parcela - 1, dia_inicial_parcela);
            
            intervalo_dias = intervalo_dias.replace(/\./g,"").replace(/\,/g, ".");
            intervalo_dias = parseFloat(intervalo_dias);
            
            if(juros == "" || $.isEmptyObject(juros)){
                juros = 0;
            }else{
                juros = juros.replace(/\./g,"").replace(/\,/g, ".");
                juros = parseFloat(juros);
            }

            if(tarifa_bancaria_renegociacao == "" || $.isEmptyObject(tarifa_bancaria_renegociacao)){
                tarifa_bancaria_renegociacao = 0;
            }else{
                tarifa_bancaria_renegociacao = tarifa_bancaria_renegociacao.replace(/\./g,"").replace(/\,/g, ".");
                tarifa_bancaria_renegociacao = parseFloat(tarifa_bancaria_renegociacao);
            }

            if(encargos == "" || $.isEmptyObject(encargos)){
                encargos = 0;
            }else{
                encargos = encargos.replace(/\./g,"").replace(/\,/g, ".");
                encargos = parseFloat(encargos);
            }

            encargos = encargos/parcelas;

            valor_total = 0;
            html = "";
            valor = {};

            for(var i = 1; i <= parcelas; i++){
                data_inicial_parcela.setDate(data_inicial_parcela.getDate() + intervalo_dias);
                dataFormatada = adicionaZero((data_inicial_parcela.getDate() )) + "/" + adicionaZero((data_inicial_parcela.getMonth() + 1)) + "/" + data_inicial_parcela.getFullYear(); 

                diferenca = Math.abs(data_inicial_parcela.getTime() - data_inicial_cobranca_juros.getTime());
                diferenca_dias = Math.ceil(diferenca / (1000 * 60 * 60 * 24));

                valor = {
                    valor_parcela : valor_total_inicial / parcelas,
                    juros_dias : 0,
                    juros_periodo: 0,
                    juros_dias_ragazzi : (encargos * juros) / 30 / 100,
                    juros_periodo_ragazzi : 0,
                    encargo_total : 0,
                    parcela_valor : 0,
                    parcela_sem_honorario : 0
                    
                } 
                valor['juros_dias'] = (valor['valor_parcela'] *juros) / 30 / 100;
                valor['juros_periodo'] = valor['juros_dias'] * diferenca_dias;
                valor['juros_periodo_ragazzi'] = valor['juros_dias_ragazzi'] *  diferenca_dias;
                valor['encargo_total'] = encargos +  valor['juros_periodo_ragazzi'];
                valor['parcela_sem_honorario'] = valor['valor_parcela'] + valor['juros_periodo'] + tarifa_bancaria_renegociacao;
                valor['parcela_valor'] =  valor['encargo_total'] + valor['parcela_sem_honorario'];


                html = html+'<div class="form-row">'
                    +'<div class="form-group col-sm-12">'
                        +'<label for="parcela_'+i+'">'+i+'º Parcela</label>'
                    +'</div>'
                    +'</div>'
                    +'<div class="form-row">'
                        +'<div class="form-group col-sm-6">'
                            +'<input id="numero_dias_'+i+'" name="numero_dias[]" type="hidden" value="'+parseInt(diferenca_dias)+'"")">'
                            +'<input id="parcela_sem_encargos_'+i+'" name="parcela_sem_encargos[]" type="hidden" value="'+parseFloat(valor['valor_parcela'].toFixed(2))+'"")">'
                            +'<input id="juros_dias_'+i+'" name="juros_dias[]" type="hidden" value="'+parseFloat(valor['juros_dias'].toFixed(2))+'"")">'
                            +'<input id="encargos_juros_'+i+'" name="encargos_juros[]" type="hidden" value="'+parseFloat(valor['juros_periodo'].toFixed(2))+'"")">'
                            +'<input id="encargos_sem_juros_'+i+'" name="encargos_sem_juros[]" type="hidden" value="'+parseFloat(encargos.toFixed(2))+'"")">'
                            +'<input id="encargo_total_'+i+'" name="encargo_total[]" type="hidden" value="'+parseFloat(valor['encargo_total'].toFixed(2))+'"")">'
                            +'<input id="encargo_dia_ragazzi_'+i+'" name="encargo_dia_ragazzi[]" type="hidden" value="'+parseFloat(valor['juros_dias_ragazzi'].toFixed(2))+'"")">'
                            +'<input id="encargo_periodo_ragazzi_'+i+'" name="encargo_periodo_ragazzi[]" type="hidden" value="'+parseFloat(valor['juros_periodo_ragazzi'].toFixed(2))+'"")">'
                            +'<input id="parcela_sem_honorario_'+i+'" name="parcela_sem_honorario[]" type="hidden" value="'+parseFloat(valor['parcela_sem_honorario'].toFixed(2))+'"")">'
                            +'<input id="data_parcela_'+i+'" class="form-control data" data-indice='+i+' placeholder="Data da '+i+'º Parcela" maxlength="10" name="data_parcela[]" type="text" value="'+dataFormatada+'" autocomplete="off" onchange="diferencaDatas($(this))">'
                        +'</div>'
                        +'<div class="form-group col-sm-6">'
                            +'<input id="valor_parcela_'+i+'" class="form-control decimal text-right" placeholder="Valor da '+i+'º Parcela" maxlength="20" name="valor_parcela[]" data-indice='+i+' type="text" value="'+numberToReal(valor['parcela_valor'].toFixed(2))+'" autocomplete="off" onkeyup="mudancaValor($(this))">'
                        +'</div>'
                    +'</div>';

                valor_total += parseFloat(valor['parcela_valor'].toFixed(2));

            }

            if(html !== ""){
                html = html+'<div class="form-row">'
                                +'<div class="form-group col-sm-12">'
                                    +'<label for="parcela_total">Total Parcelas</label>'
                                    +'<input id="parcela_total" class="form-control decimal text-right" placeholder="Valor Parcela" maxlength="20" name="parcela_total" type="text" value="'+numberToReal(valor_total.toFixed(2))+'" autocomplete="off" readonly>'
                                +'</div>'
                            +'</div>';
            }

            form_modal_negociacao.find("#parcelas").html(html);

            form_modal_negociacao.find('.data').datepicker({ 
                format: 'dd/mm/yyyy',
                zIndex: 2000,
                language: 'pt-BR',
                autoHide: true,
                startDate: "{{$data_inicial}}",
            });
            form_modal_negociacao.find('.data').mask('00/00/0000');

            form_modal_negociacao.find(".decimal").maskMoney({thousands:'.', decimal:','});
        }
    }

    function adicionaZero(numero){
        if (numero <= 9) 
            return "0" + numero;
        else
            return numero; 
    }

    function numberToReal(valor) {
        var numero = valor.split('.');
        numero[0] = numero[0].split(/(?=(?:...)*$)/).join('.');
        return numero.join(',');
    }

    function limparMesagemErroAdd(){      
        var form_modal_negociacao = $("#form_negociacao");
        form_modal_negociacao.find('.error-message').remove();
        form_modal_negociacao.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_negociacao = $("#form_negociacao");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_negociacao, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_negociacao, input, message){
        if(input.localeCompare("valor_parcela") == 0){
            form_modal_negociacao.find("#parcelas_mensagem_erro").html("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        }else if(input == 'socios'){
            message = 'Nenhum sócio foi adicionado.';
            form_modal_negociacao.find("#messagem_error_socio").html("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        }else if(input == 'avalistas'){
            message = 'Nenhum fiador foi adicionado.';
            form_modal_negociacao.find("#messagem_error_avalista").html("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        }
        else{
            var $input = $(form_modal_negociacao).find("input[name='"+input+"'], select[name='"+input+"']");
            $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
    }

    function diferencaDatas($this){
        var indice = $this.data("indice");
        parcelas = form_modal_negociacao.find("#quantidade_parcela").val();
        parcela_total = 0;
        valor_total = form_modal_negociacao.find("#valor_total").val();
        juros = form_modal_negociacao.find("#juro_mes").val();
        tarifa_bancaria_renegociacao = form_modal_negociacao.find("#tarifa_bancaria_renegociacao").val();
        intervalo_dias = form_modal_negociacao.find("#intervalo_dias").val();
        encargos = form_modal_negociacao.find("#encargos").val();
        data_inicial_parcela = form_modal_negociacao.find("#data_inicial_parcela").val();


        if(valor_total == "" || $.isEmptyObject(valor_total)){
            valor_total = 0;
        }else{
            valor_total = valor_total.replace(/\./g,"").replace(/\,/g, ".");
            valor_total = parseFloat(valor_total);
        };

        if(parcelas == "" || $.isEmptyObject(parcelas)){
            parcelas = 0;
        }

        if(juros == "" || $.isEmptyObject(juros)){
            juros = 0;
        }else{
            juros = juros.replace(/\./g,"").replace(/\,/g, ".");
            juros = parseFloat(juros);
        }

        if(tarifa_bancaria_renegociacao == "" || $.isEmptyObject(tarifa_bancaria_renegociacao)){
            tarifa_bancaria_renegociacao = 0;
        }else{
            tarifa_bancaria_renegociacao = tarifa_bancaria_renegociacao.replace(/\./g,"").replace(/\,/g, ".");
            tarifa_bancaria_renegociacao = parseFloat(tarifa_bancaria_renegociacao);
        }

        if(intervalo_dias == "" || $.isEmptyObject(intervalo_dias)){
            intervalo_dias = 0;
        }else{
            intervalo_dias = intervalo_dias.replace(/\./g,"").replace(/\,/g, ".");
            intervalo_dias = parseFloat(intervalo_dias);
        };

        if(encargos == "" || $.isEmptyObject(encargos)){
            encargos = 0;
        }else{
            encargos = encargos.replace(/\./g,"").replace(/\,/g, ".");
            encargos = parseFloat(encargos);
            encargos = encargos/parcelas;
        };

        for(var i = 1; i < indice; i++){
            parcela_valor = form_modal_negociacao.find("#valor_parcela_"+i).val();

            if(parcela_valor == "" || $.isEmptyObject(parcela_valor)){
                parcela_valor = 0;
            }else{
                parcela_valor = parcela_valor.replace(/\./g,"").replace(/\,/g, ".");
                parcela_valor = parseFloat(parcela_valor);
            };

            parcela_total += parseFloat(parcela_valor.toFixed(2));
        }

        data_inicial_parcela_split = data_inicial_parcela.split('/');

        dia_inicial_parcela = data_inicial_parcela_split[0]; 
        mes_inicial_parcela = data_inicial_parcela_split[1];
        ano_inicial_parcela = data_inicial_parcela_split[2]; 

        data_inicial_parcela = new Date(ano_inicial_parcela, mes_inicial_parcela - 1, dia_inicial_parcela);
        valorData = {};

        for(var i = indice; i <= parcelas; i++){
            if(indice == i){
                data_final = form_modal_negociacao.find("#data_parcela_"+i).val();

                data_final_split = data_final.split('/');

                dia_final = data_final_split[0]; 
                mes_final = data_final_split[1];
                ano_final = data_final_split[2]; 

                data_final = new Date(ano_final, mes_final - 1, dia_final);
                
            }else{
                indice_anterior = i - 1; 
                data_inicial = form_modal_negociacao.find("#data_parcela_"+indice_anterior).val();

                data_inicial_split = data_inicial.split('/');

                dia_inicial = data_inicial_split[0]; 
                mes_inicial = data_inicial_split[1];
                ano_inicial = data_inicial_split[2]; 

                data_inicial = new Date(ano_inicial, mes_inicial - 1, dia_inicial);

                data_inicial = data_inicial;
                data_inicial.setDate(data_inicial.getDate() + intervalo_dias);
                dataFormatada = adicionaZero((data_inicial.getDate() )) + "/" + adicionaZero((data_inicial.getMonth() + 1)) + "/" + data_inicial.getFullYear(); 

                form_modal_negociacao.find("#data_parcela_"+i).val(dataFormatada);

                data_final = form_modal_negociacao.find("#data_parcela_"+i).val();

                data_final_split = data_final.split('/');

                dia_final = data_final_split[0]; 
                mes_final = data_final_split[1];
                ano_final = data_final_split[2]; 

                data_final = new Date(ano_final, mes_final - 1, dia_final);

            }
            
            diferenca = Math.abs(data_final.getTime() - data_inicial_parcela.getTime());
            diferenca_dias = Math.ceil(diferenca / (1000 * 60 * 60 * 24));

            valorData = {
                    valor_parcela : valor_total_inicial / parcelas,
                    juros_dias : 0,
                    juros_periodo: 0,
                    juros_dias_ragazzi : (encargos * juros) / 30 / 100,
                    juros_periodo_ragazzi : 0,
                    encargo_total : 0,
                    parcela_valor : 0,
                    parcela_sem_honorario : 0
                        
                } 

            valorData['juros_dias'] = (valorData['valor_parcela'] *juros) / 30 / 100;
            valorData['juros_periodo'] = valorData['juros_dias'] * diferenca_dias;
            valorData['juros_periodo_ragazzi'] = valorData['juros_dias_ragazzi'] *  diferenca_dias;
            valorData['encargo_total'] = encargos +  valorData['juros_periodo_ragazzi'];
            valorData['parcela_sem_honorario'] = valorData['valor_parcela'] + valorData['juros_periodo'] + tarifa_bancaria_renegociacao;
            valorData['parcela_valor'] =  valorData['encargo_total'] + valorData['parcela_sem_honorario'];

            form_modal_negociacao.find("#numero_dias_"+i).val(parseInt(diferenca_dias));
            form_modal_negociacao.find("#valor_parcela_"+i).val(numberToReal(valorData['parcela_valor'].toFixed(2)));
            form_modal_negociacao.find("#parcela_sem_encargos_"+i).val(parseFloat(valorData['valor_parcela'].toFixed(2)));
            form_modal_negociacao.find("#juros_dias_"+i).val(parseFloat(valorData['juros_dias'].toFixed(2)));
            form_modal_negociacao.find("#encargos_juros_"+i).val(parseFloat(valorData['juros_periodo'].toFixed(2)));
            form_modal_negociacao.find("#encargos_sem_juros_"+i).val(parseFloat(encargos.toFixed(2)));
            form_modal_negociacao.find("#encargo_dia_ragazzi_"+i).val(parseFloat(valorData['juros_dias_ragazzi'].toFixed(2)));
            form_modal_negociacao.find("#parcela_sem_honorario_"+i).val(parseFloat(valorData['parcela_sem_honorario'].toFixed(2)));
            form_modal_negociacao.find("#encargo_periodo_ragazzi_"+i).val(parseFloat(valorData['juros_periodo_ragazzi'].toFixed(2)));
            form_modal_negociacao.find("#encargo_total_"+i).val(parseFloat(valorData['encargo_total'].toFixed(2)));

            parcela_total += parseFloat(valorData['parcela_valor'].toFixed(2));
        }

        form_modal_negociacao.find("#parcela_total").val(numberToReal(parcela_total.toFixed(2)));
    };

    function modalRenegociacaoPreviaEmail(form_modal_negociacao){
        title = "Renegociação Títulos Enviar Prévia";
        data_form_modal_negociacao = form_modal_negociacao.serialize();
        $.ajax({
            url: '{{ route('renegociacao_titulo.modal.email_previa') }}',
            type: 'POST',
            data: data_form_modal_negociacao,
            success: function (body){
                $(form_modal_negociacao).parents('.modal').modal('hide');
                createModal('email_previa_modal',  title, body, '');
            },
            error: function(callback){
                var dados = callback.responseJSON;
                message("Atenção", callback.responseJSON.message);
                limparMesagemErroAdd();
                mensagemErroAdd(callback.responseJSON);
            }
        }); 
    }

    function mudancaValor($this){
        var indice = $this.data("indice");
        var parcelas = form_modal_negociacao.find("#quantidade_parcela").val();
        var juros = form_modal_negociacao.find("#juro_mes").val();
        var valor_total = form_modal_negociacao.find("#valor_total").val();
        var valor_total_verificacao = form_modal_negociacao.find("#valor_total").val();
        var parcela_total = form_modal_negociacao.find("#parcela_total").val();
        var tarifa_bancaria_renegociacao = form_modal_negociacao.find("#tarifa_bancaria_renegociacao").val();
        var total_superior = 0;
        var total_inferior = 0;

        if(juros == "" || $.isEmptyObject(juros)){
            juros = 0;
        }else{
            juros = juros.replace(/\./g,"").replace(/\,/g, ".");
            juros = parseFloat(juros);
        }

        if(valor_total == "" || $.isEmptyObject(valor_total)){
            valor_total = 0;
        }else{
            valor_total = valor_total.replace(/\./g,"").replace(/\,/g, ".");
            valor_total = parseFloat(valor_total);
        }

        if(valor_total_verificacao == "" || $.isEmptyObject(valor_total_verificacao)){
            valor_total_verificacao = 0;
        }else{
            valor_total_verificacao = valor_total_verificacao.replace(/\./g,"").replace(/\,/g, ".");
            valor_total_verificacao = parseFloat(valor_total_verificacao);
        }

        if(parcela_total == "" || $.isEmptyObject(parcela_total)){
            parcela_total = 0;
        }else{
            parcela_total = parcela_total.replace(/\./g,"").replace(/\,/g, ".");
            parcela_total = parseFloat(parcela_total);
        }

        if(tarifa_bancaria_renegociacao == "" || $.isEmptyObject(tarifa_bancaria_renegociacao)){
            tarifa_bancaria_renegociacao = 0;
        }else{
            tarifa_bancaria_renegociacao = tarifa_bancaria_renegociacao.replace(/\./g,"").replace(/\,/g, ".");
            tarifa_bancaria_renegociacao = parseFloat(tarifa_bancaria_renegociacao);
        }

        for(var i = 1; i <= indice; i++){
            valor_parcela = form_modal_negociacao.find("#valor_parcela_"+i).val();

            if(valor_parcela == "" || $.isEmptyObject(valor_parcela)){
                valor_parcela = 0;
            }else{
                valor_parcela = valor_parcela.replace(/\./g,"").replace(/\,/g, ".");
                valor_parcela = parseFloat(valor_parcela);
            }

            total_superior += valor_parcela;
        }

        valor_total -= total_superior;

        if(valor_total < 0){
            valor_total = 0;
        }

        for(var i = parcelas; i > indice; i--){
            indice_anterior = i - 1; 
            data_inicial = form_modal_negociacao.find("#data_parcela_"+indice_anterior).val();

            data_inicial_split = data_inicial.split('/');

            dia_inicial = data_inicial_split[0]; 
            mes_inicial = data_inicial_split[1];
            ano_inicial = data_inicial_split[2]; 

            data_inicial = new Date(ano_inicial, mes_inicial - 1, dia_inicial);

            data_final = form_modal_negociacao.find("#data_parcela_"+i).val();

            data_final_split = data_final.split('/');

            dia_final = data_final_split[0]; 
            mes_final = data_final_split[1];
            ano_final = data_final_split[2]; 

            data_final = new Date(ano_final, mes_final - 1, dia_final);

            diferenca = Math.abs(data_final.getTime() - data_inicial.getTime());
            diferenca_dias = Math.ceil(diferenca / (1000 * 60 * 60 * 24));

            parcela_valor = (valor_total / (parcelas - indice)) + (valor_total / (parcelas - indice)) * (juros / 30 / 100) * diferenca_dias + tarifa_bancaria_renegociacao;

            form_modal_negociacao.find("#valor_parcela_"+i).val(numberToReal(parcela_valor.toFixed(2)));

            total_inferior += parseFloat(parcela_valor.toFixed(2));
        }

        parcela_total = 0;
        
        for(var i = 0; i <= parcelas; i++){           
            parcela_valor =  form_modal_negociacao.find("#valor_parcela_"+i).val();

            if(parcela_valor == "" || $.isEmptyObject(parcela_valor)){
                parcela_valor = 0;
            }else{
                parcela_valor = parcela_valor.replace(/\./g,"").replace(/\,/g, ".");
                parcela_valor = parseFloat(parcela_valor);
            }

            parcela_total += parseFloat(parcela_valor.toFixed(2));
        }

        form_modal_negociacao.find("#parcela_total").val(numberToReal(parcela_total.toFixed(2)));
        
    }

    function atualizacaoValores(form_modal_negociacao){
        data_inicial_parcela = form_modal_negociacao.find("#data_inicial_parcela").val();
        juro_atualizacao = form_modal_negociacao.find("#juro_atualizacao").val();
        tarifa_bancaria_atualizacao = form_modal_negociacao.find("#tarifa_bancaria_atualizacao").val();

        if(tarifa_bancaria_atualizacao == "" && $.isEmptyObject(tarifa_bancaria_atualizacao)){
            tarifa_bancaria_atualizacao = 0;
        }else{
            tarifa_bancaria_atualizacao = tarifa_bancaria_atualizacao.replace(/\./g,"").replace(/\,/g, ".")
            tarifa_bancaria_atualizacao = parseFloat(tarifa_bancaria_atualizacao);
        }
        
        if(data_inicial_parcela != "" && !$.isEmptyObject(data_inicial_parcela) && juro_atualizacao != "" && !$.isEmptyObject(juro_atualizacao)){
            data_inicial_parcela_split = data_inicial_parcela.split('/');

            dia_inicial_parcela = data_inicial_parcela_split[0]; 
            mes_inicial_parcela = data_inicial_parcela_split[1];
            ano_inicial_parcela = data_inicial_parcela_split[2]; 
    
            data_inicial_parcela = new Date(ano_inicial_parcela, mes_inicial_parcela - 1, dia_inicial_parcela);
            
            juro_atualizacao = juro_atualizacao.replace(/\./g,"").replace(/\,/g, ".")
            juro_atualizacao = parseFloat(juro_atualizacao);
            
            valor_total = 0;
            html = '';

            titulo_array.forEach(function imprimir(item){
                titulo_valor = titulo_obj[item]['valor'].replace(/\./g,"").replace(/\,/g, ".")
                titulo_valor = parseFloat(titulo_valor);

                data_vencimento_split =  titulo_obj[item]['vencimento'].split('/');

                dia_vencimento = data_vencimento_split[0]; 
                mes_vencimento = data_vencimento_split[1];
                ano_vencimento = data_vencimento_split[2];

                if(ano_inicial_parcela < ano_vencimento){
                    diferenca_dias = 0;
                }else if(ano_inicial_parcela == ano_vencimento){
                    if(mes_inicial_parcela < mes_vencimento){
                        diferenca_dias = 0;
                    }else if(mes_inicial_parcela <= mes_vencimento){
                        if(dia_inicial_parcela <= dia_vencimento){
                            diferenca_dias = 0;
                        }else{
                            data_vencimento = new Date(ano_vencimento, mes_vencimento - 1, dia_vencimento);

                            diferenca = Math.abs(data_inicial_parcela.getTime() - data_vencimento.getTime());
                            diferenca_dias = Math.ceil(diferenca / (1000 * 60 * 60 * 24)); 
                        }
                    }else{
                        data_vencimento = new Date(ano_vencimento, mes_vencimento - 1, dia_vencimento);

                        diferenca = Math.abs(data_inicial_parcela.getTime() - data_vencimento.getTime());
                        diferenca_dias = Math.ceil(diferenca / (1000 * 60 * 60 * 24));
                    }
                }else{
                    data_vencimento = new Date(ano_vencimento, mes_vencimento - 1, dia_vencimento);

                    diferenca = Math.abs(data_inicial_parcela.getTime() - data_vencimento.getTime());
                    diferenca_dias = Math.ceil(diferenca / (1000 * 60 * 60 * 24));
                }

                juro_atualizacao_dia = juro_atualizacao / 30 / 100;
                encargo_dia = titulo_valor * juro_atualizacao_dia;
                encargo_periodo = encargo_dia * diferenca_dias;
                encargo_total = encargo_periodo + tarifa_bancaria_atualizacao;
                valor_total_titulo = titulo_valor + encargo_total;
                titulo_valor_juros = titulo_valor + ((titulo_valor * juro_atualizacao_dia)) * diferenca_dias;
                valor_total = valor_total + titulo_valor_juros + tarifa_bancaria_atualizacao;

                form_modal_negociacao.find("#titulo").html('');

                html += '<input id="numero_dias'+item+'" name="numero_dias['+item+']" type="hidden" value="'+parseInt(diferenca_dias)+'"")">'
                        +'<input id="encargo_dia'+item+'" name="encargo_dia['+item+']" type="hidden" value="'+parseFloat(encargo_dia.toFixed(2))+'"")">'
                        +'<input id="encargo_periodos'+item+'" name="encargo_periodo['+item+']" type="hidden" value="'+parseFloat(encargo_periodo.toFixed(2))+'"")">'
                        +'<input id="encargo_total'+item+'" name="encargo_total['+item+']" type="hidden" value="'+parseFloat(encargo_total.toFixed(2))+'"")">'
                        +'<input id="valor_total'+item+'" name="valor_total['+item+']" type="hidden" value="'+parseFloat(valor_total_titulo.toFixed(2))+'"")">';
                
                form_modal_negociacao.find("#titulo").html(html);
            });
            
            form_modal_negociacao.find("#valor_total").val(numberToReal(valor_total.toFixed(2)));
            form_modal_negociacao.find("#valor_total_atualizado").val(form_modal_negociacao.find("#valor_total").val());
        }
    }

    function adicionarSocio(form_modal_negociacao){
        data_form_modal_negociacao = form_modal_negociacao.serialize();
        $.ajax({
            url: '{{ route('renegociacao_titulo.adicionar_socio') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form_modal_negociacao,
            async: false,
            success: function (data){
                linhas = [];
                table_socio.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        ajusteTamanhoTable(data.response.tabela[posicao].nome),
                        ajusteTamanhoTable(data.response.tabela[posicao].cpf),
                        ajusteTamanhoTable(data.response.tabela[posicao].email),
                        createBtDelete(data.response.socios, data.response.tabela[posicao].cpf)
                    ];
                    linhas.push(linha)
                }
                table_socio.rows.add(linhas).draw();   
                form_modal_negociacao.find("#socios").val(data.response.socios);

                limparCamposSocio();
            },
            error: function (callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);
            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";
    
        return $html;
    }

    function limparCamposSocio(){
        limparMesagemErroAdd();
        form_modal_negociacao.find('#nome_socio').val('');
        form_modal_negociacao.find('#cpf_socio').val('');
        form_modal_negociacao.find('#email_socio').val('');
        form_modal_negociacao.find('#endereco_socio').val('');
    }

    function adicionarAvalista(form_modal_negociacao){
        data_form_modal_negociacao = form_modal_negociacao.serialize();
        $.ajax({
            url: '{{ route('renegociacao_titulo.adicionar_avalista') }}',
            type: 'POST',
            data: data_form_modal_negociacao,
            success: function (data){
                linhas = [];
                table_avalista.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        ajusteTamanhoTable(data.response.tabela[posicao].nome),
                        ajusteTamanhoTable(data.response.tabela[posicao].cpf),
                        ajusteTamanhoTable(data.response.tabela[posicao].email),
                        createBtDelete(data.response.avalistas, data.response.tabela[posicao].cpf)
                    ];
                    linhas.push(linha)
                }
                table_avalista.rows.add(linhas).draw();   
                form_modal_negociacao.find("#avalistas").val(data.response.avalistas);

                limparCamposAvalista();
            },
            error: function (callback){
                var dados = callback.responseJSON;
                limparMesagemErroAdd();
                mensagemErroAdd(dados);    
            }
        });
    }

    function limparCamposAvalista(){
        limparMesagemErroAdd();
        form_modal_negociacao.find('#nome_avalista').val('');
        form_modal_negociacao.find('#cpf_avalista').val('');
        form_modal_negociacao.find('#email_avalista').val('');
        form_modal_negociacao.find('#endereco_avalista').val('');
        form_modal_negociacao.find('#estado_civil').val('');
        form_modal_negociacao.find('#nome_venia_conjugal').val('');
        form_modal_negociacao.find('#cpf_venia_conjugal').val('');
        form_modal_negociacao.find('.hide-on-venia').hide();
    }

    function modalConfirmarRenegociacao(form_modal_negociacao){
        data_form_modal_negociacao = form_modal_negociacao.serialize();
        $.ajax({
            url: '{{ route("renegociacao_titulo.modal.confirmar_renegociacao") }}',
            type: 'POST',
            data: data_form_modal_negociacao,
            success: function (body){
                createModal('modal_confirmar_renegociacao',  '', body, '');
            },
            error: function(callback){
                var json_error = callback.responseJSON;
                if(Object.keys(json_error).length > 0){
                    for(var field in json_error.error){
                        message("Atenção", json_error.error[field]);
                    }
                }
            }
        }); 
    }

    function createBtDelete($hash, $cpf){
        $html = "<a title='Deletar' class='bt-delete' data-hash='"+$hash+"' data-cpf='"+$cpf+"'></a>";
    
        return $html;
    }

    function deletarSocio($this){
        $.ajax({
            url: "{{ route('renegociacao_titulo.deletar_socio') }}",
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}", 
                socios: $this.data('hash'),
                cpf : $this.data('cpf')
            },
            method: 'POST',
            success: function(data){
                if(data.status === "success"){
                    linhas = [];
                    table_socio.clear().draw();
                    for (var posicao in data.response.tabela){
                        linha = [
                            ajusteTamanhoTable(data.response.tabela[posicao].nome),
                            ajusteTamanhoTable(data.response.tabela[posicao].cpf),
                            ajusteTamanhoTable(data.response.tabela[posicao].email),
                            createBtDelete(data.response.socios, data.response.tabela[posicao].cpf)
                        ];
                        linhas.push(linha)
                    }
                    table_socio.rows.add(linhas).draw();   
                    form_modal_negociacao.find("#socios").val(data.response.socios);

                    limparCamposSocio();
                }
            },
            error: function (callback){
                message("Atenção", 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!');
            }
        });
    }

    function deletarAvalista($this){
        $.ajax({
            url: "{{ route('renegociacao_titulo.deletar_avalista') }}",
            dataType: 'json',
            data: {
                _token: "{{ csrf_token() }}", 
                avalistas: $this.data('hash'),
                cpf : $this.data('cpf')
            },
            method: 'POST',
            success: function(data){
                if(data.status === "success"){
                    linhas = [];
                    table_avalista.clear().draw();
                    for (var posicao in data.response.tabela){
                        linha = [
                            ajusteTamanhoTable(data.response.tabela[posicao].nome),
                            ajusteTamanhoTable(data.response.tabela[posicao].cpf),
                            ajusteTamanhoTable(data.response.tabela[posicao].email),
                            createBtDelete(data.response.avalistas, data.response.tabela[posicao].cpf)
                        ];
                        linhas.push(linha)
                    }
                    table_avalista.rows.add(linhas).draw();   
                    form_modal_negociacao.find("#avalistas").val(data.response.avalistas);

                    limparCamposAvalista();
                }
            },
            error: function (callback){
                message("Atenção", 'Ocorreu uma instabilidade no servidor!<br />Tente novamente!');
            }
        });
    }

</script>
@endsection