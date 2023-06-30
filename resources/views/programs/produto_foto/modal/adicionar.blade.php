@extends('layouts.page-dialog')

@section('content') 
<div class="container">
    <form id='gravar-nova-foto' action="#" onsubmit="return false;" enctype="multipart/form-data">
        @csrf
        <div class="form-row">
            <div class="col-sm-6">
                {{ Form::label('codigo_produto', 'Código do Produto') }}
                <div class="input-group" id="codigo_produto_group">
                    {{ Form::text('codigo_produto', '', ['id' => 'codigo_produto_modal', 'class' => 'input-search-bt form-control pedido-item-form', 'placeholder' => 'Código do Produto', "maxlength" => "250"]) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-produto"><i class="bt-view m-2"></i></span>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="col-sm-6">
                {{ Form::label('grupo', 'Grupo') }}
                {{ Form::text('grupo', '', ['id' => 'grupo_modal', 'class' => 'form-control', 'placeholder' => 'Grupo', 'disabled' => 'disabled']) }}
            </div>
            <div class="col-sm-6">
                {{ Form::label('descricao', 'Descrição do produto') }}
                {{ Form::text('descricao', '', ['id' => 'descricao_modal', 'class' => 'form-control', 'placeholder' => 'Descrição do produto', 'disabled' => 'disabled']) }}
            </div>
        </div>
        <div class="form-row">    
            <div class="col-sm-6">
                {{ Form::label('marca', 'Marca') }}
                {{ Form::text('marca', '', ['id' => 'marca_modal', 'class' => 'form-control', 'placeholder' => 'Marca', 'disabled' => 'disabled']) }}
            </div>
            <div class="col-sm-6">
                    {{ Form::label('linha', 'Linha') }}
                    {{ Form::text('linha', '', ['id' => 'linha_modal' ,'class' => 'form-control', 'placeholder' => 'Linha', 'disabled' => 'disabled']) }}
                </div>
        </div>
        <div class="form-row">
            <div class="col-sm-12 mt-2">
                {{ Form::label('foto', 'Foto do produto') }}
                {!! Form::file('foto', ['class' => 'form-control', 'id'=>'instrucoes_lavagem', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) !!}
            </div>
        </div>
        <div class="form-row">
            <div class="col-sm mt-2">
                {!! Form::submit('Gravar', ['class' => 'btn btn-success float-right']) !!}
            </div>
        </div>
    </form>
</div>

<script>
    $(document).ready( function(){
        $(document).find('#gravar-nova-foto').on('submit', function(){

            form = $(document).find('#gravar-nova-foto');

            var formData = new FormData($(document).find('#gravar-nova-foto')[0]);

            $(document).find('.error-message').remove();
            $(document).find('.error-input').removeClass('error-input');

            $.ajax({
                url: '{{ route('produto_foto.salvar') }}',
                data: formData,
                type: 'POST',
                processData: false,
                contentType: false
            }).done(function (data){
                $(document).find('#nova-foto-modal').modal('hide');
                filterAjax();
            }).fail( function(data){
                errors = data.responseJSON.errors;

                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            });
        });

        $(document).find('#codigo_produto_modal').on('blur', function(){
            $.ajax({
                url: '{{ route('produto.retorna_informacoes_produto') }}',
                data: {
                    _token: '{{ csrf_token() }}',
                    codigo_produto: $(this).val()
                },
                type: 'POST',
                }).done(function (data){
                    console.log(data);
                    $(document).find('#grupo_modal').val(data.grupo);
                    $(document).find('#descricao_modal').val(data.descricao);
                    $(document).find('#marca_modal').val(data.marca);
                    $(document).find('#linha_modal').val(data.linha);
            });
        });
    });

    function showErrorsInputs(form, input, message){
        if(input == 'codigo_produto'){
            var $input = $(form).find("input[name='"+input+"']");
            $input.parent().parent().after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
        else{
            var $input = $(form).find("input[name='"+input+"']");
            $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            $input.addClass('error-input');
        }
    }

    $(document).find("#bt-search-produto").off("click");
    $(document).find("#bt-search-produto").on("click", function(){
        showModalProduto();
    });

	function showModalProduto(){
        $.ajax({
            url: '{{ Route('produto.modal_pesquisa') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(body) {
            	$(document).find('#table-modal-produtos-busca').remove();
                createModal('table-modal-produtos-busca', 'Produto', body, 'modal-lg');
                var modal = $(document).find('#table-modal-produtos-busca');
                $(document).ready( function () {

                	modal.find('#nome_modal_busca').autocomplete('destroy');
   					$(document).find('#codigo_modal_busca').val($(document).find('#produto-codigo-salvo').val());

					if($(document).find('#codigo_modal_busca').length > 0) {
						$(document).find("#btn-filterform-modal").trigger('click');
					}

                    table_filters_produtos_busca.on('draw', function () {
                    	
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(event){
							returnDadosProduto($(this));
                        });

                    });

                });
            },
        });
    }
    
    function returnDadosProduto($dados){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }

        $('#table-modal-produtos-busca').val($dados.find("td").eq(1).text());
        $('#codigo_produto_modal').val($dados.find("td").eq(1).text())
        $('#descricao_modal').val($dados.find("td").eq(2).text());
        $('#grupo_modal').val($dados.find("td").eq(0).text());
        $('#marca_modal').val($dados.find("td").eq(3).text());
        $('#linha_modal').val($dados.find("td").eq(4).text());
        $(document).find('#produto-codigo-salvo').val($(document).find('#codigo_modal_busca').val());
        $(document).find("#table-modal-produtos-busca").modal("hide");
    }

</script>
@endsection