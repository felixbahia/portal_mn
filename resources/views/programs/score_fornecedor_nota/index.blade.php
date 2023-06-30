@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-2">
            {{ Form::select("estabelecimento", $estabelecimentos, '', ["id" => "estabelecimento", "class"=>"form-control", 'placeholder' => 'Estabelecimentos']) }}
        </div>
        <div class="col-lg-2">
            <div class="input-group">
                {{ Form::text('fornecedor', '', ['id' => 'fornecedor_filtro', 'class' => 'form-control input-label', 'placeholder' => 'Fornecedor']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-fornecedor-busca"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            {{ Form::text('data_emissao_inicio', date('d/m/Y', strtotime('-1 year')), ['id' => 'data_emissao_inicio', 'class' => 'data', 'placeholder' => 'Data de Entrada de']) }}
        </div>
        <div class="col-lg-2">
            {{ Form::text('data_emissao_fim', date('d/m/Y'), ['id' => 'data_emissao_fim', 'class' => 'data', 'placeholder' => 'Data de Entrada até']) }}
        </div>
        <div class="col-lg-2">
            {{ Form::text('numero_nota', '', ['id' => 'numero_nota', 'placeholder' => 'Nº da nota']) }}
        </div>
        <div class="col-lg-2">
            {{ Form::select('score', ['true' => 'Com Score','false' => 'Sem Score'], '', ["id" => 'score', 'class' => 'form-control', 'placeholder' => 'Todos'])}}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Lançar Score</button>
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
                <th>Transportadora</th>
                <th class='number_format'>Valor da nota</th>
                <th>Score</th>
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

        $(document).find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true
        });
        $(document).find('.data').mask('00/00/0000');
        $('#data_emissao_inicio').on('pick.datepicker', function (e) {
            if($('#data_emissao_fim').datepicker('getDate') < e.date){
                $('#data_emissao_fim').val('');
            }
            $('#data_emissao_fim').datepicker('setStartDate', e.date);
        });
        
        $(document).find("#btn-clearform").on("click", function(){
            table_filters.clear().draw();
        });

        table_filters = $('#table-filters-notas').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": true,
            "orderMulti": false,
            "pageLength": 15,
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

        table_filters.on('draw', function () {
            $(document).find(".bt-modal").off("click");
            $(document).find(".bt-modal").on("click", function(event){
                event.stopPropagation();
                showModal($(this));
            });
        });

        $('#btn-create').click( function() {
            var table_inputs = table_filters.$('input').serialize();

            if(table_inputs == ''){
                message('Atenção', 'Escolha pelo menos uma nota.');
                return false;
            }

            $.ajax({
                url: '{{ route('score_fornecedor_nota.modal.lancamento_formulario') }}',
                method: 'POST',
                data: {_token: "{{ csrf_token() }}", lancar: table_inputs},
                success: function(body){
                    createModal('modal_formulario_score_notas', 'Lançar Score', body, 'modal-lg');
                }
            });

            return false;
        } );

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
        table_filters.clear().draw();
        $(document).find('.error-message').remove();
        $.ajax({
            url: '{{ route('score_fornecedor_nota.filtro') }}',
            type: 'POST',
            data: argumentos,
            success: function(callback){     
                
                data = callback.response.response;

                var result_array = [];

                table_filters.clear().draw();

                for (var field in data){
                    var temp_array = [
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data[field].estabelecimento+"\">"+data[field].estabelecimento+"</div></div>",
                        createLinkNf(data[field]),
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data[field].fornecedor+"\">"+data[field].fornecedor+"</div></div>",
                        data[field].emissao,
                        data[field].entrada,
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data[field].operacao+"\">"+data[field].operacao+"</div></div>",
                        "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+data[field].transportadora+"\">"+data[field].transportadora+"</div></div>",
                        data[field].valor,
                        createBtViewFormularioScore(data[field]),
                    ];
                    result_array.push(temp_array);
                }
                table_filters.rows.add(result_array).draw();
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    $.each(data, function(index, el) {
                        form_filter.find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>');
                        form_filter.find('input[name="'+index+'"]').eq(0).addClass('error');
                    });
                    form_filter.find('input.error').eq(0).focus();
                }
            }
        });
    }


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

    function createBtViewFormularioScore($this){
        if($this.score == false){
            var html = "<center><input type='checkbox' name='lancar[]' id='lancar[]' value=\""+$this.id_score+"\" autocomplete='off'></center>"
        }else if($this.score == true){
            var html = "<center><a href=\"#\" data-route=\"{{ route('score_fornecedor_nota.modal.edicao_formulario') }}\" data-id=\""+$this.id_score+"\" data-title='SCORE - "+$this.fornecedor+" - Nota - "+$this.numero+"' class='bt-modal'><i class='bt-edit'></i></a></center>"
        }
    
        return html;
    }

	function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var title = $($this).data("title");
    
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_formulario_score_notas', title, body, 'modal-lg');
            }
        });
    }
@endsection