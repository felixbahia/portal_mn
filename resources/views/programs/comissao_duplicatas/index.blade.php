@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    {{ Form::hidden('data_inicio', '', ['id' => 'data_inicio']) }}
    {{ Form::hidden('data_fim', '', ['id' => 'data_fim']) }}

    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Todos'])}}
        </div>
        @if (!in_array(Auth::user()->tipo_usuario_id, [12,16]))
            <div class="form-group col-lg-2">
                {{ Form::select('representantes', $representantes, '', ['class' => 'form-control', 'placeholder' => 'Todos'])}}
            </div>
        @endif
        @if (!in_array(Auth::user()->tipo_usuario_id, [12,16,19,13]))
            <div class="col-lg-2">
                <select name="tipo" id="tipo">
                    @foreach($tipos as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @if (!in_array(Auth::user()->tipo_usuario_id, [12,16,19,13]))
            <div class="col-lg-2">
                {{ Form::select("confirmacao", ['sim' => 'Com Confirmação', 'nao' => 'Sem Confirmação'], '', ["id" => "confirmacao", "class"=>"form-control","placeholder" => "Todos"]) }}
            </div>
        @endif
        <div class="col-lg-2">
            <input type="text" name="data" id="data" value="{{  date("m/Y") }}" placeholder="Período">
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-not-edit table-not-view" id="table-filters-comissao">
        <thead>
            <tr>
                <th rowspan="2">Representante</th>
                <th colspan="2">A receber no período</th>
                <th colspan="2">A vencer no período</th>
                <th colspan="2">Vencido</th>
                <!-- <th rowspan="2" class="number-format">Desconto virada</th> -->
                <th rowspan="2">Conf.</th>
                @if(in_array(Auth::user()->tipo_usuario_id, [13,12,16,19,14]))
                    <th rowspan="2" class="tb_acao">Aprovar</th>
                @endif
            </tr>
            <tr>
                <th class="tb_number mes-futuro-col">Valor</th>
                <th class="tb_number mes-futuro-col">Comissão</th>
                <th class="tb_number mes-col">Valor</th>
                <th class="tb_number mes-col">Comissão</th>
                <th class="tb_number atraso-col">Acumulado</th>
                <th class="tb_number atraso-col">Comissão</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <td>Total:</td>
                <td class="tb_number" id='total_basecomissao'></td>
                <td class="tb_number" id='total_comissao'></td>
                <td class="tb_number" id='total_base_vencer'></td>
                <td class="tb_number" id='total_comissao_vencer'></td>
                <td class="tb_number" id='total_vencido'></td>
                <td class="tb_number" id='total_comissao_vencido'></td>
                <!-- <td class="tb_number" id='total_descontos'></td> -->
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $(document).find("#data").mask("00/0000", {placeholder: "__/____"});
        $(document).find('#data').datepicker({
            language: 'pt-BR',
            format: 'mm/yyyy',
            zIndex: 100,
            autoHide: true,
            startDate: '10/2020'
        });
        
        table_filters_comissao = $('#table-filters-comissao').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "decimal":        ",",
                "thousands":      ".",
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
                    "targets": "number-format",
                    "class": "tb_number",
                    "type": "html-numeric-comma"
                },
                {
                    "class": "atraso-col tb_number",
                    "targets": "atraso-col",
                    "type": "html-numeric-comma"

                },
                {
                    "class": "mes-col tb_number",
                    "targets": "mes-col",
                    "type": "html-numeric-comma"

                },
                {
                    "class": "mes-futuro-col tb_number",
                    "targets": "mes-futuro-col",
                    "type": "html-numeric-comma"

                },
                {
                     "class": "tb_date", 
                     "targets": "tb_acao"
                }
            ],
            "order": [[ 0, 'asc' ]]
        });

        $("#form_filter").find("#btn-filterform").on("click", function(){
            buscaDados($("#form_filter"));
        });

        table_filters_comissao.on('draw', function () {
            $(document).find(".bt-aprove").off("click");
            $(document).find(".bt-aprove").on("click", function(event){
                event.stopPropagation();
                aprovarComissao($(this));
            });
        });
    });
    function buscaDados($form){
        table_filters_comissao.clear().draw();
        $(document).find('#table-filters-comissao').find('#total_comissao').html('');
        $(document).find('#table-filters-comissao').find('#total_basecomissao').html('');
        $(document).find('#table-filters-comissao').find('#total_base_vencer').html('');
        $(document).find('#table-filters-comissao').find('#total_comissao_vencer').html('');
        $(document).find('#table-filters-comissao').find('#total_vencido').html('');
        $(document).find('#table-filters-comissao').find('#total_comissao_vencido').html('');
        $(document).find('#table-filters-comissao').find('#total_descontos').html('');
        
        $('[data-toggle="popover"]').popover('hide');
        $('[data-toggle="tooltip"]').tooltip('hide');
        $('label.error-message').remove();
        $.ajax({
            url: '{{ route('comissao_duplicatas.filtro') }}',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(callback){
                if(callback.status === 'success'){

                    $(document).find('#data_inicio').val(callback.response.data_inicio);
                    $(document).find('#data_fim').val(callback.response.data_fim);

                    var dados = callback.response.titulos;
                    var total = callback.response.total;
                    var lines = [];
                    if(dados.length){
                        for(var field in dados){
                            var temp_field = [
                                dados[field].representante,
                                createLinkComissao(dados[field], dados[field].base_comissao, $form),
                                createLinkComissao(dados[field], dados[field].valor_comissao, $form),
                                createLinkAVencer(dados[field], dados[field].a_vencer_valor, $form),
                                createLinkAVencer(dados[field], dados[field].a_vencer_comissao, $form),
                                createLinkVencido(dados[field], dados[field].vencido_valor, $form),
                                createLinkVencido(dados[field], dados[field].vencido_comissao, $form),
                                <!-- createLinkDesconto(dados[field]), -->
                                confirmacao(dados[field]),
                                createBtaprovarComissao(dados[field])
                            ];
                            lines.push(temp_field);
                        }
                        var rows = table_filters_comissao.rows.add(lines).order([[ 0, 'asc' ]]).draw().nodes();
                        $(document).find('#table-filters-comissao').find('#total_comissao').html(total.comissao);
                        $(document).find('#table-filters-comissao').find('#total_basecomissao').html(total.base_comissao);
                        $(document).find('#table-filters-comissao').find('#total_base_vencer').html(total.a_vencer_valor);
                        $(document).find('#table-filters-comissao').find('#total_comissao_vencer').html(total.a_vencer_comissao);
                        $(document).find('#table-filters-comissao').find('#total_vencido').html(total.vencido_valor);
                        $(document).find('#table-filters-comissao').find('#total_comissao_vencido').html(total.vencido_comissao);
                        <!-- $(document).find('#table-filters-comissao').find('#total_descontos').html(total.desconto_comissao); -->
                    }
                }
            },
            error: function(callback){
                if((callback.responseJSON)){
                    if(callback.responseJSON.error){
                        var data = callback.responseJSON.error;
                        $.each(data, function(index, el) {
                            $form.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
                            $form.find('input[name="'+index+'"]').eq(0).addClass('error');
                        });
                        $form.find('input.error').eq(0).focus();
                    }
                }
            }
        });

        table_filters_comissao.draw();
        
    }
    function confirmacao($this){
        var html = '';
        if ($this.confirmacao == 'Sim'){
            var html = "<center><i class='fa fa-check check-icon' aria-hidden='true'></i></center>";
        }
        return html;
    }
    function createLinkComissao($this, $valor, $form){
        var html = "";
        if($this.pre_pago_aberto.length > 0 ){
            html = "<div data-toggle='tooltip' onclick=\"prepagosAbertos('" + $this.representante_not_parse + "', '" + $this.representante + "')\" data-html='true' title='' data-original-title='Comissão bloqueada por título pré-pago em aberto: " + $this.pre_pago_aberto + "' class='bt-bloqueado'></div>";
        }else if($this.numero_nota !== ""){
            html = "<a href='#' onclick=\"showComissaoDetalhes('" 
            + $this.representante_not_parse + "', '" 
            + $form.find('#data_inicio').val() + "', '" 
            + $form.find('#data_fim').val() + "', '" 
            + $form.find('#estabelecimento').val() + "', '"
            + $this.cod_representante + "', '" 
            + $this.representante
            + "')\">" + $valor + "</a>";
        }
        return html;
    }
    function showComissaoDetalhes(representante, data_inicio, data_fim, estabelecimento, cod_representante, nome){
        $.ajax({
            url: '{{ route('comissao_duplicatas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                representante: representante,
                data_inicio: data_inicio,
                data_fim: data_fim,
                estabelecimento: estabelecimento
            },
            success: function(body){
                createModal("comissao_representante", "Detalhes comissão Representante " + nome + " (" + cod_representante + ") - Período(" + data_inicio + " até " + data_fim + ")", body, 'modal-lg');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }

        });
    }

    function createLinkAVencer($this, $valor, $form){
        var html = "";
        if($this.numero_nota !== ""){
            html = "<a href='#' onclick=\"showAbertosDetalhes('" 
            + $this.representante_not_parse + "', '" 
            + $form.find('#data_inicio').val() + "', '" 
            + $form.find('#data_fim').val() + "', '" 
            + $form.find('#estabelecimento').val() + "', '"
            + $this.cod_representante + "', '" 
            + $this.representante + "', 'vencer')\">" + $valor + "</a>";
        }
        return html;
    }

    function createLinkVencido($this, $valor, $form){
        var html = "";
        if($this.numero_nota !== ""){
            html = "<a href='#' onclick=\"showAbertosDetalhes('" 
            + $this.representante_not_parse + "', '" 
            + $form.find('#data_inicio').val() + "', '" 
            + $form.find('#data_fim').val() + "', '" 
            + $form.find('#estabelecimento').val() + "', '"
            + $this.cod_representante + "', '" 
            + $this.representante+ "', 'vencido')\">" + $valor + "</a>";
        }
        return html;
    }

    function showAbertosDetalhes(representante, data_inicio, data_fim, estabelecimento, cod_representante, nome, vencer_vencido){
        $.ajax({
            url: '{{ route('comissao_duplicatas.abertos')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                representante: representante,
                data_inicio: data_inicio,
                data_fim: data_fim,
                estabelecimento: estabelecimento,
                vencer_vencido: vencer_vencido
            },
            success: function(body){
                
                if(vencer_vencido == 'vencer'){
                    modal_id = 'comissao_vencer_representante';
                    modal_titulo = "Detalhes Comissão A Vencer Representante " + nome + " (" + cod_representante + ") - Período(" + data_inicio + " até " + data_fim + ")";
                }
                else if(vencer_vencido == 'vencido'){
                    modal_id = 'comissao_vencidos_representante';
                    modal_titulo = "Detalhes Comissão Títulos Vencidos Representante " + nome + " (" + cod_representante + ") - Período(" + data_inicio + " até " + data_fim + ")";                   
                }

                createModal(modal_id, modal_titulo, body, 'modal-lg');
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }

        });
    }

    function createLinkDesconto($this){
        var html = "";
        if($this.numero_nota !== ""){
            html = "<a href='#' onclick=\"descontos("+ $this.representante_not_parse + ")\">"+$this.desconto_comissao+"</a>";
        }
        return html;
    }

    function descontos($codigo_representante){
        $.ajax({
            url: '{{ route('desconto_representante.exibicao') }}',
            data: {
                _token: '{{ csrf_token() }}',
                representante: $codigo_representante
            },
            method: 'POST',
            success: function(body){
                createModal("desconto_representantes", "Desconto na comissão", body, 'modal-lg');
            }
        });
    }

    function prepagosAbertos($user_id, $representante){
        $.ajax({
            url: '{{ route('titulos_prepago.modal.abertos_representante') }}',
            data: {
                _token: '{{ csrf_token() }}',
                user_id: $user_id
            },
            method: 'POST',
            success: function(body){
                createModal("prepagos_abertos", "Pedidos pré-pagos em aberto - Representante " + $representante, body, 'modal-lg');
            }
        });
    }

    function createBtaprovarComissao($this){
        html = '';

        @if(!empty(Auth::user()->codigo_representante))
            var codigo_representante = {{ Auth::user()->codigo_representante }};

            @if(Auth::user()->tipo_usuario_id == 19 || Auth::user()->tipo_usuario_id == 14 || Auth::user()->tipo_usuario_id == 13)
                if(codigo_representante == $this.cod_representante){
                    if($this.confirmacao == 'Não' && $this.comissao_fechamento == true && $this.valor_comissao.length > 0){
                        var html = "<a href='#' class='bt-aprove' data-toggle='tooltip' data-trigger='hover' data-valor_comissao='"+$this.valor_comissao+"' data-periodo='"+$this.periodo+"' title='Aprovar'></a>";
                    }
                }
            @else
                if($this.confirmacao == 'Não' && $this.comissao_fechamento == true && $this.valor_comissao.length > 0){
                    var html = "<a href='#' class='bt-aprove' data-toggle='tooltip' data-trigger='hover' data-valor_comissao='"+$this.valor_comissao+"' data-periodo='"+$this.periodo+"' title='Aprovar'></a>";
                }
            @endif
        @endif
            
        return html;
    }

    function aprovarComissao($this){
        var retorno = false;

        $.ajax({
            url: '{{ route('comissao_duplicatas.aprovar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                valor_comissao : $this.data("valor_comissao"),
                periodo : $this.data("periodo")
            },
            async: false,
            method: 'POST',
            success: function(body){
                retorno = true;
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });

        if(retorno == true){
            buscaDados($("#form_filter"));
        }
    }

@endsection
