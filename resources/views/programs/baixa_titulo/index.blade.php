@extends('layouts.app')

@section('content-filter')
    <form action="#" id='form-busca-cliente'>
        @csrf
        <div class="content-fields">
            <div class="form-group col-sm-4"> 
                <div class="input-group">
                    {{ Form::text('cliente_nome_cpf_cnpj', '', ['id' => 'cliente_nome_cpf_cnpj', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="form-group col-sm-4 d-none"> 
                <b>Total de saldo dos títulos:</b> <span id='total_saldo_titulos'></span>
            </div>
            <div class="form-group col-sm-4 d-none"> 
                <b>Total de saldo dos cheques:</b> <span id='total_saldo_cheques'></span>
            </div>
        </div>
        <div class="content-buttons">
            {{ Form::button('Buscar', array('class' => 'btn btn-filter', 'id' => 'btn-enviar')) }}
            {{ Form::button('Limpar Busca', array('class' => 'btn btn-clear', 'id' => 'btn-reset')) }}
        </div>
    </form>

@endsection

@section('content')
    <div class="content-table">
        <table class="table table-striped table-not-edit table-not-view" id="table-filters-titulos">
            <thead>
                <tr>
                    <th class='number_format'>Pedido</th>
                    <th class='number_format'>Nota</th>
                    <th class='date_format'>Emissão</th>
                    <th class='number_format'>Valor</th>
                    <th class='number_format'>Saldo</th>
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
        $(document).find('#cliente_nome_cpf_cnpj').autocomplete(optionsAutoCompleteCliente('cliente_nome_cpf_cnpj'));

        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteBusca($(this).data("route"));
            return false;
        });

        $(document).find("#btn-enviar").on('click', function(){
            returnTitulosAbertos();
        });

        $(document).find("#btn-reset").on('click', function(){
            $(document).find('#cliente_nome_cpf_cnpj').val('');
            $(document).find('.error-message').remove();
            $(document).find('.error-input').removeClass('error-input');
            $(document).find('#total_saldo_titulos').parent().addClass('d-none');
            $(document).find('#total_saldo_cheques').parent().addClass('d-none');
            table_filters_titulos.clear().draw();

        });
    });
    
    table_filters_titulos = $('#table-filters-titulos').DataTable({
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
        "columnDefs": [
            {
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                "targets": 'date_format',
                "className": 'date_format',
            },
            {
                "targets": -1,
                "orderable": false,
                "width": '1px'
            }
        ]
    });

    function optionsAutoCompleteCliente($elemento){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", $("#" + $elemento).parents('.modal').css('z-index') + 1);
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhum cliente encontrado');
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#" + $elemento).val(ui.item.label);
                return false;
            }
        };
    }

    function showModalClienteBusca(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}'
            },
            success: function(body){
                $(document).find('#cliente_searsh_show').remove();
                createModal("cliente_searsh_show", title, body, 'modal-lg');
                var modal = $(document).find("#cliente_searsh_show");
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
                            returnDadosClienteBusca($(this), event);
                        });
                    });
                });
            }
        });
    }

    function returnDadosClienteBusca($dados, event){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#cliente_nome_cpf_cnpj").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
    }

    function returnTitulosAbertos(){

        form = $(document).find("#form-busca-cliente");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route('titulos_prepago.baixar.titulos') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                cliente: $(document).find("#cliente_nome_cpf_cnpj").val()
            },
            success: function(callback){

                table_filters_titulos.clear().draw();
 
                $(document).find('#total_saldo_titulos').parent().removeClass('d-none');
                $(document).find('#total_saldo_cheques').parent().removeClass('d-none');
                $(document).find('#total_saldo_titulos').html(callback.response.total_saldo_titulos);
                $(document).find('#total_saldo_cheques').html(callback.response.total_saldo_cheques);

                if(callback.response.pedidos.length > 0){


                    data = callback.response.pedidos;

                    var fields_filter = [];

                    for(var field in data){
                        var temp_field = [
                            createLinkPedidoNasajon(data[field].pedido_numero, data[field].pedido_id),
                            createLinkNota(data[field].nota_numero, data[field].nota_id),
                            data[field].emissao,
                            data[field].valor,
                            data[field].saldo,
                            createBtnModal(data[field].id)
                        ];
                        fields_filter.push(temp_field);
                    }

                    table_filters_titulos.rows.add(fields_filter).draw();

                }
            },
            error: function(data){

                $(document).find('#total_saldo_titulos').parent().addClass('d-none');
                $(document).find('#total_saldo_cheques').parent().addClass('d-none');
                $(document).find('#total_saldo_titulos').html('');
                $(document).find('#total_saldo_cheques').html('');


                var errors = data.responseJSON.errors;                
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        });
    }

    function createBtnModal($id){
        var $html = "<a href=\"#\" class=\"bt-edit-money\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Vincular lançamentos a este pedido\" onclick=\"modalCheques('"+$id+"')\"'></a>";

        return $html;
    }

    function modalCheques($id){
        $.ajax({
            url: "{{ route('titulos_prepago.baixar.modal') }}",
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id
            },
            success: function(callback){
                createModal("baixa_cheques_modal", 'Baixa de lançamentos no título', callback, 'modal-lg');
            }
        });
    }

    function showErrorsInputs(form, input, message){
        
        if(input == 'cliente_nome_cpf_cnpj'){
            form.find("input[name='cliente_nome_cpf_cnpj']").parent().after("<label class='error-message' for='cliente_nome_cpf_cnpj'>"+message+"</label>");
            form.find("input[name='cliente_nome_cpf_cnpj']").addClass('error-input');
        }
        else{
            if(input == 'cheques'){
                var $input = form.find("#total");       
            }
            else if(input.substr(0,7) == 'cheque.'){
                var $input = form.find("input[name='cheque[]']:checked").eq(input.substr(7));
            }
            else{
                var $input = form.find("input[name='"+input+"'], select[name='"+input+"']");
            }
            $input.addClass('error-input');
        }

    }

    function createLinkPedidoNasajon($numero, $id){
        var url = '{{ route('pedidos_orcamentos.modal') }}';
        var title_modal = 'Detalhes do pedido: ' + $numero;

        var html = "<a href=\"#\" data-route=\""+url+"\" data-id=\""+$id+"\" data-modal=\"modal-lg\" data-title_modal=\""+title_modal+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModal(this);\">"+$numero+"</a>";

        return html;
    }

    function createLinkNota($numero, $nota){
        var title_modal = 'Detalhes da nota: ' + $numero;

        if($numero.length > 0 && $nota.length > 0){
            var html = "<a href=\"#\" data-id=\""+$nota+"\" data-modal=\"modal-lg\" data-title_modal=\""+title_modal+"\" data-toggle=\"tooltip\" data-placement=\"top\" title=\"Detalhes no Nasajon\" onclick=\"showModalNota(this);\">"+$numero+"</a>";
        }
        else{
            html = "Não emitida";
        }

        return html;
    }

    function showModal($this){
        var url = $($this).data("route");
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }

    function showModalNota($this){
        var url = '{{ route('notas_nasajon.modal.exibir') }}';
        var $id = $($this).data("id");
        var modal_class = $($this).data("modal");
        var title = $($this).data("title_modal");
        $.ajax({
            url: url,
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_nota: $id},
            success: function(body){
                createModal('modal_message_edit', title, body, modal_class);
            }
        });
    }
@endsection