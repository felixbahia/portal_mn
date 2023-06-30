@extends('layouts.page-dialog')

@section('content') 
<div class="container">
    <form id='gravar-nova-foto' action="#" onsubmit="return false;" enctype="multipart/form-data">
        @csrf
        <div class="form-row">
            <div class="col-sm-6">
                <b>Código do Produto</b><br>
                {{ Form::hidden('hash', $produto['hash'], ['id' => 'codigo_produto_modal']) }}
                {{ $produto['codigo_produto'] }}
            </div>
        </div>
        <div class="form-row">
            <div class="col-sm-6">
                <b>Grupo</b><br>
                {{ $produto['grupo'] }}
            </div>
            <div class="col-sm-6">
                <b>Descrição do produto</b><br>
                {{ $produto['descricao'] }}
            </div>
        </div>
        <div class="form-row">    
            <div class="col-sm-6">
                <b>Marca</b><br>
                {{ $produto['marca'] }}
            </div>
            <div class="col-sm-6">
                <b>Linha</b><br>
                {{ $produto['linha'] }}
            </div>
        </div>
        <div class="form-row mt-2">
            <div class="col-sm-12">
                <b>Foto do produto</b><br>
                {!! Form::file('foto', ['class' => 'form-control', 'id'=>'instrucoes_lavagem', 'onchange'=>"this.parentNode.nextSibling.value = this.value" ], null) !!}
            </div>
        </div>
        <div class='form-row mt-2'>
            <div class="col-sm-12">
                {!! $produto['foto'] !!}           
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
                url: '{{ route('produto_foto.editar') }}',
                data: formData,
                type: 'POST',
                processData: false,
                contentType: false
            }).done(function (data){
                $(document).find('#editar-foto-modal').modal('hide');
                filterAjax();
            }).fail( function(data){
                errors = data.responseJSON.errors;

                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            });
        })
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