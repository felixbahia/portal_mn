@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_transportadora_estabelecimento" id="form_transportadora_estabelecimento" onsubmit="return false">
    
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-lg-2">
                {{ Form::select("estabelecimento", $estabelecimentos,'', ["id" => "estabelecimento", "class"=>"form-control"]) }}
 
               </div>
            <div class="col-lg-2">
            {{ Form::text('transportadora_nome', '', ['id' => 'transportadora_nome', 'class' => 'form-control input-label', 'placeholder' => 'Transportador', 'maxlength' => '250']) }}
            </div>
        <div class="col-lg-2">
        {{ Form::select("uf_origem", $uf_origens, '', ["id" => "uf_origem", "class"=>"form-control"]) }}

         </div>
        <div class="col-lg-2">
        {{ Form::select("uf_destino", $uf_destinos, '', ["id" => "uf_destino", "class"=>"form-control"]) }}
    </div>
        <div class="col-lg-2">
            {{ Form::select("tipo_frete", $tipo_fretes, '', ["id" => "tipo_frete", "class"=>"form-control"]) }}
  
        </div>
    </div>
</div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar</button>
    </div>
</form>

@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-transportadora_estabelecimento">
        <thead>
            <tr>
    
                <th class="th-dados">Estabelecimento</th>
                <th class="th-dados">Transportador</th>
                <th class="th-dados">Origem</th>
                <th class="th-dados">Destino</th>
                <th class="th-dados">Frete</th>
                  <th>Editar</th>
                <th>Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready(function(){

        $(document).find("#transportadora_nome").autocomplete(optionsAutoCompleteTransportador());

        $(document).find('#btn-filterform').on('click', function(event){
            event.stopPropagation();
            filtro();
        });
        $(document).find("#btn-create").off("click");
        $(document).find("#btn-create").on("click", function(){
            event.stopPropagation();
            modalAdicionar();
        });
        table_filters_transportadora_estabelecimento = $('#table-filters-transportadora_estabelecimento')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
        }).DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 15,
            "autoWidth": false,
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
            "columnDefs":[
                {
                    "targets": "estabelecimento",
                    "orderable": true
                }
            ]
        });

        table_filters_transportadora_estabelecimento.on('draw', function () {
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(){
                modalEditar($(this).data('id'));
            });
            $(document).find(".bt-delete").off("click");
            $(document).find(".bt-delete").on("click", function(){
                modalDeletar($(this).data('id'));
            });
        });
    });

    function filtro(){
        table_filters_transportadora_estabelecimento.clear().draw();
        $.ajax({
            url: "{{ route('transportadora_estabelecimento.filtro') }}", 
            dataType: 'json',
            data: $(document).find('#form_transportadora_estabelecimento').serialize(),
            method: 'POST',
            success: function(callback){
                dados = callback.response;
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
                        var temp_field = [
                            dados[field].estabelecimento_nome,
                            dados[field].transportadora_nome,
                            dados[field].uf_origem,
                            dados[field].uf_destino,
                            dados[field].tipo_frete,
                            criarBtnEditar(dados[field]),
                            criarBtnDeletar(dados[field])

                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_transportadora_estabelecimento.rows.add(fields_filter).draw().nodes();
                }
            },
            error: function(callback){
                message('Atenção', 'Nenhuma Transportadora localizada.');
            }
        });
    }

    function criarBtnEditar($dados){

        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }


    function criarBtnDeletar($dados){
        var $html = "<a href='#' data-id=\""+$dados.id+"\" class=\"bt-delete\" data-toggle=\"tooltip\" data-placement=\"top\"></a>";
        return $html;
    }

    function modalAdicionar() {
        $.ajax({
            url: '{{ route("transportadora_estabelecimento.modal.salvar") }}',
            data: {_token: '{{ csrf_token() }}'},
            method: 'POST',
            success: function(body){
                var title = 'Cadastro de {{ CustomView::programaName() }}';
                createModal("modal_add_transportadora_estabelecimento", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function modalEditar($id) {
        $.ajax({
            url: '{{ route("transportadora_estabelecimento.modal.editar") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Editar {{ CustomView::programaName() }}';
                createModal("modal_edit_transportadora_estabelecimento", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function modalDeletar($id) {
        $.ajax({
            url: '{{ route("transportadora_estabelecimento.modal.excluir") }}',
            data: {_token: '{{ csrf_token() }}', id: $id},
            method: 'POST',
            success: function(body){
                var title = 'Deletar {{ CustomView::programaName() }}';
                createModal("modal_delete_transportadora_estabelecimento", title, body, '');
            },
            error: function(data){
                message('Alerta', data.responseJSON.error.msg.user);
            }
        });
    }

    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.estabelecimento = $(document).find('#estabelecimento').val();
                $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
           delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_pedido_edit').css('z-index')) + 1));
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
        
                $(document).find("#transportadora_nome").val(ui.item.label);
                return false;
            }
        };
    }
@endsection