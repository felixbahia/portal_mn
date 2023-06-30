@extends('layouts.page-dialog')

@section('content') 
<form action="" name="form_filter_itens_edt" class="cadPedido" id="form_filter_itens_edt" onsubmit="return false;">
    @csrf
    <div class="form-row">
        <div class="form-group col-sm-3"> 
            {{ Form::label('grupo', 'Grupo', []) }}
            {{ Form::hidden('id', $dados['id']) }}
            {{ Form::text('grupo', $dados['descricao'], ['id' => 'grupo', 'class' => 'form-control cad-grupo-form', 'placeholder' => 'Grupo', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-3"> 
            {{ Form::label('caracteristicas', 'Características', []) }}
            {{ Form::text('caracteristicas', $dados['caracteristicas'], ['id' => 'caracteristicas', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Características', 'maxlength' => '250']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('tamanho_pecas', 'Tamanho da Peça', []) }}
            {{ Form::text('tamanho_pecas', $dados['tamanho_pecas'], ['id' => 'tamanho_pecas', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Tamanho da Peça', 'maxlength' => '250']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('origem', 'Origem', []) }}
            {{ Form::text('origem', $dados['origem'], ['id' => 'origem', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Origem', 'maxlength' => '250']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('gramatura_gm2', 'Gramatura g/m2', []) }}
            {{ Form::text('gramatura_gm2', $dados['gramatura_gm2'], ['id' => 'gramatura_gm2', 'class' => 'form-control text-right', 'placeholder' => 'Gramatura G/M2', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('gramatura_linear', 'Gramatura Linear', []) }}
            {{ Form::text('gramatura_linear', $dados['gramatura_linear'], ['id' => 'gramatura_linear', 'class' => 'form-control text-right', 'placeholder' => 'Gramatura Linear', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('rendimento', 'Rendimento MT/KG', []) }}
            {{ Form::text('rendimento', $dados['rendimento'], ['id' => 'rendimento', 'class' => 'form-control text-right', 'placeholder' => 'Rendimento', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('encolhimento', 'Encolhimento %', []) }}
            {{ Form::text('encolhimento', $dados['encolhimento'], ['id' => 'encolhimento', 'class' =>'form-control text-right', 'placeholder' => 'Encolhimento', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('titulo_trama', 'Titulo Trama', []) }}
            {{ Form::text('titulo_trama', $dados['titulo_trama'], ['id' => 'titulo_trama', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Titulo Trama', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('titulo_urdume', 'Titulo Urdume', []) }}
            {{ Form::text('titulo_urdume', $dados['titulo_urdume'], ['id' => 'titulo_urdume', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Titulo Urdume', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('ligamento', 'Ligamento', []) }}
            {{ Form::text('ligamento', $dados['ligamento'], ['id' => 'ligamento', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Ligamento', 'maxlength' => '10']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('construcao', 'Construção', []) }}
            {{ Form::text('construcao', $dados['construcao'], ['id' => 'construcao', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Construção', 'maxlength' => '20']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('informacao_adicional', 'Informação Adicional', []) }}
            {{ Form::text('informacao_adicional', $dados['informacao_adicional'], ['id' => 'informacao_adicional', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Informação Adicional', 'maxlength' => '250']) }}
        </div>
        <div class="form-group col-sm-3"> 
            {{ Form::label('largura', 'Largura', []) }}
            {{ Form::text('largura', $dados['largura'], ['id' => 'largura', 'class' => 'form-control cad-laudo-form', 'placeholder' => 'Largura', 'maxlength' => '250']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-row mt-2">
            <div class="col-sm-12">
                <b>Instrução de Lavagem</b><br>
                {!! Form::file('arquivo', ['class' => 'form-control', 'id'=>'arquivo', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) !!}
                {!! $dados['caminho'] !!}    
            </div>
        </div>
        <div class="form-group col-sm-12"> 
            <button type="button" id="btn-salvar" class="btn btn-primary float-right">Salvar</button>
        </div>
    <div class="content-dialog-table">
        <div class="content-table">
            <br>
            <table class="table table-striped table-filter-pedido-itens" id="table-filters-pedidos-itens">
                <thead>
                    <tr>
                        <th class="td_codigo_produto">Código</th>
                        <th>Descrição</th>
                        <th>SubGrupo</th>
                        <th>Marca</th>
                        <th>Linha</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($produtos))
                        @foreach ($produtos as $item)
                        <tr>
                            <td>{{ $item['codigo'] }}</td>
                            <td>{!! $item['descricao'] !!}</td>
                            <td>{!! $item['subgrupo'] !!}</td>
                            <td>{!! $item['marca'] !!}</td>
                            <td>{{ $item['linha'] }}</td>
                        </tr>
                        @endforeach
                        @endif
                </tbody>
            </table>
        </div>
    </div>
</form>
<script>
    table_produtos = '';
    itens = '';
    init();

    $(document).ready(function(){

        $(document).find("#grupo").autocomplete(optionsAutoCompleteGrupo());


        form_modal_add = $(document).find('#form_filter_itens_edt');
        $('#rendimento').maskMoney({thousands:'', decimal:',', precision:2});
        $('#encolhimento').maskMoney({thousands:'', decimal:',', precision:3});
        $('#gramatura_linear').maskMoney({thousands:'', decimal:',', precision:3});

        form_modal_add.find("#btn-salvar").on('click', function(){
            editarDados(form_modal_add.serialize());
        });

    });

    function optionsAutoCompleteGrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_laudo_cadastro_edit_delete').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
                    produtosGrupo();
				}, 100);
			}
		};
	}

    function produtosGrupo(){
        form = $(document).find("#form_filter_itens_edt");
        data_form = form.serialize();
        filterClearProdutos();
        $.ajax({
            url: '{{ route('laudo.produtos.grupo')}}',
            data: data_form,
            method: 'POST',
            success: function(data){
                produtos = [];
                for (var fields in data.response){
                    temp_array = [
                        data.response[fields].codigo_produto,
                        data.response[fields].descricao,
                        data.response[fields].subgrupo,
                        data.response[fields].marca,
                        data.response[fields].linha,
                    ];
                    produtos.push(temp_array)
                }
                table_produtos.rows.add(produtos).draw();            
            }
        });
    }

    function filterClearProdutos(){
        table_produtos.clear().draw();
    }


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
    }

    function editarDados(data_form_modalEdt){
        form_modal_edt = $(document).find('#form_filter_itens_edt');
        data_form_modal_edt = form_modal_edt.serialize();
        var formData = new FormData($(document).find('#form_filter_itens_edt')[0]);
        var retorno = false;
        $.ajax({
            url: "{{ route('laudo.editar') }}", 
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            method: 'POST',
            async: false,
            success: function(callback){
                retorno = true;
                $(form_modal_edt).parents('.modal').modal('hide');
                filterLaudo($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                limparMesagemErroEdt();
                mensagemErroEdt(dados);
            }
        });
        return retorno;
    }

    function limparMesagemErroEdt(){      
        var form_modal_add = $("#form_filter_itens_edt");
        form_modal_add.find('.error-message').remove();
        form_modal_add.find('input, select, span').removeClass('error-input');
    }

    function mensagemErroEdt(json_error){
        var form_modal_add = $("#form_filter_itens_edt");
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsEdt(form_modal_add, field, json_error.error[field]);
            }
        }
    }


    function showErrorsInputsEdt(form_modal, input, message){
      
        if(input.localeCompare('id') == 0){
            var $input = $(form_modal).find("#form_filter_itens_edt");
            $(form_modal).find("input[name='id']").addClass('error-input');
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
        
    }

</script>
@endsection
