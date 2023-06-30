@extends('layouts.page-dialog')

@section('content')
@if (!empty($motivo_reprovacao))
<div class="alert alert-danger" role="alert">
	<p>Este pedido de devolução foi rejeitado.<br>
	Motivo: {{$motivo_reprovacao}}</p> 
</div>
@endif
<form action="#" id='form-editar-devolucao' onsubmit="return false;" enctype="multipart/form-data">
    @csrf
    {!! Form::hidden('id', $id, ['id' => 'id_modal']) !!}
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('estabelecimento_modal', 'Estabelecimento', ['class'=>'input-label']) !!}
            {!! Form::select('estabelecimento', $estabelecimentos, $estabelecimento, ['id' => 'estabelecimento_modal', 'class' => 'form-control estabelecimento_class', 'placeholder' => 'Selecione','onchange' => 'limparDocumentos()']) !!}
        </div>
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('nota_modal', 'Número da Nota', ['class'=>'input-label']) !!}
            {!! Form::text('nota_fiscal', $nota_fiscal, ['id' => 'nota_modal', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col">
            {!! Form::label('cliente_modal', 'Cliente', ['class'=>'input-label']) !!}
            {!! Form::text('cliente', $cliente, ['id' => 'cliente_modal', 'class' => 'form-control', 'disabled']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            {!! Form::label('valor_modal', 'Valor', ['class'=>'input-label']) !!}
            {!! Form::text('valor', $valor, ['id' => 'valor_modal', 'class' => 'form-control text-right', 'disabled']) !!}
        </div>
        <div class="col-sm-6">
            {!! Form::label('emissao_modal', 'Data de emissão', ['class'=>'input-label']) !!}
            {!! Form::text('emissao', $emissao, ['id' => 'emissao_modal', 'class' => 'form-control', 'disabled']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('motivo_modal', 'Motivo', ['class'=>'input-label']) !!}
            {!! Form::select('motivo', $motivos, $motivo, ['id' => 'motivo_modal', 'class' => 'form-control', 'placeholder' => 'Selecione']) !!}
        </div>
        <div class="col">
            {!! Form::label('tipo_venda_modal', 'Tipo de venda', ['class'=>'input-label']) !!}
            {!! Form::text('tipo_venda', $tipo_venda, ['id' => 'tipo_venda_modal', 'class' => 'form-control', 'disabled']) !!}
        </div>
        <div class="col-sm-4">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> Tipo de devolução
            <div class='row mt-2'>
                <div class="col">
                    {!! Form::radio('valor_parcial', 1, $valor_parcial, ['id' => 'valor_parcial_modal_sim', 'class'=>'valor_parcial_radio']) !!}
                    {!! Form::label('valor_parcial_modal_sim', 'Parcial', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('valor_parcial', 0, !$valor_parcial, ['id' => 'valor_parcial_modal_nao', 'class'=>'valor_parcial_radio']) !!}
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
            {!! Form::text('nome_contato', $nome_contato, ['id' => 'nome_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('telefone_contato_modal', 'Telefone', ['class'=>'input-label']) !!}
            {!! Form::text('telefone_contato', $telefone_contato, ['id' => 'telefone_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio'>*</span> {!! Form::label('email_contato_modal', 'E-mail', ['class'=>'input-label']) !!}
            {!! Form::text('email_contato', $email_contato, ['id' => 'email_contato_modal', 'class' => 'form-control']) !!}
        </div>
    </div>
    
    <div class="row defeito @if($motivo!=1)d-none @endif">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio @if(isset($arquivo))d-none @endif'>*</span> {!! Form::label('arquivo_modal', 'Imagem do produto') !!}
            {!! Form::file('arquivo', ['id' => 'arquivo_modal','class' => 'form form-control']) !!}
        </div>
    </div>
    <div class="row mt-2">
        <div class="col">
            Documentos:
        </div>
    </div>
    <div>
        @if(!empty($documentos_diversos))
            @foreach ($documentos_diversos as $key => $documentos_diversos)
                <div class="row m-2 col-3 diversos{{ $key }}">
                    <div class="col-md-9 m-1">
                        <a href="{{  $documentos_diversos['caminho'] }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i>{{ $documentos_diversos['descricao'] }}</a> 
                    </div>
                    <div class="col-lg-2 m-1 float-right pr-2">
                        <input type="button" onclick="removerDocumentoDiversos('{{ $documentos_diversos['id_diversos'] }}','{{$key}}')" class="form-group btn btn-danger" value="Remover">
                    </div>
                </div>
            @endforeach
        @endif
    </div>
    <div class="row">
        <div class="form-group col-md-3">
            {{ Form::button('Adicionar Documento', ['class' => 'btn btn-success', 'id' => 'btn_adicionar_documento']) }}
        </div>
    </div>
    <div class="adicionar-elemento">
    </div>

    @if(!empty($documento_isento))
        <div class="row mt-2">
            <div class="col">
                <label for="arquivo_modal">Declaração de Isento</label>
            </div>
        </div>
        <div class="row mb-2 div_isento">
            <div class="col-sm-2">
                <a href="{{  $documento_isento['caminho'] }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i> Documento Cliente Isento </a>         
                {!! Form::hidden('id_isento', $documento_isento['id_isento'] , ['id' => 'id_isento']) !!}
            </div>
            <div class="col-lg-2">
                <input type="button" id="remove_isento" class="form-group btn btn-danger remove_documento" value="Remover">
            </div>
        </div>
    @else
    <div class="row mt-2">
        <div class="col">
            <label for="arquivo_modal">Declaração de Isento</label>
        </div>
    </div>
    <div class="row mb-2 div_isento">
        <div class="col-sm-6"> 
            <input id="arquivo_cliente_isento" class="form form-control" name="arquivo_cliente_isento" type="file" autocomplete="off">
        </div>
    </div>

    @endif
    @if(isset($arquivo))
    <div class="row">
        <div class="col">
            <a href="{{ $arquivo }}" target="_blank"><b>Imagem Vinculada</b></a>
        </div>
    </div>
    @endif
    <div class="row">
        <div class="col">
            <span data-toggle="tooltip" data-placement="top" title="Campo obrigatório" class='campo_obrigatorio defeito @if($motivo!=1)d-none @endif'>*</span> {!! Form::label('observacao_modal', 'Observação') !!}
            {!! Form::textarea('observacao', $observacao, ['class' => 'form form-control devolucao-textarea', 'id' => 'observacao_modal', 'col' => '5', 'rows' => '4', 'maxlength' => '254']) !!}
        </div>
    </div>

    <div class="row @if(!$valor_parcial)d-none @endif valor-div">
        <div class="content-dialog-table">
            <table class="table table-striped table-filter-dialog" id="table-filters-dialog-produtos">
                <thead>
                    <tr>
                        <th></th>
                        <th>Código</th>
                        <th>Grupo</th>
                        <th>Descrição</th>
                        <th class="tb_number">Quantidade</th>
                        <th><span data-toggle="tooltip" data-placement="top" title="Campo obrigatório se selecionado" class='campo_obrigatorio'>*</span> Devolvida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produtos as $produto)
                    <tr>
                        <td><input type="checkbox" name="produtos[]" value="{{ $produto['id'] }}" class='checkbox_produto' @if(!empty($produto['quantidade_devolvida'])) checked @endif></td>
                        <td>{{ $produto['codigo'] }}</td>
                        <td>{{ $produto['grupo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
                        <td>{{ $produto['quantidade'] }}</td>
                        <td><input type="text" name="valor_{{ $produto['id'] }}" class="valor_parcial text-right form-control" @if(empty($produto['quantidade_devolvida'])) disabled @endif value='{{ $produto['quantidade_devolvida'] }}'></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>        
    </div>

    <div class="row mt-2">
        @if(isset($laudo_tecnico) && !empty($laudo_tecnico))
        <div class="col-sm-4">
            <p> 
                <a href="{!! $laudo_tecnico !!}" target='_blank'><i class='btn-nota-pdf'></i><b>Laudo técnico</b></a>   
            </p>
        </div>
        @endif
        <div class="col-sm text-right" id='enviar-div'>
            {!! Form::button('Enviar', ['id' => 'btn_enviar', 'class' => 'btn btn-success']) !!}
        </div>
    </div>
</form>

<script>
    $(document).ready(function(){
        $(document).find('.valor_parcial_radio').on('click', function(){
            if($(this).val() == 1){
                $(document).find('.valor-div').removeClass('d-none');
            }
            else{
                $(document).find('.valor-div').addClass('d-none');
            }
        });

        $(document).find('').on('click', function(){
            removerDocumentoIsento();
        });

        $(document).find('#estabelecimento_modal, #nota_modal').on('change', function(){
            recuperaNota();
        });

        $(document).find('#btn_enviar').on('click', function(){
            enviaNota();
        });

        $(document).find('#valor_devolvido_modal').maskMoney({thousands:'.', decimal:','});

        var options_telefone =  {
            onKeyPress: function(telefone, e, field, options_telefone) {
                var masks = ['(00) 0000-00009', '(00) 00000-0000'];
                var mask = (telefone.length>14) ? masks[1] : masks[0];
                $(document).find('#telefone_contato_modal').mask(mask, options_telefone);
            }
        };
        
        @if(strlen($telefone_contato) <= 14)
            $(document).find("#telefone_contato_modal").mask('(00) 0000-0000', options_telefone);
        @else
            $(document).find("#telefone_contato_modal").mask('(00) 00000-0000', options_telefone);
        @endif

        $(document)
            .find('#table-filters-dialog-produtos')
            .find('.checkbox_produto').on('click', function(){
                habilitarCampo($(this));
            });

        $(document)
            .find('#table-filters-dialog-produtos')
            .find('.valor_parcial')
            .maskMoney({thousands:'.', decimal:','});

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

            $estabelecimento = $(document).find('.estabelecimento_class option:selected').val();

            if($estabelecimento == ''){
                message('Atenção','Selecione o estabelecimento');
                return false;
            }
			if (x < max_fields)
			{
                var anterior = 0;
                
                if($estabelecimento == 3 || $estabelecimento == 4){
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
                            '<div class="form-check col-lg-2 mt-4">'+
                                '<input type="radio" class="form-check-input" name="nf_remessa['+x+']" id="nf_remessa" value="nf_remessa" checked/>'+
                                '<label class="form-check-label" for="nf_remessa">NF de Remessa</label>'+
                            '</div>'+
                            '<div class="form-check col-lg-2 mt-4">'+
                                '<input type="radio" class="form-check-input" name="nf_remessa['+x+']" id="carta_correcao_remessa" value="carta_correcao_remessa"/>'+
                                '<label class="form-check-label" for="carta_correcao_remessa">Carta de Correção</label>'+
                            '</div>'+
                            '<div class="form-group col-lg-2">'+
                                '<br>'+
                                '<input type="button" id="remove'+x+'" class="form-group btn btn-danger remove_documento" value="Remover">'+
                            '</div>'+
                        '</div>'+
                    '</div>';
                }else{
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
                            '<div class="form-check col-lg-2 mt-4">'+
                                '<input type="radio" class="form-check-input" name="nf_devolucao['+x+']" id="nf_devolucao" value="nf_devolucao" checked/>'+
                                '<label class="form-check-label" for="nf_devolucao">NF de Devolução</label>'+
                            '</div>'+
                            '<div class="form-check col-lg-2 mt-4">'+
                                '<input type="radio" class="form-check-input" name="nf_devolucao['+x+']" id="carta_correcao_devolucao" value="carta_correcao_devolucao"/>'+
                                '<label class="form-check-label" for="carta_correcao_devolucao">Carta de Correção</label>'+
                            '</div>'+
                            '<div class="form-group col-lg-2">'+
                                '<br>'+
                                '<input type="button" id="remove'+x+'" class="form-group btn btn-danger remove_documento" value="Remover">'+
                            '</div>'+
                        '</div>'+
                    '</div>';

                }

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

    function limparDocumentos(){
        $(document).find('.adicionar-elemento').html('');
    }

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

    function recuperaNota(){

        var form = $(document).find('#form-editar-devolucao');
        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        table_filters_dialog_produtos.clear().draw();

        if($(document).find('#estabelecimento_modal').val().trim() != '' && $(document).find('#nota_modal').val().trim() != ''){
            $.ajax({
                url: '{{ route("devolucao_nota.recupera_nota") }}',
                dataType: 'json',
                method: 'POST',
                data: $(document).find('#form-editar-devolucao').serialize(),
                success: function(data){
                    $(document).find('#cliente_modal').val(data.response.cliente);
                    $(document).find('#valor_modal').val(data.response.valor);
                    $(document).find('#emissao_modal').val(data.response.emissao);

                    $(document).find('#enviar-div').removeClass('d-none');

                    var dados = data.response.itens;
                    var lines = [];

                    for(var field in dados){
                        var linha;

                        linha = [
                            createCheckbox(dados[field].id),
                            dados[field].codigo_produto,
                            dados[field].grupo,
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
                        showErrorsInputsModalEditar(form, field, errors[field]);
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

        var form = $(document).find('#form-editar-devolucao');
        var dados = new FormData(form[0]);

        $(document)
            .find("#table-filters-dialog-produtos")
            .find('.checkbox_produto:checked')
            .each(function(i, e){

                var id = $(this).val();
                var valor = $(this).parent().parent().find('.valor_parcial').val();

                dados.append('produtos['+i+'][produto]', id);
                dados.append('produtos['+i+'][quantidade_devolvida]', valor);
            });

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: '{{ route("devolucao_nota.salvar.editar") }}',
            method: 'POST',
            data: dados,
            processData: false,
            contentType: false,
            success: function(data){
                message('Atenção', data.message)
                $(document).find('#editar-devolucao-modal').modal('hide');
                buscarNotas();
            },
            error: function(callback){
                errors = callback.responseJSON.error;

                for(var field in errors){
                    showErrorsInputsModalEditar(form, field, errors[field]);
                }
            }
        })
    }

    function showErrorsInputsModalEditar(form, input, message){
        var inputexplode = input.split(".");
		if(inputexplode.length > 1){
			input = inputexplode[0]+"["+inputexplode[1]+"]";
			message = message;
			var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
			$input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
		}
        if(input.match(/produtos/i) != null){
            $input = form.find(".valor_parcial:not([disabled])").eq(input.split('.')[1]);
        }
        else if(input == 'valor_parcial'){
            $input = form.find(".valor_parcial_radio").parent().parent();
        }
        else{
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

    function removerDocumentoDiversos(id_documento,key){
        $.ajax({
            url: '{{ route("devolucao_nota.salvar.remover_diversos") }}',
            dataType: 'json',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_documento: id_documento},
            success: function(data){
                $(document).find(".diversos"+key).remove();

                message('Atenção','Documento Excluido.');
            },
            error: function(callback){
                message('Erro','Ocorreu um erro ao tentar excluir, por favor tente mais tarde.');
            }
        });
    }

    function removerDocumentoIsento(){
        var id_isento_val = $(document).find('#id_isento').val();

        $.ajax({
            url: '{{ route("devolucao_nota.salvar.remover_isento") }}',
            dataType: 'json',
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id_isento: id_isento_val},
            success: function(data){
                var conteudo = 
                '<div class="col">'+
                    '<input id="arquivo_cliente_isento" class="form form-control" name="arquivo_cliente_isento" type="file" autocomplete="off">'+
                '</div>';
                $('.div_isento').empty();
                $('.div_isento').append( conteudo );

                message('Atenção','Documento Isento Excluido.','');
            },
            error: function(callback){
                message('Erro','Ocorreu um erro ao tentar excluir, por favor tente mais tarde.');
            }
        });
    }
</script>
@endsection