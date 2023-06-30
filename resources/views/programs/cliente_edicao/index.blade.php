@extends('layouts.app')

@section('content')
    <form action="{{ route('cliente.edicao.salvar') }}" name="clientes_atualizar" id="clientes_atualizar">
        @csrf
        {{ Form::hidden('id', '', ['id' => 'id']) }}
        
        <div class="content-filter">	
            @csrf
            <div class="content-fields">
                <div class="row">
                    <div class="form-group col-sm-12"> 
                        {{ Form::label('cliente_nome_cpf_cnpj', 'Cliente', []) }}
                        <div class="input-group">
                            {{ Form::text('cliente_nome_cpf_cnpj', '', ['id' => 'cliente_nome_cpf_cnpj', 'class' => 'form-control input-label', 'placeholder' => 'Cliente']) }}
                            <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialogCadastro") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
                        </div>
                    </div>
                </div>
        
                <div class="row">
                    <hr class="col sm">
                </div>
                <div class="row">
                    <div class="form-group col-sm-6"> 
                        {{ Form::label('razao_social', 'Razão social', []) }}
                        {{ Form::text('razao_social', '', ['id' => 'razao_social', 'class' => 'form-control', 'placeholder' => 'Razão social', 
                        'maxlength' => '150','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-3" id='inscricao_estadual_div'> 
                        {{ Form::label('inscricaoestadual', 'Inscrição Estadual', []) }}
                        {{ Form::text('inscricaoestadual', '', ['id' => 'inscricaoestadual', 'class' => 'form-control', 'placeholder' => 'Inscrição Estadual', 'maxlength' => '20','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-3"> 
                        {{ Form::label('indicador_inscricao_estadual', 'Inscrição Estadual Indicação', []) }}
                        {{ Form::select('indicador_inscricao_estadual', $indicador_inscricao_estadual, '', ['id' => 'indicador_inscricao_estadual', 'class' => 'form-control disabled', 'disabled' => true]) }}
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-sm-3"> 
                        {{ Form::label('ddd', 'DDD', []) }}
                        {{ Form::text('ddd', '', ['id' => 'ddd', 'class' => 'form-control', 'placeholder' => 'DDD', 'maxlength' => '2','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-3"> 
                        {{ Form::label('telefone', 'Telefone', []) }}
                        {{ Form::text('telefone', '', ['id' => 'telefone', 'class' => 'form-control', 'placeholder' => 'Telefones', 'maxlength' => '10','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-6"> 
                        {{ Form::label('email', 'E-mail', []) }}
                        {{ Form::text('email', '', ['id' => 'email', 'class' => 'form-control', 'placeholder' => 'E-mail', 'maxlength' => '150','disabled' => true]) }}
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-sm-1"> 
                        {{ Form::label('cep', 'CEP', []) }}
                        {{ Form::text('cep', '', ['id' => 'cep', 'class' => 'form-control', 'placeholder' => 'CEP', 'maxlength' => '15','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('tipo_logradouro', 'Tipo de Logradouro', []) }}
                        {{ Form::text('tipo_logradouro', '', ['id' => 'tipo_logradouro', 'class' => 'form-control', 'placeholder' => 'Tipo de Logradouro', 'maxlength' => '150','disabled' => true,'disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-5">
                        {{ Form::label('logradouro', 'Logradouro', []) }}
                        {{ Form::text('logradouro', '', ['id' => 'logradouro', 'class' => 'form-control', 'placeholder' => 'Logradouro', 'maxlength' => '150','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('numero', 'Número', []) }}
                        {{ Form::text('numero', '', ['id' => 'numero', 'class' => 'form-control', 'placeholder' => 'Número', 'maxlength' => '10','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-2">
                        {{ Form::label('complemento', 'Complemento', []) }}
                        {{ Form::text('complemento', '', ['id' => 'complemento', 'class' => 'form-control', 'placeholder' => 'Complemento', 'maxlength' => '60','disabled' => true]) }}
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-sm-6">
                        {{ Form::label('bairro', 'Bairro', []) }}
                        {{ Form::text('bairro', '', ['id' => 'bairro', 'class' => 'form-control', 'placeholder' => 'Bairro', 'maxlength' => '60','disabled' => true]) }}
                    </div>
                    <div class="form-group col-sm-4">
                        {{ Form::label('cidade', 'Cidade', []) }}
                        {{ Form::text('cidade', '', ['id' => 'cidade', 'class' => 'form-control', 'placeholder' => 'Cidade', 'maxlength' => '60','disabled' => true]) }}
                    </div>

                    <div class="form-group col-sm-2">
                        {{ Form::label('uf', 'Estado', []) }}
                        {{ Form::select('uf', $estados, '', ['id' => 'uf', 'class' => 'form-control disabled', 'placeholder' => 'Estado','disabled' => true]) }}
                    </div>
                </div>
                <div class="row">
                    <hr class="col sm">
                </div>
                <div class="row table-documentos-div">
                    <div class="col-12">
                        <h3><center>Documentos</center></h3>
					    {{ Form::hidden('cpf_cnpj', '',['id' =>'cpf_cnpj','class' => 'form-control']) }}
                    </div>
                </div>
                <div class="row">
                    <div class="content-table">
                        <table class="table table-striped table-not-edit table-not-view" id="table-documentos">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Documento</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group col-md-3">
                        {{ Form::button('Adicionar Documento', ['class' => 'btn btn-success', 'id' => 'btn_adicionar_documento']) }}
                    </div>
                </div>
                <div class="row adicionar-elemento">

                </div>
                <div class="col-sm-12 mt-2" id="button-bottom">
                    {{ Form::button('Enviar', array('class' => 'btn btn-success float-right d-none ml-2', 'id' => 'btn-enviar')) }}
                    {{ Form::button('Limpar', array('class' => 'btn btn-primary float-right d-none', 'id' => 'btn-reset')) }}
                </div> 
            </div>
        </div>
    </form>
@endsection

@section('script-footer')        
    
    $(document).ready(function(){
        $(document).find("#cep").mask("00000-000");

        var options =  {
        onKeyPress: function(telefone, e, field, options) {
            var masks = ['0000-00000', '00000-0000'];
            var mask = (telefone.length>9) ? masks[1] : masks[0];
            $('#telefone').mask(mask, options);
        }};

        var x = 1;
		var max_fields = 20;
		
		$('#btn_adicionar_documento').click (function(e){
			e.preventDefault(); 
			if (x < max_fields){
                var anterior = 0;
                var conteudo = 
                    '<div id="adicionar-documento-div-'+x+'" class="form-group col-sm-12 remove'+x+' remover-inputs-documentos">'+
                        '<div class="col-sm-5">'+
                            '{{ Form::label("descricao_documento", "Descrição Documento") }}'+
                            '<input type="text" id="descricao_documento['+x+']" name="descricao_documento['+x+']" class="form-control campo-documento campo_descricao'+x+'"  placeholder="Escreva o Documento" maxlength="50">'+
                        '</div>'+
                        '<div class="col-sm-5">'+
                            '{{ Form::label("label_documento", "Documento") }}'+
                            '<input type="file" id="documento['+x+']" name="documento['+x+']" class="form-control campo-documento campo_arquivo'+x+'">'+
                        '</div>'+
                        '<div class="col-sm-2">'+
                            '<br>'+
                            '<button type="button" id="remove'+x+'" class="btn btn-danger remove_documento btn-remover-documento">Remover</button>'+
                        '</div>'+
                    '</div>';
                
                if(x > 1){
                    anterior = x - 1;
                    for(var i = 1; i < x; i++){
                        var descricao = $(".campo_descricao"+i).val();
                        var documento = $(".campo_arquivo"+i).val();
                        
                        if(descricao == '' || documento == ''){
                            message("Atenção", "Adcicione documentos para adicionar mais campos!");
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

        $(document).find("#telefone").mask("0000-00000", options);

        $(document).find('#btn-enviar').on('click', function(){
            ajaxForm($(document).find('#clientes_atualizar'));
        })

        $(document).find('#btn-reset').on('click', function(){
            $(document).find('#clientes_atualizar').trigger("reset");
        });

        $(document).find('#cep').on('change', function(){
            buscaCep();
        });

        $(document).find('#clientes_atualizar').on('reset', function(){
            $(document).find('#cliente_nome_cpf_cnpj').prop('disabled', false);
            $(document).find('#razao_social, #inscricaoestadual, #telefone, #email, #tipo_logradouro, #logradouro, #numero, #complemento, #bairro, #cidade, #cep, #ddd, #indicador_inscricao_estadual, #uf').prop('disabled', true);

            $(document).find('#btn-enviar, #btn-reset').addClass('d-none');
            $(document).find('#indicador_inscricao_estadual, #uf').addClass('disabled');

            $(document).find('#btn_adicionar_documento').addClass('d-none disabled');
            $(document).find('.table-documentos-div').addClass('d-none disabled');
            $(document).find('#table-documentos').addClass('d-none disabled');

            $(document).find("#bt-search-cliente-busca").off("click");
            $(document).find("#bt-search-cliente-busca").on("click", function(event){
                event.stopPropagation();
                showModalClienteBusca($(this).data("route"));
                return false;
            });

            $(document).find("#clientes_atualizar").find('.error-message').remove();
            $(document).find("#clientes_atualizar").find('.error-input').removeClass('error-input');    

            $(document).find('#inscricao_estadual_div').show();

        })

        $(document).find('#cliente_nome_cpf_cnpj').autocomplete(optionsAutoCompleteCliente());
        $(document).find('#clientes_atualizar').trigger("reset");

        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteBusca($(this).data("route"));
            return false;
        });

        table_documentos = $('#table-documentos')
        .on( 'error.dt', function ( e, settings, techNote, men ) {
            hide_loader();
            message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
        }).DataTable({
            "show": function(event, ui) {
                var oTable = $('div.dataTables_scrollBody>table.display', ui.panel).dataTable();
                if ( oTable.length > 0 ) {
                    oTable.fnAdjustColumnSizing();
                }
            },
            "searching": false,
            "lengthChange": false,
            "info": false,
            "autoWidth": false,
            "pageLength": 15,
            "sScrollY": "200px",
            "bScrollCollapse": true,
            "bPaginate": false,
            "bJQueryUI": true,
            "drawCallback": function(settings) {
                $('[data-toggle="popover"]').popover({
                    container: 'body',
                    html: true,
                    show: true,
                    template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
                });
                $('[data-toggle="popover"]').on('show.bs.popover', function () {
                    var $this = $(this);
                    $('.popover').not($this).each(function(){
                        $("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
                    });
                    $("body").on("keyup", function(e){
                        if(e.keyCode == 27){
                            $($this).popover('hide');
                        }
                    });
                });
    
                $(document).find("a.thumb").fancybox(
                    {
                        onComplete: function(){
                            $('#fancybox-content')
                                .on('mouseover', function(){
                                    $(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
                                })
                                .on('mouseout', function(){
                                    $(this).children('#fancybox-img').css({'transform': 'scale(1)'});
                                })
                                .on('mousemove', function(e){
                                    $(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
                            });
                        }
                    }
                );
    
            },
            "aoColumnDefs": [
                { "sWidth": "10%", "aTargets": [ -1 ] }
            ],
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
        }).on("click",".deletaBtn", function() {
            var tr = $(this).closest('tr');
            tr.css("background-color","red");
            tr.fadeOut(1000, function(){
                tr.remove();
            });
        }).draw();

        $(document).find('#indicador_inscricao_estadual').on('change', function(){
            if($(this).val() == 2){
                $(document).find('#inscricao_estadual_div').hide();
            }
            else{
                $(document).find('#inscricao_estadual_div').show();
            }
        })
    });

    function ajaxForm(form){

        var form_data = new FormData(form[0]);
        var url = form.attr("action");

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        $.ajax({
            url: url,
            dataType: 'json',
            data: form_data,
            method: 'POST',
            processData: false,
            contentType: false,
            success: function(data){
                message("Atenção", "Dados salvos com sucesso!");
                form.trigger("reset");
                $('.remover-inputs-documentos').remove();
            },
            error: function(data){
                var errors = data.responseJSON.error;
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field]);
                }
            }
        });

        function showErrorsInputs(form, input, message){
            var inputexplode = input.split(".");
            if(inputexplode.length > 1){
                input = inputexplode[0]+"["+inputexplode[1]+"]";
                message = message;
                var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']").parent();
                $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
            }else{
                var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
                $input.after("<label class='error-message col-sm-5' for='"+input+"'>"+message+"</label>");
                $input.addClass('error-input');
            }
        }
    }
    
    function buscaCep(){
        $.ajax({
            url: '{{ route('busca_cep') }}',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                cep: $(document).find('#cep').val()
            },
            method: 'POST',
            success: function(data){
                $(document).find('#tipo_logradouro').val(data.dados.tipo_logradouro);
                $(document).find('#logradouro').val(data.dados.logradouro);
                $(document).find('#bairro').val(data.dados.bairro);
                $(document).find('#cidade').val(data.dados.cidade);
                $(document).find('#uf').val(data.dados.uf);
            },
        });
    }

    function optionsAutoCompleteCliente(){

        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                $.post("{{ route('cliente.autocompleteid') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($(document).find("#frm_cad_cliente_limite_credito").parents('.modal').css('z-index')) + 10));
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
                $(document).find("#cliente_nome_cpf_cnpj").val(ui.item.label);
                $(document).find("#id").val(ui.item.value);

                $.ajax({
                    url: '{{ route('cliente.edicao.infoCliente') }}',
                    dataType: 'json',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: $(document).find('#id').val()
                    },
                    method: 'POST',
                    success: function(data){

                        $(document).find('#cliente_nome_cpf_cnpj').prop('disabled', true);
                        $(document).find('#razao_social, #inscricaoestadual, #telefone, #email, #tipo_logradouro, #logradouro, #numero, #complemento, #bairro, #cidade, #cep, #ddd, #indicador_inscricao_estadual, #uf').prop('disabled', false);
                        
                        $(document).find('#btn-enviar, #btn-reset').removeClass('d-none');
                        $(document).find('#indicador_inscricao_estadual, #uf').removeClass('disabled');

                        $(document).find('#btn_adicionar_documento').removeClass('d-none disabled');
                        $(document).find('#cpf_cnpj').val(data.response.cpf_cnpj);
                        
                        $(document).find('#razao_social').val(data.response.razao_social);
                        $(document).find('#inscricaoestadual').val(data.response.inscricaoestadual);
                        $(document).find('#telefone').val(data.response.telefone);
                        $(document).find('#email').val(data.response.email);
                        $(document).find('#tipo_logradouro').val(data.response.tipo_logradouro);
                        $(document).find('#logradouro').val(data.response.logradouro);
                        $(document).find('#numero').val(data.response.numero);
                        $(document).find('#complemento').val(data.response.complemento);
                        $(document).find('#bairro').val(data.response.bairro);
                        $(document).find('#cidade').val(data.response.cidade);
                        $(document).find('#cep').val(data.response.cep);
                        $(document).find('#ddd').val(data.response.ddd);
                        $(document).find('#indicador_inscricao_estadual').val(data.response.indicador_inscricao_estadual);
                        $(document).find('#uf').val(data.response.uf);

                        if(data.response.indicador_inscricao_estadual == 2){
                            $(document).find('#inscricao_estadual_div').hide();
                        }
                        else{
                            $(document).find('#inscricao_estadual_div').show();
                        }

                        if(data.response.documentos){
                            $(document).find('.table-documentos-div').removeClass('d-none disabled');
                            $(document).find('#table-documentos').removeClass('d-none disabled');
                            
                            table_documentos.clear().draw();

                            var out = [];
                            for (var fields in data.response.documentos){
                                var documento = '';
                                if(ext(data.response.documentos[fields].documento) == 'PNG' || ext(data.response.documentos[fields].documento) == 'png' || ext(data.response.documentos[fields].documento) == 'jpg' || ext(data.response.documentos[fields].documento) == 'JPG'){
                                    documento = '<a href="'+data.response.documentos[fields].documento+'"  class="thumb" data-toggle="popover" data-trigger="hover" aria-readonly="true" title="Documento" data-content="<img src=\''+data.response.documentos[fields].documento+'\' width=\'250\' class=\'rounded mx-auto d-block\' alt=\'Documento\'>"><i class="btn-foto-canhoto"></i>Documento</a>';
                                }else{
                                    documento = '<a href="'+data.response.documentos[fields].documento+'"  target="blank"><i class="btn-download"></i>Documento</a>';
                                }

                                out.push([
                                    data.response.documentos[fields].descricao,
                                    documento,
                                    createBtViewExcluirDocumento(data.response.documentos[fields]),
                                ]);
                            }
                            table_documentos.rows.add(out).draw();
                        }else{
                            $(document).find('.table-documentos-div').addClass('d-none disabled');
                            table_documentos.clear().draw();
                            $(document).find('#table-documentos').addClass('d-none disabled');
                        }
                    },
                });
                
                return false;
            }
        };
    }

    function createBtViewExcluirDocumento($this){
        var html = "<a href=\"#\" onclick=\"excluirDocumento('"+$this.id_documento+"');\"  class='btn btn-danger deletaBtn'>Exluir</a>";
        return html;
    }

    function ext(path) {
        var idx = (~-path.lastIndexOf(".") >>> 0) + 2;
        return path.substr((path.lastIndexOf("/") - idx > -3 ? -1 >>> 0 : idx));
    }

    function excluirDocumento(id_documento){
        $.ajax({
            url: '{{ route('cliente.edicao.documento.excluir') }}',
            method: 'POST',
            data: {
                _token: '{{csrf_token()}}', id : id_documento,
            },
            success: function(body){
                if(body.status = 'success'){
                    message("Atenção", "Documento Excluido com Sucesso");
                }else{
                    message("Atenção", "Erro ao processar, tente novamente mais tarde");
                }
            },
            error: function(data){
                message("Atenção", "Erro ao processar, tente novamente mais tarde");
            }
        });
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
        $(document).find("#cliente_id_filtro").val($dados.find("td").eq(0).text());
        $(document).find("#cliente_nome_cpf_cnpj").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());

        $.ajax({
            url: '{{ route('cliente.edicao.infoCliente') }}',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                cliente_nome_cpf_cnpj: $dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text()
            },
            method: 'POST',
            success: function(data){

                $(document).find('#cliente_nome_cpf_cnpj').prop('disabled', true);
                $(document).find('#razao_social, #inscricaoestadual, #telefone, #email, #tipo_logradouro, #logradouro, #numero, #complemento, #bairro, #cidade, #cep, #ddd, #indicador_inscricao_estadual, #uf').prop('disabled', false);
                
                $(document).find('#btn-enviar, #btn-reset').removeClass('d-none');
                $(document).find("#bt-search-cliente-busca").off("click");

                $(document).find('#btn_adicionar_documento').removeClass('d-none disabled');
                
                $(document).find('#indicador_inscricao_estadual, #uf').removeClass('disabled');

                $(document).find('#razao_social').val(data.response.razao_social);
                $(document).find('#inscricaoestadual').val(data.response.inscricaoestadual);
                $(document).find('#telefone').val(data.response.telefone);
                $(document).find('#email').val(data.response.email);
                $(document).find('#tipo_logradouro').val(data.response.tipo_logradouro);
                $(document).find('#logradouro').val(data.response.logradouro);
                $(document).find('#numero').val(data.response.numero);
                $(document).find('#complemento').val(data.response.complemento);
                $(document).find('#bairro').val(data.response.bairro);
                $(document).find('#cidade').val(data.response.cidade);
                $(document).find('#cep').val(data.response.cep);
                $(document).find('#ddd').val(data.response.ddd);
                $(document).find('#indicador_inscricao_estadual').val(data.response.indicador_inscricao_estadual);
                $(document).find('#uf').val(data.response.uf);

                if(data.response.documentos){
                    $(document).find('.table-documentos-div').removeClass('d-none disabled');
                    $(document).find('#table-documentos').removeClass('d-none disabled');
                    table_documentos.clear().draw();
                    var out = [];
                    for (var fields in data.response.documentos){
                        var documento = '';
                        if(ext(data.response.documentos[fields].documento) == 'PNG' || ext(data.response.documentos[fields].documento) == 'png' || ext(data.response.documentos[fields].documento) == 'jpg' || ext(data.response.documentos[fields].documento) == 'JPG'){
                            documento = '<a href="'+data.response.documentos[fields].documento+'"  class="thumb" data-toggle="popover" data-trigger="hover" aria-readonly="true" title="Documento" data-content="<img src=\''+data.response.documentos[fields].documento+'\' width=\'250\' class=\'rounded mx-auto d-block\' alt=\'Documento\'>"><i class="btn-foto-canhoto"></i>Documento</a>';
                        }else{
                            documento = '<a href="'+data.response.documentos[fields].documento+'"><i class="btn-download"></i>Documento</a>';
                        }

                        out.push([
                            data.response.documentos[fields].descricao,
                            documento,
                            createBtViewExcluirDocumento(data.response.documentos[fields]),
                        ]);
                    }
                    table_documentos.rows.add(out).draw();
                }else{
                    $(document).find('.table-documentos-div').addClass('d-none disabled');
                    table_documentos.clear().draw();
                    $(document).find('#table-documentos').addClass('d-none disabled');
                }
            }
        });

        $(document).find("#cliente_searsh_show").modal("hide");
    }
@endsection