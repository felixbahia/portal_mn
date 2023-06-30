@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">
    <form action="#" name="form_filter_dialog_proforma" id="form_filter_dialog_proforma" onsubmit="return false;">
        @csrf

        <div class="form-row mt-3">
            @if(empty($fornecedor))
                <div class="col-lg-2">
                    {!! Form::select('status', $status, '', ['id' => 'status', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Todos os status']) !!}
                </div>                
                <div class="col-lg-4">
                    <div class="input-group">
                        {{ Form::text('fornecedor_dialog', '', ['id' => 'fornecedor_dialog', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Fornecedor', 'onkeyup' => "optionsFornecedorDialog($(this))"]) }}
                        <span class="input-group-addon border rounded-right" id="bt-search-fornecedor_dialog-busca" data-route="{{ route("fornecedor.busca.index") }}"><i id="bt-view-fornecedor" class="bt-view m-2"></i></span>
                    </div>
                </div>
                <div class="col-lg-2">
                    {!! Form::text('proforma', '', ['id' => 'proforma', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Proforma']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::text('pedido', '', ['id' => 'pedido', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Pedido']) !!}
                </div>
                <div class="col-lg-1">
                    {!! Form::text('data_dialog_inicial', '', ['id' => 'data_dialog_inicial', 'class' => 'form-control pedido-item-form data', 'placeholder' => 'Data Compras Inicial']) !!}
                </div>
                <div class="col-lg-1">
                    {!! Form::text('data_dialog_final', '', ['id' => 'data_dialog_final', 'class' => 'form-control pedido-item-form data', 'placeholder' => 'Data Compras Final']) !!}
                </div>
            @else
                {!! Form::hidden('fornecedor_dialog', $fornecedor, ['id' => 'fornecedor_dialog']) !!}
                <div class="col-lg-2">
                    {!! Form::select('status', $status, '', ['id' => 'status', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Todos os status']) !!}
                </div>                
                <div class="col-lg-3">
                    {!! Form::text('proforma', '', ['id' => 'proforma', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Proforma']) !!}
                </div>
                <div class="col-lg-3">
                    {!! Form::text('pedido', '', ['id' => 'pedido', 'class' => 'form-control pedido-item-form', 'placeholder' => 'Pedido']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::text('data_dialog_inicial', '', ['id' => 'data_dialog_inicial', 'class' => 'form-control pedido-item-form data', 'placeholder' => 'Data Compras Inicial']) !!}
                </div>
                <div class="col-lg-2">
                    {!! Form::text('data_dialog_final', '', ['id' => 'data_dialog_final', 'class' => 'form-control pedido-item-form data', 'placeholder' => 'Data Compras Final']) !!}
                </div>
            @endif
        </div>
        </br>
        <div class="content-buttons">
            <button name="btn-filterform_dialog" id="btn-filterform_dialog" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog" id="table-filters-dialog-proforma">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Fornecedor</th>
                <th class="tb_number">Pedido</th>
                <th>Proforma</th>
                <th class="tb_date">Data Compra</th>
                <th class="tb_date">Previsão Entrega</th>
                <th class="tb_date">Data da Entrega</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script>
    $(document).ready( function () {
        $("#form_filter_dialog_proforma").find("#btn-filterform_dialog").on("click", function(){
            filterAjaxDialog($("#form_filter_dialog_proforma").serialize());
        });
        $('#table-filters-dialog-proforma').find("td").off('mouseenter');
        $('#table-filters-dialog-proforma').find("td").on('mouseenter', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
                $this.attr('data-original-title', $this.text());
            }
        });
        $('[data-toggle="tooltip"]').tooltip();

        table_dialog_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 10,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum registro inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "tb_date" },
                
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_dialog = '';
        table_dialog = $(document).find('#table-filters-dialog-proforma').DataTable(table_dialog_options);
        table_dialog.draw();

        $("#form_filter_dialog_proforma").find("#bt-search-fornecedor_dialog-busca").on("click", function(){
            showModalFornecedorDialog($(this).data("route"), "Lista de Fornecedores");
        });

        $("#form_filter_dialog_proforma").find('.data').datepicker({ 
            format: 'dd/mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        $("#form_filter_dialog_proforma").find('.data').mask('00/00/0000');
    });
    function createBtSelect($this){
        return "<a href=\"#\" data-dados='"+JSON.stringify($this)+"' class=\"bt-selected\"></a>";
    }
    function changeTextOverflow(dados){
        $dados = "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+dados+"\">"+dados+"</div></div>";
        return $dados;
    }
    function filterAjaxDialog(data_form){
        limparMesagemErro();
        var $return;
        table_dialog.clear().draw();
        $.ajax({
            url: "{{ route('importacao.filtro_buscar_proforma') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){
                var fields_filter = [];
                for(var field in data.response){
                    var temp_field = [
                        changeTextOverflow(data.response[field].estabelecimento),
                        changeTextOverflow(data.response[field].fornecedor),
                        data.response[field].pedido,
                        data.response[field].proforma,
                        data.response[field].data_compra,
                        data.response[field].previsão_entrega,
                        data.response[field].data_entrega,
                        changeTextOverflow(data.response[field].status),
                    ];
                    fields_filter.push(temp_field);
                }
                fields_filter = fields_filter;
                table_dialog.rows.add(fields_filter).draw();
            }
            ,
            error: function(callback){
                mensagemErro(callback.responseJSON);
            }

        });
    }

    function mensagemErro(json_error){
        var form_modal = $(document).find("#form_filter_dialog_proforma");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputs(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputs(form_modal, input, message){
        var $input = form_modal.find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function limparMesagemErro(){     
        var form_modal = $("#form_filter_dialog_proforma");
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span').removeClass('error-input');

    }

    function showModalFornecedorDialog(url, title){
        $.ajax({
            url: url,
            method: 'GET',
            success: function(body){
                createModal("fornecedor_search_show", title, body, 'modal-lg');
                $(document).ready( function () {
                    table_dialog.on('draw', function () {
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").off("click");
                        $(document).find("#fornecedor_search_show").find('tbody').find("tr").on("click", function(){
                            returnDadosFornecedorDialog($(this));
                        });
                    });
                });
            }
        });
    }

    function returnDadosFornecedorDialog($this){
        if($this.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#fornecedor_search_show").modal("hide");
        $("#form_filter_dialog_proforma").find("#fornecedor_dialog").val($this.find("td").eq(1).text()+" - "+$this.find("td").eq(3).text());
    }

    function optionsFornecedorDialog($this){
        esconderPopoverTooltip();
        $this.autocomplete(optionsAutoCompleteFornecedorDialog($this));   
    }

    function optionsAutoCompleteFornecedorDialog($this){
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
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_importacao_adicionar').css('z-index')) + 1));
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
</script>
@endsection
