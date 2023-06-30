@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_unidade_negocio_metas" id="form_unidade_negocio_metas" onsubmit="return false;">
    @csrf
    {!! Form::hidden('usuarios', '', ['id' => 'usuarios']) !!}
    {!! Form::hidden('usuarios_unidade_negocio', '', ['id' => 'usuarios_unidade_negocio']) !!}
    {!! Form::hidden('usuarios_adicionado', '', ['id' => 'usuarios_adicionado']) !!}
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('mes_ano', 'Mês/Ano', []) }}
            {{ Form::text('mes_ano', '', ['id' => 'mes_ano', 'class' => 'form-control data', 'placeholder' => 'Mês/Ano', 'maxlength' => '20']) }}
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-12"> 
            {{ Form::label('unidade_negocio', 'Unidade Negócio', []) }}
            <div class="input-group" id="unidade_negocio_group">
                {{ Form::text('unidade_negocio', '', ['id' => 'unidade_negocio', 'class' => 'form-control', 'placeholder' => 'Unidade Negócio', 'maxlength' => '250']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-unidade_negocio"><i class="bt-view m-2"></i></span>
            </div>
        </div>
    </div>
    <div class="content-filter-dialog">	
        <p><strong>Adicionar Membro</strong></p>
        <div class="form-row">
            <div class="form-group col-sm-8">
                {{ Form::label('usuario', 'Vendedor', []) }}
                <div class="input-group" id="usuario_group">
                    {{ Form::text('usuario', '', ['id' => 'usuario', 'class' => 'form-control', 'placeholder' => 'Vendedor', 'maxlength' => '250']) }}
                    <span class="input-group-addon border rounded-right" id="bt-search-usuario"><i class="bt-view m-2"></i></span>
                </div>
            </div>
            <div class="form-group col-sm-4">
                {{ Form::label('meta', 'Meta', []) }}
                <div class="input-group" id="usuario_group">
                    {!! Form::text('meta', '', ['id' => 'meta', 'class' => 'form-control moeda text-right', 'placeholder' => 'Meta', 'maxlength' => '21']) !!}
                    <span class="input-group-addon border-right border-top border-bottom rounded-right btn-line-add-span">
                        <i class="btn-line-add rounded-right" id="btn-add_membro"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="content-dialog-table">
        <div class="content-table">
            <table class="table table-striped" id="table-unidade_negocio-membros">
                <thead>
                    <th>Membro</th>
                    <th class="tb_number">Meta</th>
                    <th class="td_acao"></th>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number" id='total_meta'></td>
                    <td class="td_acao"></td>
                </tfoot>
            </table>
        </div>
    </div>
    <div id="messagem_error_usuarios"></div>
    <div class="col-sm-12 mt-5" id="button-bottom">
        {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar')) }}
    </div> 
</form>
<script>
    $(document).ready(function(){
        form_modal = $(document).find("#form_unidade_negocio_metas");

        form_modal.find('.data').datepicker({ 
            format: 'mm/yyyy',
            zIndex: 2000,
            language: 'pt-BR',
            autoHide: true,
        });
        form_modal.find('.data').mask('00/0000');

        form_modal.find("#unidade_negocio").autocomplete(optionsAutoCompleteUnidadeNegocioModal(form_modal));
        form_modal.find("#bt-search-unidade_negocio").off('click');
        form_modal.find("#bt-search-unidade_negocio").on('click', function(){
            showModalUnidadeNegocioModal(form_modal);
        });

        form_modal.find(".moeda").maskMoney({thousands:'.', decimal:','});

        form_modal.find("#usuario").autocomplete(optionsAutoCompleteUsuarioModal(form_modal));

        form_modal.find("#bt-search-usuario").off('click');
        form_modal.find("#bt-search-usuario").on('click', function(){
            showModalUsuarioModal(form_modal);
        });

        form_modal.find("#btn-add_membro").off('click');
        form_modal.find("#btn-add_membro").on('click', function(){
            adicionarRepresentante(form_modal);
        });


        form_modal.find("#unidade_negocio").off('change');
        form_modal.find("#unidade_negocio").on('change', function(){
            getRepresentanteUnidadeNegocio(form_modal);
        });

        form_modal.find("#btn-salvar").on('click', function(){
            inserirDados(form_modal);
        });

        table_membros_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "25vh",
            "autoWidth": false,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Membro inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Membro inserido",
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
                    'targets': 'td_acao',
                    'class': 'td_acao',
                    'width': '5px',
                    "orderable": false
                }
                
            ],
            "order": [[ 0, 'asc' ]]
        };
        table_membros = '';
        table_membros = $(document).find('#table-unidade_negocio-membros').DataTable(table_membros_options);
        table_membros.draw();
    });

    function optionsAutoCompleteUnidadeNegocioModal(form_modal){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('unidade_negocio.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_unidade_negocio_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal.find("#unidade_negocio").val(ui.item.value)
                return false;
            }
        };
    }

    function showModalUnidadeNegocioModal(form_modal){
        $.ajax({
            url: '{{ route('unidade_negocio.modal.buscar') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_unidade_negocio", "Buscar Usuário", data, 'modal-lg');
                table_modal_buscar_unidade_negocio.on('draw', function () {

                    $(document).find("#table-filters-unidade_negocio").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-unidade_negocio").find('tbody').find("tr").on("click", function(){
                        returnDadosUnidadeNegocioModal($(this), form_modal);
                    });

                });
            }
        });
    }

    function returnDadosUnidadeNegocioModal($dados, form_modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_unidade_negocio").modal("hide");
        
        form_modal.find('#unidade_negocio').val($dados.find("td").eq(0).text());
    }

    function adicionarRepresentante(form_modal){
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: '{{ route('unidade_negocio.metas.adicionar_representante') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form_modal,
            async: false,
            success: function (data){
                linhas = [];
                table_membros.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        ajusteTamanhoTable(data.response.tabela[posicao].nome),
                        data.response.tabela[posicao].meta,
                        createBtnDeleteRepresentante(data.response.tabela[posicao].id_usuario),
                    ];
                    linhas.push(linha)
                }
                table_membros.rows.add(linhas).draw();

                form_modal.find("#usuarios").val(data.response.usuarios);
                form_modal.find("#usuarios_adicionado").val(data.response.usuarios_adicionado);

                limparCamposAdicionarMembro(form_modal);

                $('.dataTables_scrollFootInner').find('#total_meta').html(data.response.total_meta);
            },
            error: function (data){

            }
        });
    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='"+$value+"'>"+$value+"</div></div>";
    
        return $html;
    }

    function inserirDados(form_modal){
        limparMesagemErroModal(form_modal);
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: "{{ route('unidade_negocio.metas.adicionar') }}", 
            dataType: 'json',
            data: data_form_modal,
            method: 'POST',
            async: false,
            success: function(callback){
                $(form_modal).parents('.modal').modal('hide');
                filterAjax($("#form_filter").serialize());
            },
            error: function(callback){
                var dados = callback.responseJSON;
                mensagemErroModal(form_modal, dados);
            }
        });
    }

    function limparMesagemErroModal(form_modal){      
        form_modal.find('.error-message').remove();
        form_modal.find('input, select, span, div').removeClass('error-input');
    }

    function mensagemErroModal(form_modal, json_error){
        if(Object.keys(json_error).length > 0){
            for(var field in json_error.error){
                showErrorsInputsModal(form_modal, field, json_error.error[field]);
            }
        }
    }

    function showErrorsInputsModal(form_modal, input, message){
        if(input.localeCompare('unidade_negocio') == 0){
            var $input = $(form_modal).find("#bt-search-unidade_negocio");
            $(form_modal).find("input[name='unidade_negocio']").addClass('error-input');
        }else if(input.localeCompare('usuarios') == 0){
            var $input = $(form_modal).find("#messagem_error_usuarios");
            message = "Não foi adicionado Membros";
        }else{
            var $input = $(form_modal).find("input[name='"+input+"'], select[name='"+input+"']");
        }

        $input.after("<label class='error-message' for='err"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }

    function getRepresentanteUnidadeNegocio(form_modal){
        data_form_modal = form_modal.serialize();
        $.ajax({
            url: '{{ route('unidade_negocio.metas.get_representante_por_unidade_negocio_meta') }}',
            type: 'POST',
            dataType: 'json',
            data: data_form_modal,
            async: false,
            success: function (data){
                linhas = [];
                table_membros.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        ajusteTamanhoTable(data.response.tabela[posicao].nome),
                        data.response.tabela[posicao].meta,
                        createBtnDeleteRepresentante(data.response.tabela[posicao].id_usuario),
                    ];
                    linhas.push(linha)
                }
                table_membros.rows.add(linhas).draw();   
                form_modal.find("#usuarios").val(data.response.usuarios);
                form_modal.find("#usuarios_unidade_negocio").val(data.response.usuarios_unidade_negocio);
                $('.dataTables_scrollFootInner').find('#total_meta').html(data.response.total_meta);
            },
        });
    }

    function createBtnDeleteRepresentante($id){
        var $html = "<a href=\"#\" data-id=\""+$id+"\" data-modal=\"\" data-title_modal=\"Excluir Representante\" class=\"bt-delete\" data-placement=\"top\" title=\"Excluir\" onclick=\"deletarRepresentante($(this))\"></a>";
        return $html;
    }

    function deletarRepresentante($this){
        var form_modal = $(document).find("#form_unidade_negocio_metas");
        var id = $($this).data("id");
        var usuarios_unidade_negocio = form_modal.find("#usuarios_unidade_negocio").val();
        var usuarios_adicionado = form_modal.find("#usuarios_adicionado").val();
        $.ajax({
            url: '{{ route('unidade_negocio.metas.deletar_representante') }}',
            type: 'POST',
            dataType: 'json',
            data:  {
                _token: "{{ csrf_token() }}", 
                id: id,
                usuarios_unidade_negocio: usuarios_unidade_negocio,
                usuarios_adicionado: usuarios_adicionado,
            },
            async: false,
            success: function (data){
                linhas = [];
                table_membros.clear().draw();
                for (var posicao in data.response.tabela){
                    linha = [
                        ajusteTamanhoTable(data.response.tabela[posicao].nome),
                        data.response.tabela[posicao].meta,
                        createBtnDeleteRepresentante(data.response.tabela[posicao].id_usuario),
                    ];
                    linhas.push(linha)
                }
                table_membros.rows.add(linhas).draw();   
                form_modal.find("#usuarios").val(data.response.usuarios);
                form_modal.find("#usuarios_adicionado").val(data.response.usuarios_adicionado);
                form_modal.find("#usuarios_unidade_negocio").val(data.response.usuarios_unidade_negocio);
                $('.dataTables_scrollFootInner').find('#total_meta').html(data.response.total_meta);
            },
            error: function (data){

            }
        });
    }

    function optionsAutoCompleteUsuarioModal(form_modal){
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                $.post("{{ route('usuario.autocomplete_codigo_representante') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
                $('.ui-autocomplete').css("z-index", (parseInt($('#modal_unidade_negocio_adicionar').css('z-index')) + 1));
            },
            response: function( event, ui ) {
                if(ui.content.length === 0){
                    event.stopPropagation();
                    return false;
                }
            },
            select: function( event, ui ) {
                event.stopPropagation();
                form_modal.find("#usuario").val(ui.item.value)
                return false;
            }
        };
    }

    function showModalUsuarioModal(form_modal){
        $.ajax({
            url: '{{ route('usuario.modal.buscar') }}',
            type: 'GET',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (data){
                createModal("modal_search_usuario", "Buscar Usuário", data, 'modal-lg');
                table_modal_buscar_user.on('draw', function () {

                    $(document).find("#table-filters-user").find('tbody').find("tr").off("click");
                    $(document).find("#table-filters-user").find('tbody').find("tr").on("click", function(){
                        returnDadosUsuarioModal($(this), form_modal);
                    });

                });
            }
        });
    }

    function returnDadosUsuarioModal($dados, form_modal){
        if($dados.find("td").eq(0).hasClass('dataTables_empty')){
            return false;
        }
        $(document).find("#modal_search_usuario").modal("hide");
        
        form_modal.find('#usuario').val($dados.find("td").eq(1).text()+" - "+$dados.find("td").eq(0).text());
    }

    function limparCamposAdicionarMembro(form_modal){
        form_modal.find("#usuario").val("");
        form_modal.find("#meta").val("");
    }
</script>
@endsection