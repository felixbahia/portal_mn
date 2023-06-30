@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {!! Form::text('data_inicio', '', ['class' => 'form-control data', 'id' => 'data_inicio', 'placeholder' => 'Emissão de']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::text('data_fim', '', ['class' => 'form-control data', 'id' => 'data_fim', 'placeholder' => 'Emissão até']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::select('devolucao_nota_status_id', $status, '', ['class' => 'form-control', 'id' => 'devolucao_nota_status_id', 'placeholder' => 'Status']) !!}
        </div>
        <div class="col-lg-2">
            {!! Form::select('motivo', $motivos, '', ['class' => 'form-control', 'id' => 'motivo', 'placeholder' => 'Motivo']) !!}
        </div>
        @if($show_gerentes == true)
        <div class="form-group col-lg-2">
            {{ Form::select('gerente', $gerentes, '', ["id" => 'gerentes', 'class' => 'form-control busca_left', 'placeholder' => 'Gerentes'])}}
        </div>
        @endif
        @if($show_representantes == true)
        <div class="form-group col-lg-2">
            {{ Form::select('representante', $representantes, '', ["id" => 'representante', 'class' => 'form-control', 'placeholder' => 'Representantes'])}}
        </div>
        @endif
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-index">
        <thead>
            <tr>
                <th>Status do processo</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_number">Valor</th>
                <th class='lupa'>Processo</th>
                <th class='lupa'>Motivo</th>
                <th class='lupa'>Gerente</th>
                <th class='lupa'>Representante</th>
                <th class='lupa'>Separador</th>
                <th class='lupa'>Produto</th>
            </tr>
        </thead>
        <tbody>

        </tbody>
        <tfoot>
            <th class="text-left">Total</th>
            <th id="quantidade-total" class="text-right"></th>
            <th id="valor-total" class="text-right"></th>
            <th id='processo_total'></th>
            <th id='motivo_total'></th>
            <th id='gerente_total'></th>
            <th id='representante_total'></th>
            <th id='separador_total'></th>
            <th id='produto_total'></th>
        </tfoot>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {

        $(document).find("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        $(document).find('#btn-clearform').on('click', function(){
            limparDadosTela()
        })

        table_filters_index.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });

        $('.data').mask("99/99/9999");
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });

        $(document).find("#gerentes").on('change', function(event){
            var campos = $(document).find("#representante");
            var indice = campos.index(event.target) + 1;
            var seletor = $(campos[indice]);
            checkDadosUser(seletor, $(this).val());
        });
    });

    table_filters_index_options = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
        'columnDefs': [
            {
                "class": "tb_number",
                'targets': "tb_number",
            },
            {
                'targets': 'lupa',
                'orderable': false,
                'width': '1px'
            }
        ]
    };

    table_filters_index = $('#table-filters-index').DataTable(table_filters_index_options);

    function showModal($this, $tela = null){
        var devolucao_nota_status_id = $($this).data("status");
        var hash = $($this).data("hash");
        var status_descricao = $($this).data("status-descricao");
        $.ajax({
            url: "{{ route('consulta_devolucao.modal') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                 hash: hash,
                 devolucao_nota_status_id: devolucao_nota_status_id,
                 status_descricao: status_descricao,
                 tela: $tela
            },
            success: function(body){
                createModal('modal_message_edit', "Processos de devolução: " + status_descricao, body, 'modal-lg');
                $(document).find('#modal_message_edit').on('shown.bs.modal', function(){
                    table_filters_devolucoes_total.columns.adjust().draw()
                })
            }
        });
    }

    function createBtnView($status, $hash, $status_descricao){
        var $html = "<a href=\"#\" class=\"bt-view\" data-status=\""+$status+"\" data-status-descricao=\""+$status_descricao+"\" data-hash=\""+$hash+"\"data-toggle=\"tooltip\" data-placement=\"top\" title=\"Por processos\" onclick=\"showModal(this)\"></a>";
        return $html;
    }

    function createBtnViewMotivo($status, $hash, $status_descricao){
        var $html = "<a href=\"#\" class=\"bt-view\" data-status=\""+$status+"\" data-status-descricao=\""+$status_descricao+"\" data-hash=\""+$hash+"\"data-toggle=\"tooltip\" data-placement=\"top\" title=\"Por motivo\" onclick=\"showModal(this, 'motivo')\"></a>";
        return $html;
    }

    function createBtnViewGerente($status, $hash, $status_descricao){
        var $html = "<a href=\"#\" class=\"bt-view\" data-status=\""+$status+"\" data-status-descricao=\""+$status_descricao+"\" data-hash=\""+$hash+"\"data-toggle=\"tooltip\" data-placement=\"top\" title=\"Por gerente\" onclick=\"showModal(this, 'gerente')\"></a>";
        return $html;
    }

    
    function createBtnViewRepresentante($status, $hash, $status_descricao){
        var $html = "<a href=\"#\" class=\"bt-view\" data-status=\""+$status+"\" data-status-descricao=\""+$status_descricao+"\" data-hash=\""+$hash+"\"data-toggle=\"tooltip\" data-placement=\"top\" title=\"Por representante\" onclick=\"showModal(this, 'representante')\"></a>";
        return $html;
    }

    function createBtnViewSeparador($status, $hash, $status_descricao){
        var $html = "<a href=\"#\" class=\"bt-view\" data-status=\""+$status+"\" data-status-descricao=\""+$status_descricao+"\" data-hash=\""+$hash+"\"data-toggle=\"tooltip\" data-placement=\"top\" title=\"Por separador\" onclick=\"showModal(this, 'separador')\"></a>";
        return $html;
    }

    function createBtnViewProduto($status, $hash, $status_descricao){
        var $html = "<a href=\"#\" class=\"bt-view\" data-status=\""+$status+"\" data-status-descricao=\""+$status_descricao+"\" data-hash=\""+$hash+"\"data-toggle=\"tooltip\" data-placement=\"top\" title=\"Por produto\" onclick=\"showModal(this, 'produto')\"></a>";
        return $html;
    }

    function filterAjax(data_form){
        var $return;
        limparDadosTela();
        $.ajax({
            url: "{{ route('consulta_devolucao.filter') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status == 'success'){
                    var data = callback.response.linhas;
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].status,
                            data[field].quantidade,
                            data[field].valor,
                            createBtnView(data[field].devolucao_nota_status_id, callback.response.hash, data[field].status),
                            createBtnViewMotivo(data[field].devolucao_nota_status_id, callback.response.hash, data[field].status),
                            createBtnViewGerente(data[field].devolucao_nota_status_id, callback.response.hash, data[field].status),
                            createBtnViewRepresentante(data[field].devolucao_nota_status_id, callback.response.hash, data[field].status),
                            createBtnViewSeparador(data[field].devolucao_nota_status_id, callback.response.hash, data[field].status),
                            createBtnViewProduto(data[field].devolucao_nota_status_id, callback.response.hash, data[field].status),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_index.rows.add(fields_filter).draw();

                    $(document).find('#quantidade-total').html(callback.response.total.quantidade);
                    $(document).find('#valor-total').html(callback.response.total.valor);
                    $(document).find('#processo_total').html(createBtnView('', callback.response.hash, 'Total'));
                    $(document).find('#motivo_total').html(createBtnViewMotivo('', callback.response.hash, 'Total'));
                    $(document).find('#gerente_total').html(createBtnViewGerente('', callback.response.hash, 'Total'));
                    $(document).find('#representante_total').html(createBtnViewRepresentante('', callback.response.hash, 'Total'));
                    $(document).find('#separador_total').html(createBtnViewSeparador('', callback.response.hash, 'Total'));
                    $(document).find('#produto_total').html(createBtnViewProduto('', callback.response.hash, 'Total'));
                }
            }
        });
    }

    function checkDadosUser(campo_busca, valor){

        console.log(valor);
        primeira_opcao = $(campo_busca).find("option:first").html();
        var campos = "<option value=\"\">" + primeira_opcao + "</option>";        
        $(campo_busca).html("");

        if(valor === ""){
            $(campo_busca).html(campos);
            return;
        }

        $.ajax({
            url: "{{ route('usuario.dados_subordinados') }}",
            dataType: 'json',
            data: {_token:'{{ csrf_token() }}', user: valor},
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){
                    var response = callback.response;
                    for(var line in response){
                        campos += "<option value=\""+response[line].id+"\">"+response[line].name+"</option>";
                    }
                }
            },
            error: function(data){
                hide_loader();
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        }).done(function(){
            $(campo_busca).html(campos).focus();
        });
    }

    function limparDadosTela(){
        $(document).find('#quantidade-total').html('');
        $(document).find('#valor-total').html('');
        $(document).find('#processo_total').html('');
        $(document).find('#motivo_total').html('');
        $(document).find('#gerente_total').html('');
        $(document).find('#representante_total').html('');
        $(document).find('#separador_total').html('');
        $(document).find('#produto_total').html('');
        table_filters_index.clear().draw();
    }
@endsection