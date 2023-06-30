@extends('layouts.page-dialog')

@section('content')
@if(isset($motivo_reprovacao))
<div class="alert alert-danger" role="alert">
	<p>Esta etapa permanece pendente após análise.<br>
	Motivo: {{$motivo_reprovacao}}</p> 
</div>
@endif
<form action="#" id='form-aprovar-devolucao' onsubmit="return false;" @if($status==3)enctype="multipart/form-data"@endif>
    @csrf
    {!! Form::hidden('id', $id, ['id' => 'id_modal']) !!}
    <div class="row mt-2">
        <div class="col-sm-4">
            <b>Estabelecimento</b><br>
            {!! $estabelecimento !!}
        </div>
        <div class="col-sm-4">
            <b>Cliente</b><br>
            {!! $cliente !!}
        </div>
        <div class="col-sm-4">
            <b>Número da Nota</b><br>
            {{ $nota_fiscal }}
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-4">
            <b>Motivo</b><br>
            {!! $motivos[$motivo] !!}
        </div>
        <div class="col-sm-4">
            <b>Tipo de venda</b><br>
            {!! $tipo_venda !!}
        </div>
        <div class="col-sm-4">
            <b>Data de emissão</b><br>
            {!! $emissao !!}
        </div>
    </div>
    <div class="row mt-2">
        <div class="col-sm-4">
            <b>Valor</b><br>
            {!! $valor !!}
        </div>
        <div class="col-sm-4">
            <b>Tipo de devolução</b><br>
            <span class='mt-3'>{{ $valor_parcial }}</span>
        </div>
        <div class="col-sm-4">
            {!! Form::label('status', 'Status', ['class' => 'input_label']) !!}
            {!! Form::select('status', $status_lista, $status, ['class' => 'form-control', 'placeholder' => 'Selecione um status']) !!}
        </div>
    </div>

    @if((isset($laudo_tecnico) && !empty($laudo_tecnico)) || (isset($arquivo) && !empty($arquivo)) || (isset($nota_cliente_arquivo) && !empty($nota_cliente_arquivo)) || (isset($romaneio_arquivo) && !empty($romaneio_arquivo)) )
    <div class="row mt-1">
        <div class="col">
            <b>Arquivos:</b><br>
        @if(isset($laudo_tecnico) && !empty($laudo_tecnico))
            <a href="{{ $laudo_tecnico }}" target='_blank' class='mr-2'><i class="btn-nota-pdf"></i> Laudo técnico</a> 
        @endif
        @if(isset($arquivo) && !empty($arquivo))
            <a href="{{ $arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Imagem do produto</a>
        @endif
        @if(isset($nota_cliente_arquivo) && !empty($nota_cliente_arquivo))
            <a href="{{ $nota_cliente_arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Nota do cliente</a>
        @endif
        @if(isset($romaneio_arquivo) && !empty($romaneio_arquivo))
            <a href="{{ $romaneio_arquivo }}" target='_blank' class='mr-2'><i class="btn-imagem"></i> Romaneio</a>
        @endif
        </div>
    </div>
    @endif

    <div class="row mt-2">
        <div class="col"><b>Contato com o cliente:</b></div>
    </div>
    <div class="row">
        <div class="col">
            {!! Form::label('nome_contato_modal', 'Nome', ['class'=>'input-label']) !!}
            {!! Form::text('nome_contato', $nome_contato, ['id' => 'nome_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            {!! Form::label('telefone_contato_modal', 'Telefone', ['class'=>'input-label']) !!}
            {!! Form::text('telefone_contato', $telefone_contato, ['id' => 'telefone_contato_modal', 'class' => 'form-control']) !!}
        </div>
        <div class="col">
            {!! Form::label('email_contato_modal', 'E-mail', ['class'=>'input-label']) !!}
            {!! Form::text('email_contato', $email_contato, ['id' => 'email_contato_modal', 'class' => 'form-control']) !!}
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
    <div class="row mt-2">
        <div class="col-sm-4">
            Responsabilidade do Frete
            <div class="row mt-2">
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'textil', $responsabilidade_frete_exibir=='textil'?true:false, ['id' => 'responsabilidade_frete_textil', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_textil', 'Têxtil MN', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'cliente', $responsabilidade_frete_exibir=='cliente'?true:false, ['id' => 'responsabilidade_frete_cliente', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_cliente', 'Cliente', ['class'=>'input-label']) !!}
                </div>
                <div class="col">
                    {!! Form::radio('responsabilidade_frete', 'representante', $responsabilidade_frete_exibir=='representante'?true:false, ['id' => 'responsabilidade_frete_representante', 'class'=>'responsabilidade_frete_radio']) !!}
                    {!! Form::label('responsabilidade_frete_representante', 'Representante', ['class'=>'input-label']) !!}
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <p>
                <b>Valor do Frete</b><br>
                {!! $frete_valor !!}
            </p>
        </div>
    </div>
    <div class="row mt-2">
        <div class="col">
            {!! Form::label('laudo_imagem', 'Laudo técnico') !!}
            {!! Form::file('laudo_imagem', ['class' => 'form form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-lg-6">
            {{ Form::label('transportador', 'Transportadora', []) }}
            <div class="input-group" id="transporadora_group">
                {{ Form::text('transportador', $transportador, ['id' => 'transportador', 'class' => 'form-control input-label']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-transportadora" data-route="{{ route("transportador.index.dialog") }}"><i id="bt-view-transportadora" class="bt-view m-2"></i></span>
            </div>
        </div>
        <div class="col-lg-6">
            {!! Form::label('transportador_email', 'E-mail da transportadora') !!}
            {!! Form::text('transportador_email', $transportador_email, ['id' => 'transportador_email', 'class' => 'form-control']) !!}
        </div>
    </div>
    <div class="row">
        <div class="col-sm-6">
            {!! Form::label('nota_cliente_numero', 'Número da nota do cliente') !!}
            {!! Form::text('nota_cliente_numero', $nota_cliente_numero, ['id' => 'nota_cliente_numero', 'class' => 'form-control']) !!}
        </div>
        <div class="col-sm-6">
            Arquivo da nota<br>
            {!! Form::file('nota_cliente_arquivo', ['class' => 'form-control']) !!}
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-sm-6">
            Romaneio<br>
            {!! Form::file('romaneio_arquivo', ['class' => 'form-control']) !!}
        </div>
    
        @if(in_array($estabelecimento_codigo, ['03','04']))
        <div class="col-lg-6">
            {!! Form::label('nota_remessa', 'Número da nota de remessa') !!}
            {!! Form::text('nota_remessa', $nota_remessa, ['id' => 'nota_remessa', 'class' => 'form-control']) !!}
        </div>
        @endif
    </div>

    <div class="row">
        @foreach($aprovadores as $aprovador)
        <div class="col-sm-4">
            <b>{{ $aprovador['status'] }}</b><br>
            {{ $aprovador['aprovador'] }} - {{ $aprovador['data'] }}
        </div>
        @endforeach
    </div>
    
    <div class="row">
        <div class="col">
            {!! Form::label('mensagem_modal', 'Observação') !!}
            {!! Form::textarea('mensagem', '', ['class' => 'form form-control', 'id' => 'mensagem_modal', 'col' => '5', 'rows' => '4', 'maxlength' => '254']) !!}
        </div>
    </div>
    
    <div class="row @if(empty($produtos))d-none @endif valor-div">
        <div class="content-dialog-table">
            <table class="table table-striped table-filter-dialog" id="table-filters-dialog-produtos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Grupo</th>
                        <th>Descrição</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_number">Devolvida</th>
                        <th class="tb_number">Recebida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produtos as $produto)
                    <tr>
                        <td>{{ $produto['codigo'] }}</td>
                        <td>{{ $produto['grupo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
                        <td>{{ $produto['quantidade'] }}</td>
                        <td>{{ $produto['quantidade_devolvida'] }}</td>
                        <td>{!! Form::text('quantidade_recebida', $produto['quantidade_recebida']??'', ['data-id' => $produto['id'], 'class' => 'text-right form-control form-float quantidade_recebida', 'size' => '10']) !!}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>        
    </div>

    <div class="row mt-2">
        <div class="col-sm">
            {!! Form::checkbox('marcar-devolucao-ok', 'ok', false, ['id' => 'marcar-devolucao-ok']) !!}
            {!! Form::label('marcar-devolucao-ok', 'Marcar todos os itens como recebidos') !!}
        </div>
        <div class="col-sm text-right" id='enviar-div'>
            {!! Form::button('Salvar alterações', ['id' => 'btn_salvar', 'class' => 'btn btn-success']) !!}
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
        })

        $(document).find('#btn_salvar').on('click', function(){
            salvarEdicao();
        })

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
            .find('.form-float')
            .maskMoney({thousands:'.', decimal:',', allowZero: true});

        $(document).find("#transportador").autocomplete(optionsAutoCompleteTransportador());
        $(document).find("#bt-view-transportadora").off("click");
        $(document).find("#bt-view-transportadora").on("click", function(event){
            event.stopPropagation();
            modalTransportador();
            return false;
        });

        $(document).find("#marcar-devolucao-ok").on('click', function(){
            if($(this).is(':checked')){
                $(document).find('.quantidade_recebida').each(function (i,e){
                    $(this).val($(this).parent().parent().find('td').eq(4).html());
                    $(this).prop('disabled', true);
                })
            }
            else{
                $(document).find('.quantidade_recebida').each(function (i,e){
                    $(this).val('');
                    $(this).prop('disabled', false);
                })
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
            { "targets": -1, "width": '75px'},
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", "width": '1%'},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    });

    function salvarEdicao(){

        var form = $(document).find('#form-aprovar-devolucao');
        var dados = new FormData($(document).find('#form-aprovar-devolucao')[0]);        

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        if(!$(document).find("#marcar-devolucao-ok").is(':checked')){
            $(document)
                .find("#form-aprovar-devolucao")
                .find('.quantidade_recebida')
                .each(function(i, e){

                    var id = $(this).data('id');
                    var valor = $(this).val();

                    dados.append('produtos['+i+'][id]', id);
                    dados.append('produtos['+i+'][quantidade_recebida]', valor);
                });
        }
        else{
            dados.append('conferencia_devolucao_completa', true);
        }
        
        $.ajax({
            url: '{{ route("devolucao_nota_aprovacao.editar") }}',
            method: 'POST',
            data: dados,
            processData: false,
            contentType: false,
            success: function(data){
                message('Atenção', 'Informações salvas com sucesso')
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

        if(input != 'produtos' && input.match(/produtos/i) != null){
            $input = form.find(".quantidade_recebida").eq(input.split('.')[1]);
        }
        else if(['valor_parcial', 'responsabilidade_frete'].includes(input)){
            $input = form.find("."+input+"_radio").parent().parent();
        }
        else if(input == 'transportador'){
            $input = form.find("#transportador").parent();
        }
        else{
            var $input = form.find("input[name='"+input+"'], select[name='"+input+"'], textarea[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function optionsAutoCompleteTransportador(){
        $(document).find(".error-message").remove();
        return {
           source: function (request, response) {
               request._token = "{{ csrf_token() }}";
               request.estabelecimento = {{ $estabelecimento_codigo }};
               $.post("{{ route('transportador.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#editar-devolucao-modal').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    message('Atenção', 'Nenhuma transportadora encontrada');
                    event.stopPropagation();
                    $(document).find("#transportadora_nome").focus();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                $(document).find("#transportador").val(ui.item.label);
                returnEmailTransportador(ui.item.value);
                return false;
            }
        };
    }

    function modalTransportador(){
        $.ajax({
            url: '{{ Route('transportador.index.dialog') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', estabelecimento: {{ $estabelecimento_codigo }}},
            success: function(data){
				$(document).find('#modal_busca_transportador').remove();
                createModal('modal_busca_transportador', "Busca de transporadora", data, 'modal-lg');
                var modal = $(document).find("#modal_busca_transportador");
                $(document).ready( function(){
                    table_dialog.on('draw', function () {
                        modal.find('tbody').find("tr").off("click");
                        modal.find('tbody').find("tr").on("click", function(){
							returnDadosTransportador($(this), modal);
                        });
                    });
                });
            }
        });
	}

	function returnDadosTransportador($dados, modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
		$(document).find('#editar-devolucao-modal').find("#transportador").val($dados.find("td:eq(1)").text() + " - " + $dados.find("td:eq(2)").text());
        returnEmailTransportador($dados.find("td:eq(0)").text())
		modal.modal('hide');
	}

    function returnEmailTransportador($codigo){
        $.ajax({
            url: '{{ Route('transportador.dados_transportador') }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}', codigo: $codigo},
            success: function(data){
                $(document).find('#editar-devolucao-modal').find('#transporador_email').val(data.response.email);
            }
        });
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
	
</script>
@endsection