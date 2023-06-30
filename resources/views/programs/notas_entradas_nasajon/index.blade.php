@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="form-group col-lg-3 col-xl-4">
            <div class="input-group">
                {{ Form::text('fornecedor', '', ['id' => 'fornecedor_filtro', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            <input type="text" name="data_emissao_inicio" id="data_emissao_inicio" class='data' placeholder="Data de emissão de">
        </div>
        <div class="col-lg-2">
            <input type="text" name="data_emissao_fim" id="data_emissao_fim" class='data' placeholder="Data de emissão até">
        </div>
        <div class="col-lg-2">
            <input type="text" name="numero_nota" id="numero_nota" value="" placeholder="Nº da nota">
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
    <table class="table table-striped" id="table-filters-notas">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th class='number_format'>Número da Nota</th>
                <th>Fornecedor</th>
                <th class='date_format'>Data de Emissão</th>
                <th class='date_format'>Data de Entrada</th>
                <th>Natureza de Operação</th>
                <th class='number_format'>Valor da nota</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    
    $(document).ready(function (){

        $(document).find("#bt-search-fornecedor-busca").off("click");
        $(document).find("#bt-search-fornecedor-busca").on("click", function(event){
            event.stopPropagation();
            showModalFornecedorBusca($(this).data("route"));
            return false;
        });
        $(document).find("#fornecedor_filtro").autocomplete(optionsAutoCompleteFornecedorFiltro());

        $(document).find('#btn-filterform').on('click', function(){
            filterDados();
        });

        $(document).find(".data").mask("00/00/0000");
        $(document).find('.data').datepicker({
            language: 'pt-BR',
            zIndex: 100,
            autoHide: true,
        });

        $(document).find("#btn-clearform").on("click", function(){
            table_filters.clear().draw();
        });

    });

    function showModalFornecedorBusca(){
        var title = "Busca de Fornecedores";
        $.ajax({
            url: '{{ route('fornecedor.busca.index') }}',
            method: 'GET',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body){
                $(document).find('#fornecedor_searsh_show').remove();
                createModal("fornecedor_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#fornecedor_searsh_show");

                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosFornecedorBusca($(this), event);
                        });
                    });
                });

            }
        });
    }

    function returnDadosFornecedorBusca($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_filtro").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#fornecedor_searsh_show").modal("hide");
    }
    function optionsAutoCompleteFornecedorFiltro(){
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
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#fornecedor_filtro").val(ui.item.label);
                return false;
            }
        };
    }
    function filterDados(){
        form_filter = $('#form_filter');
        var argumentos = form_filter.serialize();
        
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.filter') }}',
            type: 'POST',
            data: argumentos,
            success: function(callback){     
                
                data = callback.response;

                var result_array = [];

                table_filters.clear().draw();

                for (var field in data){
                    var temp_array = [
                        data[field].estabelecimento_nome,
                        createLinkNf(data[field]),
                        data[field].fornecedor,
                        data[field].data_emissao,
                        data[field].data_entrada,
                        data[field].natureza_operacao,
                        data[field].valor_documento,
                    ];
                    result_array.push(temp_array);
                }
                table_filters.rows.add(result_array).draw();
            }
        });
    }

    table_filters = $('#table-filters-notas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
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
            }        },
        "columnDefs": [
            {
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                "targets": 'date_format',
                "className": 'date_format',
            },
        ],
        "order": [[ 2, 'desc' ]]
    });

    function showNotasDetalhes($id){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.nota')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }

    function createLinkNf($this){
        var html = "";
        if($this.numero_nota !== ""){
            html = "<a href='#' onclick=\"showNotasDetalhes('" + $this.id + "')\">" + $this.numero + "</a>";
        }

        return html;
    }
@endsection