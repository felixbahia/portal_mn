@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="col-sm-2">   
                <select name="estabelecimento" id="estabelecimento" class="form-control">
                    <option value=''>Estabelecimento</option>
                    @foreach ($estabelecimentos as $key => $value)
                    <option value='{{$key}}'>{{$value}}</option>
                    @endforeach
                </select>
            </div>
            @if ($check_gerentes === true || $check_vendedor_representante === true)
                @if($check_gerentes === true)
                <div class="form-group col-sm-2">
                    {{ Form::select('gerentes', $gerentes, '', ["id" => 'gerentes', 'class' => 'form-control busca_left', 'placeholder' => 'Gerentes'])}}
                </div>
                @endif
                @if($check_vendedor_representante === true)
                <div class="form-group col-sm-2">
                    {{ Form::select('vendedor_representante', $vendedor_representante, '', ["id" => 'vendedor_representante', 'class' => 'form-control', 'placeholder' => 'Vendedor Interno / Representantes'])}}
                </div>
                @endif
            @endif
            @if(!Auth::user()->hasRole('Cliente'))
                <div class="form-group col-sm-3">
                    <div class="input-group">                
                        <input type="text" class='form-control input-label' name="cliente" id="cliente" placeholder="Cliente" maxlength="250" />
                        <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                    </div>
                </div>
            @endif
            <div class="col-sm-2 form-group">
                {{ Form::text('nota_fiscal','',['id' => 'nota_fiscal', 'class' => 'form-control', 'placeholder' => 'Número da Nota','maxlength' => '250']) }}
            </div>
        </div>
        <div class="row">
            <div class="col-sm-2 form-group">
                {{ Form::text('ordem_devolucao','',['id' => 'ordem_devolucao', 'class' => 'form-control', 'placeholder' => 'O.D.','maxlength' => '250']) }}
            </div>
            <div class="col-sm-1 form-group">
                {{ Form::text('data_inicio','',['id' => 'data_inicio', 'class' => 'form-control data', 'placeholder' => 'Data Início MM/AAAA','maxlength' => '10']) }}
            </div>
            <div class="col-sm-1 form-group">
                {{ Form::text('data_fim', '',['id' => 'data_fim', 'class' => 'form-control data', 'placeholder' => 'Data Fim MM/AAAA','maxlength' => '10']) }}
            </div>
            @if(in_array(Auth::id(), [863, 576]) || Auth::id() == 9334 || Auth::user()->tipo_usuario_id == 1)
            <div class="offset-sm-3 col-sm-2 mt-2 form-group">
                {!! Form::checkbox('sem_frete', '1', false, ['id' => 'sem_frete', 'class' => 'form-check-input']) !!}
                {!! Form::label('sem_frete', 'Sem frete especificado', ['class' => 'form-check-label']) !!}
            </div>
            @endif
            <div class="col-sm-2 form-group">
                {!! Form::select('status', $status, 'todos_abertos', ['id' => 'status', 'class' => 'form form-control', 'placeholder' => 'Selecione uma etapa']) !!}
            </div>
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
                @if(!Auth::user()->hasRole('Cliente'))
                <th class="campo_minimo_nao_ordenar"></th>
                @endif
                <th class="campo_minimo tb_number"><span data-toggle="tooltip" data-placement="top" title="Ordem de devolução" data-original-title="Ordem de devolução">O.D.</span></th>
                <th class="campo_minimo">Estabelecimento</th>
                <th>Cliente</th>
                <th class='tb_number'>Nota</th>
                <th class='sort-date'>Emissão</th>
                <th class='valor'>Valor</th>
                <th>Tipo</th>
                <th>Motivo</th>
                <th>Status</th>
                <th class='campo_minimo tb_number'>Dias</th>
                <th class="campo_minimo_nao_ordenar"></th>
                @if(!Auth::user()->hasRole('Cliente'))
                <th class="campo_minimo_nao_ordenar"></th>
                @endif
                @if(in_array(Auth::id(), [863, 576]) || Auth::id() == 9334 || Auth::user()->tipo_usuario_id == 1)
                <th class="campo_minimo_nao_ordenar"></th>
                <th class="campo_minimo_nao_ordenar"></th>
                <th class="campo_minimo_nao_ordenar"></th>
                @endif
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

        $(document).find("#btn-clearform").on("click", function(){
            table_filters_notas.clear().draw();
            $(document).find(".busca_left").val('').trigger('change');
        });

        $(document).find(".busca_left").on('change', function(event){
            var campos = $(document).find("select:visible");
            var indice = campos.index(event.target) + 1;
            var seletor = $(campos[indice]);
            checkDadosUser(seletor, $(this).val());
        });

        datepicker_options = {
			format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
            endDate: new Date()
        };

        $('#data_inicio').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') < e.date){
                $('#data_fim').val('');
            }
            $('#data_fim').datepicker('setStartDate', e.date);
        });

        $('#data_fim').on('pick.datepicker', function (e) {
            if($('#data_fim').datepicker('getDate') > e.date){
                $('#data_inicio').val('');
            }
            $('#data_inicio').datepicker('setEndDate', e.date);
        });

        $(document).find("#data_inicio").datepicker(datepicker_options);
        $(document).find("#data_inicio").mask("00/00/0000");
        
        $(document).find("#data_fim").datepicker(datepicker_options);
        $(document).find("#data_fim").mask("00/00/0000");
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
            { "class": "number_format", "targets": "valor", type: "numeric-comma" },
            { "class": "number_format", "targets": "tb_number" },
            { "class": "tb_date", targets: "sort-date" },
            { "targets": "campo_minimo", "width": '1px' },
            { "targets": "campo_minimo_nao_ordenar", "orderable": false, "width": '1px' },
        ],
        "order": [[ 1, 'asc' ]]
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
        $(document).find('input').blur();

        $.ajax({
            url: '{{ route("devolucao_nota_aprovacao.filter") }}',
            dataType: 'json',
            method: 'POST',
            data: $(document).find('#form_filter').serialize(),
            success: function(data){

                table_filters_notas.clear().draw();

                var response = data.response.dados;
                var linhas = [];

                for(var field in response){
                    
                    titulo_linha = [
                        @if(!Auth::user()->hasRole('Cliente'))
                        createBtnReprovar(response[field].id, response[field].mostrar_botoes, response[field].status),
                        @endif
                        createBtnLog(response[field].id, response[field].numero),
                        "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+ response[field].estabelecimento + "'>" + response[field].estabelecimento + "</div></div>",
                        "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+ response[field].cliente + "'>" + response[field].cliente + "</div></div>",
                        createLinkNota(response[field].nota_fiscal, response[field].nota_id),
                        response[field].emissao,
                        response[field].valor,
                        response[field].valor_parcial,
                        "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+response[field].motivo + "'>" + response[field].motivo + "</div></div>",
                        response[field].status_exibir,
                        response[field].parado,
                        createBtnVisualizar(response[field].id),
                        @if(!Auth::user()->hasRole('Cliente'))
                        createBtnAprovar(response[field].id, response[field].mostrar_botoes, response[field].status),
                        @endif
                        @if(in_array(Auth::id(), [863, 576]) || Auth::id() == 9334 || Auth::user()->tipo_usuario_id == 1)
                        createBtnCancelar(response[field].id, response[field].botao_cancelar),
                        createBtnFrete(response[field].id, response[field].botao_frete),
                        createBtnEditar(response[field].id)
                        @endif
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

    function createBtnAprovar($id, $mostrar_botoes, $status){

        if($mostrar_botoes === true && ($status < 7 || $status >= 14)){
            var $html = "<a href=\"#\" class=\"bt-aprove\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"aprovar('"+$id+"');\"></a>";
        }
        else{
            var $html = '';
        }
        return $html;
    }

    function aprovar($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota_aprovacao.modal.aprovar') }}',
            success: function(data){
                createModal('aprovar-devolucao-modal', 'Aprovar etapa', data, 'modal-lg');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createBtnReprovar($id, $mostrar_botoes, $status){

        if($mostrar_botoes){
            var $html = "<a href=\"#\" class=\"bt-reprove\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"reprovar('"+$id+"', '"+$status+"');\"></a>";
        }
        else{
            var $html = '';
        }

        return $html;
    }

    function reprovar($id, $status){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota_aprovacao.modal.reprovar') }}',
            success: function(data){
                if($status==4){
                    var classe = 'modal-lg';
                }
                else{
                    var classe = '';
                }
                createModal('reprovar-devolucao-modal', 'Reprovar devolução', data, classe);
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createBtnVisualizar($id){

        var $html = "<a href=\"#\" class=\"bt-view\" data-toggle=\"tooltip\" data-placement=\"top\" onclick=\"visualizar_nota('"+$id+"');\"></a>";

        return $html;
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

    @if ($check_gerentes === true || $check_vendedor_representante === true)
    function checkDadosUser(campo_busca, valor){
        primeira_opcao = $(campo_busca).find("option:first").html();
        $(campo_busca).html("");
        var campos = "<option value=\"\">" + primeira_opcao + "</option>";
        $.ajax({
            url: "{{ route('usuario.dados_subordinados_outros') }}",
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
    @endif

    @if(in_array(Auth::id(), [863, 576])|| Auth::id() == 9334 || Auth::user()->tipo_usuario_id == 1)
    function createBtnCancelar($id, $botao_cancelar){

        if($botao_cancelar == true){
            var $html = "<a href=\"#\" class=\"bt-cancelar\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Cancelar requisição\" onclick=\"cancelar('"+$id+"');\"></a>";
        }
        else{
            var $html = '';
        }

        return $html;
    }

    function cancelar($id, $status){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota_aprovacao.modal.cancelar') }}',
            success: function(data){
                createModal('cancelar-devolucao-modal', 'Cancelar devolução', data, '');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createBtnFrete($id, $botao_frete){

        if($botao_frete == true){
            var $html = "<a href=\"#\" class=\"bt-frete\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Especificar frete\" onclick=\"frete('"+$id+"');\"></a>";
        }
        else{
            var $html = '';
        }

        return $html;
    }

    function frete($id, $status){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota_aprovacao.modal.frete') }}',
            success: function(data){
                createModal('frete-devolucao-modal', 'Especificar frete', data, '');
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    function createBtnEditar($id){

        var $html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Editar/finalizar devolução\" onclick=\"modalEdicao('"+$id+"');\"></a>";

        return $html;
    }

    function modalEdicao($id){
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id
            },
            url: '{{ route('devolucao_nota_aprovacao.modal.editar') }}',
            success: function(data){
                var modal_class = 'modal-lg';
                createModal('editar-devolucao-modal', 'Editar devolução', data, modal_class);
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message)
            }
        });
    }

    @endif
@endsection
