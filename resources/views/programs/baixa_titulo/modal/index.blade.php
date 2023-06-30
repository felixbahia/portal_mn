@extends('layouts.page-dialog')

@section('content')
    <form name="baixar-titulo-form" id="baixar-titulo-form">
        
        @csrf
        {{ Form::hidden('titulo', $titulo['id'], ['id' => 'titulo_id']) }}
        {{ Form::hidden('saldo', $titulo['saldo'], ['id' => 'titulo_saldo']) }}
        
        <div class="border-bottom" id="info-sintese" >
            <div class="row">
                <div class="col-lg-12"><b>Cliente:</b> {{ $titulo['cliente'] }}</div>
            </div>
            <div class="row">
                <div class="col-lg-4"><b>Pedido:</b> 
                    <a href="#" data-route="{{ route('pedidos_orcamentos.modal') }}" data-id="{{ $titulo['pedido_id'] }}" data-modal="modal-lg" data-title_modal="Detalhes do pedido: {{ $titulo['pedido_numero'] }}" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModal(this);">{{ $titulo['pedido_numero'] }}</a></div>
                <div class="col-lg-4"><b>Nota:</b> <a href="#" data-id="{{ $titulo['nota_id'] }}" data-modal="modal-lg" data-title_modal="Detalhes da nota: {{ $titulo['nota_numero'] }}" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNota(this);">{{ $titulo['nota_numero'] }}</a></div>
                <div class="col-lg-4"><b>Valor:</b> {{ $titulo['saldo_formatado'] }}</div>
            </div>
        </div>

        <div class="content-dialog-table" style="overflow: auto">
            <table class="table table-striped table-not-edit table-not-view" id="table-filters-cheques">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Identificação</th>
                        <th class='number_format'>Valor</th>
                        <th class='number_format'>Saldo do Lançamento</th>
                        <th class='date_format'>Bom para</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($titulos as $titulo)
                    <tr>
                        <td>{{ $titulo['tipo'] }}</td>
                        <td>{{ $titulo['identificacao'] }}</td>
                        <td>{{ $titulo['valor'] }}</td>
                        <td>{{ $titulo['saldo'] }}</td>
                        <td>{{ $titulo['bom_para'] }}</td>
                        <td><input type='checkbox' class='cheque' name='cheque[]' data-saldo='{{ $titulo['saldo_float'] }} 'id='cheque_{{ $titulo['id'] }}' value='{{ $titulo['id'] }}'></td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td>Total selecionado:</td>
                        <td id='total'>0,00</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="content-buttons">
            {{ Form::button('Vincular cheques', array('class' => 'btn btn-success float-right mt-3', 'id' => 'btn-enviar-modal')) }}
        </div>
    </form>

    <script>
        $(document).ready(function(){

            $(document).find("#btn-enviar-modal").off();
            $(document).find("#btn-enviar-modal").on('click', function(){
                enviarCheques();
            });

            $(document).find('.cheque').on('click', function(){
                if($(this).is('[readonly]')){
                    return false;
                }
            });

            $(document).find('.cheque').on('keydown', function(e){
                if($(this).is('[readonly]')){
                    return false;
                }
            });

            $(document).find('.cheque').on('change', function(){
                somaTotalCheques()
            });

            $(document).find("#btn-reset").on('click', function(){
                $(document).find('#baixar-titulo-form')[0].reset();
                $(document).find('#pedido').html('');
                $(document).find('#pedido').append("<option selected=\"selected\" value=\"\">Selecione um cliente</option>");
                $(document).find('.error-message').remove();
                $(document).find('.error-input').removeClass('error-input');
                table_filters_cheques.clear().draw();

            });
        });
        
        table_filters_cheques = $('#table-filters-cheques').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            'paging': false,
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
                    $(document).find("#btn-reset").trigger('click');
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

        function somaTotalCheques(){
            var soma = 0

            $(document).find(".cheque:checked").each( function(){

                soma += Number($(this).data('saldo'));

                if(Number($(document).find('#titulo_saldo').val()) <= soma){
                    $(document).find(".cheque").not(":checked").attr('readonly', true);
                }
                else{
                    $(document).find(".cheque").removeAttr('readonly');
                }
            });

            if(soma == 0){
                $(document).find(".cheque").removeAttr('readonly');
            }

            $(document).find('#total').html(String(soma.toFixed(2)).replace(',', '').replace('.', ',')).mask('#.##0,00', {reverse: true});

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

        function enviarCheques(){
            $(document).find('.error-message').remove();
            $(document).find('.error-input').removeClass('error-input');
        
            var form = $(document).find('#baixar-titulo-form');

            $.ajax({
                url: '{{ route("titulos_prepago.baixar.salvar") }}',
                method: 'POST',
                data: form.serialize(),
                success: function(){
                    message('Sucesso!', 'Cheques baixados com sucesso!');
                    $(document).find("#baixa_cheques_modal").modal('hide');
                    returnTitulosAbertos();
                },
                error: function(data){
                    var errors = data.responseJSON.errors;
                    
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }

                    message("Atenção", data.responseJSON.message);
                }
            })
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
    </script>
@endsection