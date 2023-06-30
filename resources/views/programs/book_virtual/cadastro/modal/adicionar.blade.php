@extends('layouts.page-dialog')

@section('content') 
<form action="" name="form_filter_itens" class="cadPedido" id="form_filter_itens" onsubmit="return false;">
    @csrf
    {!! Form::hidden('itens', '',['id' => 'itens']) !!}
    <div class="form-row">
        <div class="form-group col-sm-4"> 
            {{ Form::label('book_virtual', 'Book Virtual', []) }}
            {{ Form::text('book_virtual', '', ['id' => 'book_virtual', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Book Virtual', 'maxlength' => '250']) }}
        </div>
        <div class="form-group col-sm-4">
            {{ Form::label('padrao_codigo', 'Padrão do código') }}
            {{ Form::select('padrao_codigo', $padroes_codigo, '', ['id' => 'padrao_codigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => '']) }}
        </div>
        <div class="form-group col-sm-4"> 
            {{ Form::label('artigo', 'Artigo', []) }}
            {{ Form::text('artigo', '', ['id' => 'artigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Artigo', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('nome_artigo', 'Nome do Artigo', []) }}
            {{ Form::text('nome_artigo', '', ['id' => 'nome_artigo', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Nome do Artigo', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6"> 
            {{ Form::label('pecas', 'Peças de', []) }}
            {{ Form::text('pecas', '', ['id' => 'pecas', 'class' => 'form-control cad-book_virtual-form', 'placeholder' => 'Peças de', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="col-sm-12 mt-5" id="button-bottom">
            <button type="button" id="btn-salvar" class="btn btn-primary float-right">Salvar</button>
        </div>
    </div>
</form>
<script>
    table_produtos = '';
    itens = '';
    init();

    function init(){
        table_filters_produtos_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "35vh",
            "autoWidth": false,
            "drawCallback": function(settings) {
                $(document).find('.estoque, .preco, #total_pedido').popover({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'manual'
                });
            },
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum produto inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum  produto inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                {
                    'targets': 'td_foto',
                    'width': '150px',
                    'height': '150px'
                },
                {
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                },
                {
                    'targets': ['td_quantidade', 'td_preco', 'td_total'],
                    'width': '120px'
                },
                {
                    'targets': ['td_comissao', 'td_coluna'],
                    'width': '50px'
                },
                {
                    'targets': 'td_codigo_produto',
                    'width': '100px'
                },
                
            ],
            "order": [[ 1, 'asc' ]]
        };
        table_produtos = '';
        table_produtos = $(document).find('#table-filters-pedidos-itens').DataTable(table_filters_produtos_options);
        table_produtos.draw();
        $('[data-toggle="popover"]').off('show.bs.popover');
        $('[data-toggle="popover"]').popover('hide');

        $('[data-toggle="popover"]').popover({
            container: 'body',
            html: true,
            show: true,
            template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
        });

        $(document).find('#btn-novo-desenho').on('click', function(){
            var elemento = $(document).find('#modelo-desenhos')
                .clone()
                .appendTo("#desenhos");

            elemento.removeClass("d-none")
                .removeAttr('id').find('.delete-foto').on('click', function(){
                    $(this)
                        .parent()
                        .parent()
                        .remove();
                });

            elemento.find('.codigo_desenho, .imagem')
                .removeAttr('disabled');

        });
    }

    $(document).ready( function () {
        form_modal_add = $(document).find('#form_filter_itens');

        form_modal_add.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal_add.serialize());
        });
    });

    function inserirDados(data_form_modal_add){

        limparMesagemErroAdd();

        form_modal_add = $(document).find('#form_filter_itens');

        var erro_imagem = 0;
        var erro = 0;

        var formData = new FormData($(document).find('#form_filter_itens')[0]);
        var retorno = false;
        $.ajax({
            url: "{{ route('book_virtual.cadastro.adicionar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                retorno = true;
                
                var url = "{{ route('book_virtual.cadastro.modal.editar') }}";
                var title="Editar Book Virtual";
                var modal_class='modal-lg';
                var id = callback.response.id;
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {_token: "{{ csrf_token() }}", id: id},
                    success: function(body){
                        createModal('modal_book_virtual_cadastro_edit_delete', title, body,modal_class);
                        var modal = $("#modal_book_virtual_cadastro_edit_delete");
                    }
                });

                $(form_modal_add).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                mensagemErroAdd(dados);
            }
        });
        return retorno;
    }

    function limparMesagemErroAdd(){      
        var form_modal_add = $("#form_filter_itens");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroAdd(json_error){
        var form_modal_add = $("#form_filter_itens");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsAdd(form_modal_add, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsAdd(form_modal_add, input, message){
        var $input = $(form_modal_add).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

</script>
@endsection
