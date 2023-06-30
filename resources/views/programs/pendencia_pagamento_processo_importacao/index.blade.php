@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class='form-row'>
        <div class="col-lg-12">
            <div class="input-group">
                {{ Form::text('fornecedor_filtro', '', ['id' => 'fornecedor_filtro', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedorFiltro($(this))"]) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor_filtro-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <br>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters_pendencias">
        <thead>
            <tr>
                <th></th> 
                <th class="tb_number">{{$datas[0]}}</th>
                <th class="tb_number">{{$datas[1]}}</th> 
                <th class="tb_number">{{$datas[2]}}</th>  
                <th class="tb_number">{{$datas[3]}}</th>                  
                <th class="tb_number">{{$datas[4]}}</th>
                <th class="tb_number">{{$datas[5]}}</th>
                <th class="tb_number">{{$datas[6]}}</th>
                <th class="tb_number">{{$datas[7]}}</th> 
                <th class="tb_number">{{$datas[8]}}</th>  
                <th class="tb_number">{{$datas[9]}}</th>                  
                <th class="tb_number">{{$datas[10]}}</th>
                <th class="tb_number">{{$datas[11]}}</th>
                <th class="tb_number">Total</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $("#btn-create").off("click");
        $("#btn-create").on("click", function(){
            showModalCreate();
        });

        $("#btn-filterform").off("click");
        $("#btn-filterform").on("click", function(){
            filterAjax($("#form_filter").serialize());
        });

        table_filters = $('#table-filters_pendencias').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "orderMulti": false,
            "ordering": false,
            "language": {
                "decimal":        ",",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
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
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    "orderable": false
                },
                {
                    'targets': 'tb_number',
                    'class': 'tb_number',
                },
                {
                    'targets': 'tb_date',
                    'class': 'tb_date',
                }
            ],
        });

        $(document).find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        $(document).find('.data').mask('00/00/0000');

        $(document).find("#bt-search-fornecedor_filtro-busca").on("click", function(){
            showModalFornecedorFiltro($(this).data("route"), "Lista de Fornecedores", "fornecedor_filtro");
        });

    });

    function filterAjax(data_form){
        table_filters.clear().draw();
        $.ajax({
            url: "{{ route('pendencia_pagamento_processo_importacao.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
                var data = callback.response;
                var fields_filter = [];
                var temp_field = [
                    data.proformas['red'].descricao,
                    createBtViewDetalhes(data.proformas['red'].inteiro_1, 'red', "{{$datas_dados[0]}}", "{{$datas[0]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_2, 'red', "{{$datas_dados[1]}}", "{{$datas[1]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_3, 'red', "{{$datas_dados[2]}}", "{{$datas[2]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_4, 'red', "{{$datas_dados[3]}}", "{{$datas[3]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_5, 'red', "{{$datas_dados[4]}}", "{{$datas[4]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_6, 'red', "{{$datas_dados[5]}}", "{{$datas[5]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_7, 'red', "{{$datas_dados[6]}}", "{{$datas[6]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_8, 'red', "{{$datas_dados[7]}}", "{{$datas[7]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_9, 'red', "{{$datas_dados[8]}}", "{{$datas[8]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_10, 'red', "{{$datas_dados[9]}}", "{{$datas[9]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_11, 'red', "{{$datas_dados[10]}}", "{{$datas[10]}}"),
                    createBtViewDetalhes(data.proformas['red'].inteiro_12, 'red', "{{$datas_dados[11]}}", "{{$datas[11]}}"),
                    data.proformas['red'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    data.proformas['imposto'].descricao,
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_1, 'imposto', "{{$datas_dados[0]}}", "{{$datas[0]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_2, 'imposto', "{{$datas_dados[1]}}", "{{$datas[1]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_3, 'imposto', "{{$datas_dados[2]}}", "{{$datas[2]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_4, 'imposto', "{{$datas_dados[3]}}", "{{$datas[3]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_5, 'imposto', "{{$datas_dados[4]}}", "{{$datas[4]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_6, 'imposto', "{{$datas_dados[5]}}", "{{$datas[5]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_7, 'imposto', "{{$datas_dados[6]}}", "{{$datas[6]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_8, 'imposto', "{{$datas_dados[7]}}", "{{$datas[7]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_9, 'imposto', "{{$datas_dados[8]}}", "{{$datas[8]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_10, 'imposto', "{{$datas_dados[9]}}", "{{$datas[9]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_11, 'imposto', "{{$datas_dados[10]}}", "{{$datas[10]}}"),
                    createBtViewDetalhes(data.proformas['imposto'].inteiro_12, 'imposto', "{{$datas_dados[11]}}", "{{$datas[11]}}"),
                    data.proformas['imposto'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    data.proformas['antecipacao'].descricao,
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_1, 'antecipacao', "{{$datas_dados[0]}}", "{{$datas[0]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_2, 'antecipacao', "{{$datas_dados[1]}}", "{{$datas[1]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_3, 'antecipacao', "{{$datas_dados[2]}}", "{{$datas[2]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_4, 'antecipacao', "{{$datas_dados[3]}}", "{{$datas[3]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_5, 'antecipacao', "{{$datas_dados[4]}}", "{{$datas[4]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_6, 'antecipacao', "{{$datas_dados[5]}}", "{{$datas[5]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_7, 'antecipacao', "{{$datas_dados[6]}}", "{{$datas[6]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_8, 'antecipacao', "{{$datas_dados[7]}}", "{{$datas[7]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_9, 'antecipacao', "{{$datas_dados[8]}}", "{{$datas[8]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_10, 'antecipacao', "{{$datas_dados[9]}}", "{{$datas[9]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_11, 'antecipacao', "{{$datas_dados[10]}}", "{{$datas[10]}}"),
                    createBtViewDetalhes(data.proformas['antecipacao'].inteiro_12, 'antecipacao', "{{$datas_dados[11]}}", "{{$datas[11]}}"),
                    data.proformas['antecipacao'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    data.proformas['normal'].descricao,
                    createBtViewDetalhes(data.proformas['normal'].inteiro_1, 'normal', "{{$datas_dados[0]}}", "{{$datas[0]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_2, 'normal', "{{$datas_dados[1]}}", "{{$datas[1]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_3, 'normal', "{{$datas_dados[2]}}", "{{$datas[2]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_4, 'normal', "{{$datas_dados[3]}}", "{{$datas[3]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_5, 'normal', "{{$datas_dados[4]}}", "{{$datas[4]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_6, 'normal', "{{$datas_dados[5]}}", "{{$datas[5]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_7, 'normal', "{{$datas_dados[6]}}", "{{$datas[6]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_8, 'normal', "{{$datas_dados[7]}}", "{{$datas[7]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_9, 'normal', "{{$datas_dados[8]}}", "{{$datas[8]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_10, 'normal', "{{$datas_dados[9]}}", "{{$datas[9]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_11, 'normal', "{{$datas_dados[10]}}", "{{$datas[10]}}"),
                    createBtViewDetalhes(data.proformas['normal'].inteiro_12, 'normal', "{{$datas_dados[11]}}", "{{$datas[11]}}"),
                    data.proformas['normal'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    data.proformas['carta_x'].descricao,
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_1, 'carta_x', "{{$datas_dados[0]}}", "{{$datas[0]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_2, 'carta_x', "{{$datas_dados[1]}}", "{{$datas[1]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_3, 'carta_x', "{{$datas_dados[2]}}", "{{$datas[2]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_4, 'carta_x', "{{$datas_dados[3]}}", "{{$datas[3]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_5, 'carta_x', "{{$datas_dados[4]}}", "{{$datas[4]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_6, 'carta_x', "{{$datas_dados[5]}}", "{{$datas[5]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_7, 'carta_x', "{{$datas_dados[6]}}", "{{$datas[6]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_8, 'carta_x', "{{$datas_dados[7]}}", "{{$datas[7]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_9, 'carta_x', "{{$datas_dados[8]}}", "{{$datas[8]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_10, 'carta_x', "{{$datas_dados[9]}}", "{{$datas[9]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_11, 'carta_x', "{{$datas_dados[10]}}", "{{$datas[10]}}"),
                    createBtViewDetalhes(data.proformas['carta_x'].inteiro_12, 'carta_x', "{{$datas_dados[11]}}", "{{$datas[11]}}"),
                    data.proformas['carta_x'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                    "",
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    data.proformas['total_geral_real'].descricao,
                    data.proformas['total_geral_real'].inteiro_1,
                    data.proformas['total_geral_real'].inteiro_2,
                    data.proformas['total_geral_real'].inteiro_3,
                    data.proformas['total_geral_real'].inteiro_4,
                    data.proformas['total_geral_real'].inteiro_5,
                    data.proformas['total_geral_real'].inteiro_6,
                    data.proformas['total_geral_real'].inteiro_7,
                    data.proformas['total_geral_real'].inteiro_8,
                    data.proformas['total_geral_real'].inteiro_9,
                    data.proformas['total_geral_real'].inteiro_10,
                    data.proformas['total_geral_real'].inteiro_11,
                    data.proformas['total_geral_real'].inteiro_12,
                    data.proformas['total_geral_real'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    data.proformas['total_geral_dolar'].descricao,
                    data.proformas['total_geral_dolar'].inteiro_1,
                    data.proformas['total_geral_dolar'].inteiro_2,
                    data.proformas['total_geral_dolar'].inteiro_3,
                    data.proformas['total_geral_dolar'].inteiro_4,
                    data.proformas['total_geral_dolar'].inteiro_5,
                    data.proformas['total_geral_dolar'].inteiro_6,
                    data.proformas['total_geral_dolar'].inteiro_7,
                    data.proformas['total_geral_dolar'].inteiro_8,
                    data.proformas['total_geral_dolar'].inteiro_9,
                    data.proformas['total_geral_dolar'].inteiro_10,
                    data.proformas['total_geral_dolar'].inteiro_11,
                    data.proformas['total_geral_dolar'].inteiro_12,
                    data.proformas['total_geral_dolar'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                var temp_field = [
                    data.proformas['total_geral_convertido'].descricao,
                    data.proformas['total_geral_convertido'].inteiro_1,
                    data.proformas['total_geral_convertido'].inteiro_2,
                    data.proformas['total_geral_convertido'].inteiro_3,
                    data.proformas['total_geral_convertido'].inteiro_4,
                    data.proformas['total_geral_convertido'].inteiro_5,
                    data.proformas['total_geral_convertido'].inteiro_6,
                    data.proformas['total_geral_convertido'].inteiro_7,
                    data.proformas['total_geral_convertido'].inteiro_8,
                    data.proformas['total_geral_convertido'].inteiro_9,
                    data.proformas['total_geral_convertido'].inteiro_10,
                    data.proformas['total_geral_convertido'].inteiro_11,
                    data.proformas['total_geral_convertido'].inteiro_12,
                    data.proformas['total_geral_convertido'].inteiro_total,
                ];
                fields_filter.push(temp_field);
                table_filters.rows.add(fields_filter).draw().nodes();
            }
        });
    }

    function createBtViewDetalhes($valor, $modalidade, $data, $data_descricao){
        if($modalidade == "antecipacao"){
            $titulo = "Antecipação à Pagar(sinal)(US$) " + $data_descricao;
        }else if($modalidade == "carta_x"){
            $titulo = "Pagamento Fornecedor Carta X(US$) " + $data_descricao;
        }else if($modalidade == "normal"){
            $titulo = "Pagamento Fornecedor Normal(US$) " + $data_descricao;
        }else if($modalidade == "imposto"){
            $titulo = "Pagamento Imposto(R$) " + $data_descricao;
        }else{
            $titulo = "Hedge(US$) " + $data_descricao;
        }        
        
        html = "<a href=\"#\"  data-toggle='tooltip' data-html='true' data-modalidade=\""+$modalidade+"\" data-data=\""+$data+"\" data-titulo=\""+$titulo+"\" onclick=\"abriModalDetalhes($(this))\">"+$valor+"</a>";

        return html;
    }

    function abriModalDetalhes($this){
        var data = $($this).data("data");
        var modalidade = $($this).data("modalidade");
        var title = $($this).data("titulo");
        $.ajax({
            url: '{{ route('pendencia_pagamento_processo_importacao.modal.detalhes') }}',
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}", 
                data : data,
                modalidade : modalidade,
            },
            success: function(body){
                createModal("model_detalhes", title, body, 'modal-lg');
                var modal = $("#model_detalhes");
            }
        });
    }

    function showModalFornecedorFiltro(url, title, campo){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedorFiltro($(this), campo);
                        });
                    });
                });
            }
        });
    }
    function returnDadosFornecedorFiltro($this, campo){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        form.find("#"+campo).val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    }

    function optionsFornecedorFiltro($this){
        esconderPopoverTooltip();
        $this.autocomplete(optionsAutoCompleteFornecedorFiltro($this));   
    }

    function optionsAutoCompleteFornecedorFiltro($this){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('fornecedor.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#form_filter').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum fornecedor encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $this.val(ui.item.label);
                return false;
            }
        };
    }
@endsection