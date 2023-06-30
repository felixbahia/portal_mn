@extends('layouts.page-dialog')

@section('content')
<form action="#" name='form-nova-devolucao' id='form-nova-devolucao' onsubmit="return false;" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('estabelecimento_label', 'Estabelecimento', ['class'=>'input-label']) !!}
            {!! Form::select('estabelecimento', $estabelecimentos, '', ['id' => 'estabelecimento_id', 'class' => 'form-control estabelecimento_class', 'placeholder' => 'Selecione','onchange' => 'limparDocumentos()']) !!}
        </div>
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('nota_modal', 'Número da Nota', ['class'=>'input-label']) !!}
            {!! Form::text('nota_fiscal', '', ['id' => 'nota_modal', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col">
            {!! Form::label('cliente_modal', 'Cliente', ['class'=>'input-label']) !!}
            {!! Form::text('cliente', '', ['id' => 'cliente_modal', 'class' => 'form-control', 'disabled']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            {!! Form::label('valor_modal', 'Valor', ['class'=>'input-label']) !!}
            {!! Form::text('valor', '', ['id' => 'valor_modal', 'class' => 'form-control text-right', 'disabled']) !!}
        </div>
        <div class="col-sm-6">
            {!! Form::label('emissao_modal', 'Data de emissão', ['class'=>'input-label']) !!}
            {!! Form::text('emissao', '', ['id' => 'emissao_modal', 'class' => 'form-control', 'disabled']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('motivo_modal', 'Motivo', ['class'=>'input-label']) !!}
            {!! Form::select('motivo', $motivos, '', ['id' => 'motivo_modal', 'class' => 'form-control', 'placeholder' => 'Selecione']) !!}
        </div>
        <div class="col">
            {!! Form::label('tipo_venda_modal', 'Tipo de venda', ['class'=>'input-label']) !!}
            {!! Form::text('tipo_venda', '', ['id' => 'tipo_venda_modal', 'class' => 'form-control', 'disabled']) !!}
        </div>
        <div class="col-sm-4">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> Tipo de devolução
            <div class='row mt-2 mx-1 rounded'>
                <div class="col">
                    {!! Form::radio('valor_parcial', 1, false, ['id' => 'valor_parcial_modal_sim', 'class'=>'valor_parcial_radio']) !!}
                    {!! Form::label('valor_parcial_modal_sim', 'Parcial', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('valor_parcial', 0, false, ['id' => 'valor_parcial_modal_nao', 'class'=>'valor_parcial_radio']) !!}
                    {!! Form::label('valor_parcial_modal_nao', 'Total', ['class'=>'input-label']) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col"><b>Contato com o cliente:</b></div>
    </div>
    <div class="row">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('nome_contato_modal', 'Nome', ['class'=>'input-label']) !!}
            {!! Form::text('nome_contato', '', ['id' => 'nome_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('telefone_contato_modal', 'Telefone', ['class'=>'input-label']) !!}
            {!! Form::text('telefone_contato', '', ['id' => 'telefone_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('email_contato_modal', 'E-mail', ['class'=>'input-label']) !!}
            {!! Form::text('email_contato', '', ['id' => 'email_contato_modal', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório"></span> {!! Form::label('arquivo_modal', 'Declaração de Isento') !!}
            {!! Form::file('arquivo_cliente_isento', ['id' => 'arquivo_cliente_isento','class' => 'form form-control']) !!}
        </div>
    </div>
    <div class="row defeito d-none">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('arquivo_modal', 'Imagem do produto') !!}
            {!! Form::file('arquivo', ['id' => 'arquivo_modal','class' => 'form form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <strong>Documentos</strong>
        </div>
        <div class="col-md-12">
            <div class="adicionar-elemento">
            </div>
        </div>
        <div class="col-md-12">
            {{ Form::button('Anexar Documento', ['class' => 'btn btn-success', 'id' => 'btn_adicionar_documento']) }}
        </div>
    </div>
    <div class="row">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio defeito d-none'>*</span> {!! Form::label('observacao_modal', 'Observação') !!}
            {!! Form::textarea('observacao', '', ['class' => 'form form-control devolucao-textarea', 'id' => 'observacao_modal', 'col' => '5', 'rows' => '4', 'maxlength' => '254']) !!}
        </div>
    </div>
</form>

<div class="row d-none valor-div">
    <div class="content-dialog-table">
        <table class="table table-striped table-filter-dialog" id="table-filters-dialog-produtos">
            <thead>
                <tr>
                    <th></th>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th class="tb_number">Quantidade</th>
                    <th><span data-toggle="tooltip" data-placement="top" title="Campo obrigatório se selecionado" class='campo_obrigatorio'>*</span> Devolvida</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>        
</div>

<div class="row mt-2">
    <div class="col-sm text-right d-none" id='enviar-div'>
        {!! Form::button('Enviar', ['id' => 'btn_enviar', 'class' => 'btn btn-success', 'form' => 'form-nova-devolucao']) !!}
    </div>
</div>

<script>
    $(document).ready(function(){
        $(document).find('.valor_parcial_radio').on('click', function(){
            if($('.valor_parcial_radio:checked').val() == 1){
                $(document).find('.valor-div').removeClass('d-none');
            }
            else{
                $(document).find('.valor-div').addClass('d-none');
            }
        })
        
        $(document).find('#estabelecimento_id, #nota_modal').on('change', function(){
            recuperaNota();
        });

        $(document).find('#btn_enviar').on('click', function(){
            enviaNota();
        })

        $(document).find('#valor_devolvido_modal').maskMoney({thousands:'.', decimal:','});

        var options_telefone =  {
            onKeyPress: function(telefone, e, field, options_telefone) {
                var masks = ['(00) 0000-00009', '(00) 00000-0000'];
                var mask = (telefone.length>14) ? masks[1] : masks[0];
                $(document).find('#telefone_contato_modal').mask(mask, options_telefone);
            }
        };
        
        $(document).find("#telefone_contato_modal").mask('(00) 00000-0000', options_telefone);

        $(document).find('#motivo_modal').on('change', function(){
            if($(this).val() == 1){
                $(document).find('.defeito').removeClass('d-none');
            }
            else{
                $(document).find('.defeito').addClass('d-none');
            }
        });

		var x = 1;
		var max_fields = 20;

        $('#btn_adicionar_documento').click (function(e){
			e.preventDefault(); 

			if (x < max_fields)
			{
                var anterior = 0;

                var conteudo = 
                '<div id="adicionar-documento-div-'+x+'" class="remove'+x+'">'+
                    '<div class="row border border-dark rounded m-1">'+
                        '<div class="form-group col-md-3">'+
                            '{{ Form::label("descricao_documento_label", "Descrição") }}'+
                            '<input type="text" id="descricao_documento['+x+']" name="descricao_documento['+x+']" class="form-control campo_descricao'+x+'"  placeholder="Escreva o Documento" maxlength="30">'+
                        '</div>'+
                        '<div class="form-group col-md-3">'+
                            '{{ Form::label("label_documento", "Anexo") }}'+
                            '<input type="file" id="documento['+x+']" name="documento['+x+']" class="form-control campo_arquivo'+x+'">'+
                        '</div>'+
                        '<div class="form-group col-lg-2">'+
                            '<br>'+
                            '<input type="button" id="remove'+x+'" class="form-group btn btn-danger remove_documento" value="Remover">'+
                        '</div>'+
                    '</div>'+
                '</div>';

				if(x > 1){
                    anterior = x - 1;
                    for(var i = 1; i < x; i++){
                        var descricao = $(".campo_descricao"+i).val();
                        var documento = $(".campo_arquivo"+i).val();
                        
                        if(descricao == '' || documento == ''){
                            message("Atenção", "Adicione documentos para adicionar mais campos!");
                            return false;
                        }
                    }
                    $(document).find("#remove"+anterior).hide();
                }
				
				$('.adicionar-elemento').append( conteudo );
				x++;
			}else{
				message('Alerta','Limite Máximo de Documentos Atingido');
			}
		});

		$('.adicionar-elemento').on("click",".remove_documento",function(e) {
			e.preventDefault();
			var anterior = x - 2;
            $(document).find("#remove"+anterior).show();
            if(x > 1){
                x --; 
            }
			var id = $(this).attr('id');
			$('.'+ id).remove();
		});
        
    });

    table_filters_dialog_produtos = $(document).find('#table-filters-dialog-produtos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "paging": false,
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
            { "targets": [0,-1], "orderable": false, "width": '1%'},
            { "targets": 3, "width": '30%' },
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", "width": '1%'},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    });

    function limparDocumentos(){
        $(document).find('.adicionar-elemento').html('');
    }

    function recuperaNota(){

        var form = $(document).find('#form-nova-devolucao');
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        table_filters_dialog_produtos.clear().draw();

        if($(document).find('#estabelecimento_id').val().trim() != '' && $(document).find('#nota_modal').val().trim() != ''){
            $.ajax({
                url: '{{ route("devolucao_nota.recupera_nota") }}',
                dataType: 'json',
                method: 'POST',
                data: $(document).find('#form-nova-devolucao').serialize(),
                success: function(data){
                    $(document).find('#cliente_modal').val(data.response.cliente);
                    $(document).find('#valor_modal').val(data.response.valor);
                    $(document).find('#emissao_modal').val(data.response.emissao);
                    $(document).find('#tipo_venda_modal').val(data.response.tipo_venda);
                    
                    $(document).find('#enviar-div').removeClass('d-none');

                    var dados = data.response.itens;
                    var lines = [];

                    for(var field in dados){
                        var linha;

                        linha = [
                            createCheckbox(dados[field].id),
                            dados[field].codigo_produto,
                            dados[field].descricao,
                            dados[field].quantidade_comprada,
                            createCampo(dados[field].id),
                        ];

                        lines.push(linha);    
                    }

                    table_filters_dialog_produtos.rows.add(lines).draw();

                    $(document)
                        .find('#table-filters-dialog-produtos')
                        .find('.checkbox_produto').on('click', function(){
                            habilitarCampo($(this));
                        });

                    $(document)
                        .find('#table-filters-dialog-produtos')
                        .find('.valor_parcial')
                        .maskMoney({thousands:'.', decimal:','});

                },
                error: function(callback){
                    errors = callback.responseJSON.error;

                    for(var field in errors){
                        showErrorsInputsModalNovo(form, field, errors[field]);
                    }

                    $(document).find('#enviar-div').addClass('d-none');
                    $(document).find('#cliente_modal').val('');
                    $(document).find('#valor_modal').val('');
                    $(document).find('#emissao_modal').val('');
                }
            });
        }
        else{
            $(document).find('#enviar-div').addClass('d-none');
            $(document).find('#cliente_modal').val('');
            $(document).find('#valor_modal').val('');
            $(document).find('#emissao_modal').val('');
        }
    }

    function enviaNota(){

        var form = $(document).find('#form-nova-devolucao');
        var dados = new FormData(form[0]);

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        if(form.find('#arquivo_modal')[0].files[0] != undefined && form.find('#arquivo_modal')[0].files[0].size > 2097152){
            showErrorsInputsModalNovo(form, 'arquivo', 'A imagem deve ser menor que 2MB');
            return;
        }

        $(document)
            .find("#table-filters-dialog-produtos")
            .find('.checkbox_produto:checked')
            .each(function(i, e){

                var id = $(this).val();
                var valor = $(this).parent().parent().find('.valor_parcial').val();

                dados.append('produtos['+i+'][produto]', id);
                dados.append('produtos['+i+'][quantidade_devolvida]', valor);
            });

        $.ajax({
            url: '{{ route("devolucao_nota.salvar.novo") }}',
            method: 'POST',
            data: dados,
            processData: false,
            contentType: false,
            success: function(data){
                message('Atenção', data.message)
                $(document).find('#nova-devolucao-modal').modal('hide');
                buscarNotas();
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputsModalNovo(form, field, errors[field]);
                }
            }
        })
    }

    function showErrorsInputsModalNovo(form, input, message){
        var inputexplode = input.split(".");
		if(inputexplode.length > 1){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
			message = message;
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
		}else if(input.match(/produtos/i) != null){
            $input = $(document).find(".valor_parcial:not([disabled])").eq(input.split('.')[1]);
        }
        else if(input == 'valor_parcial'){
            $input = form.find(".valor_parcial_radio").parent().parent();
        }else{
            var $input = form.find("input[name='"+input+"'], select[name='"+input+"'], textarea[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function createCheckbox($id){
        var html = "<input type=\"checkbox\" name=\"produtos[]\" value=\"" + $id + "\" class='checkbox_produto''>";

        return html;
    }

    function createCampo($id){
        var html = "<input type=\"text\" name=\"valor_" + $id + "\" class=\"valor_parcial text-right form-control\" disabled>";

        return html;
    }

    function habilitarCampo($elemento){

        if($elemento.prop('checked') == true){
            $(document).find('[name=valor_'+$elemento.val()+']').prop('disabled', false);
        }
        else{
            $(document).find('[name=valor_'+$elemento.val()+']').prop('disabled', true);
        }
    }
</script>
@endsection