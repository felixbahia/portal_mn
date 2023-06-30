@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">

        <div class="col-lg-2">   
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento', 'class' => 'form form-control', 'placeholder' => 'Estabelecimento']) !!}
        </div>
        <div class="col-lg-3">
            <div class="input-group">                
                <input type="text" class='form-control input-label' name="cliente" id="cliente" placeholder="Cliente" maxlength="250" />
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-2">
            <input type="text" name="nota_fiscal" id="nota_fiscal" class="form-control" placeholder="Número da Nota" maxlength="250" />
        </div>
        <div class="col-lg-2">
            {!! Form::select('status', $status, '', ['id' => 'status', 'class' => 'form form-control', 'placeholder' => 'Selecione um status']) !!}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <input type="button" id="btn-novo" class="btn btn-success float-right" value="Nova devolução" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-notas">
        <thead>
            <tr>
                <th><span data-toggle="tooltip" data-placement="top" title="Ordem de devolução" data-original-title="Ordem de devolução">O.D.</span></th>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th>Nota</th>
                <th class='tb_number'>Valor</th>
                <th>Tipo</th>
                <th>Motivo</th>
                <th>Status</th>
                <th></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')

    $(document).ready(function(){
        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteBusca($(this).data("route"));
            return false;
        });

        $(document).find("#cliente").autocomplete(optionsAutoCompleteClienteFiltro());

        $(document).find('#btn-filterform').on('click', function(){
            buscarNotas();
        });

        $(document).find("#btn-novo").on("click", function(){
            novaDevolucao();
        });

        $(document).find("#btn-clearform").on("click", function(){
            table_filters_notas.clear().draw();
        });

    });

    function showModalClienteBusca(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: "POST",
            data: {
                _token: "{{csrf_token()}}"
            },
            success: function(body){
                $(document).find("#cliente_searsh_show").remove();
                createModal("cliente_searsh_show", title, body, "modal-lg");
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on("draw", function () {
                        modal.find("tbody").find("tr").off("click");
                        modal.find("tbody").find("tr").on("click", function(event){
                            returnDadosClienteBusca($(this), event);
                        });
                    });
                });
            }
        });
    }

    table_filters_notas = $("#table-filters-notas").DataTable({
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
            "thousands":      ".",
            "decimal":        ",",
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
                'targets': 0,
                'class': 'tb_number',
                'width': '1vw',
            },
            {
                "class": "tb_number", 
                "type": 'num-fmt', 
                "targets": "tb_number",
                render: $.fn.dataTable.render.number( '.', ',', 2 )
            },
            { "targets": [-1, -2], "orderable": false},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ]]
        }
    );

    function returnDadosClienteBusca($dados, event){
        if($dados.find("td").eq(0).hasClass("dataTables_empty")){
            return false;
        }
        $(document).find("#cliente").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");


    }

    function optionsAutoCompleteClienteFiltro(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route("clientes.autocomplete") }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message("Atenção", "Nenhum cliente encontrado");
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#cliente").val(ui.item.label);
                return false;
            }
        };
    }

    function buscarNotas(){
        
        table_filters_notas.clear().draw();

        $.ajax({
            url: '{{ route("devolucao_nota.filter") }}',
            dataType: 'json',
            method: 'POST',
            data: $(document).find('#form_filter').serialize(),
            success: function(data){

                var response = data.response.dados;
                var linhas = [];

                for(var field in response){
                    
                    titulo_linha = [
                        createBtnLog(response[field].id, response[field].id_requisicao),
                        response[field].estabelecimento,
                        response[field].cliente,
                        createLinkNota(response[field].nota_fiscal, response[field].nota_id),
                        response[field].valor,
                        response[field].valor_parcial,
                        response[field].motivo,
                        response[field].status_exibir,
                        createBtnEdit(response[field].id, response[field].status),
                        createBtnDelete(response[field].id, response[field].status)
                    ]

                    linhas.push(titulo_linha);

                }

                table_filters_notas.rows.add(linhas).nodes().draw();

            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)  
            }
        });
    }

    function novaDevolucao(){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            url: '{{ route('devolucao_nota.modal.novo') }}',
            success: function(data){
                createModal('nova-devolucao-modal', 'Nova requisição de devolução', data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createBtnEdit($id, $status){

        if(['8'].includes($status)){
            var $html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"editar_nota('"+$id+"');\"></a>";
        }
        else{
            var $html = "<a href=\"#\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"visualizar_nota('"+$id+"');\"></a>";
        }

        return $html;
    }

    function editar_nota($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota.modal.editar') }}',
            success: function(data){
                createModal('editar-devolucao-modal', 'Editar requisição de devolução', data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createBtnDelete($id, $status){

        if(['1','8'].includes($status)){
            var $html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"excluir_nota('"+$id+"');\"></a>";
        }
        else{
            var $html = "";
        }
        
        return $html;
    }

    function excluir_nota($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota.modal.excluir') }}',
            success: function(data){
                createModal('excluir-devolucao-modal', 'Excluir requisição de devolução', data, '');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function visualizar_nota($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota.modal.visualizar') }}',
            success: function(data){
                createModal('editar-devolucao-modal', 'Detalhes da requisição de devolução', data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createLinkNota($numero, $nota){

        var html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModalNota('"+$nota+"');\">"+$numero+"</a>";

        return html;
    }

    function showModalNota($id){
        var url = '{{ route('notas_nasajon.modal.exibir') }}';
        var modal_class = 'modal-lg';
        var title = 'Detalhes da nota';
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_nota: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }

    function createBtnLog($id, $numero){
        var html = "<a href=\"#\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Movimentos da requisição\" onclick=\"showModalLog('"+$id+"');\">"+$numero+"</a>";

        return html;
    }

    function showModalLog($id){
        var url = '{{ route('devolucao_nota.modal.logs') }}';
        var modal_class = 'modal-lg';
        var title = 'Movimentos da requisição';
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_log_requisicao', title, body, modal_class);
            }
        });
    }
@endsection
</script>