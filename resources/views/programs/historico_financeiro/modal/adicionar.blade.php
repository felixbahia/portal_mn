@extends('layouts.page-dialog')

@section('content') 
<form action="" name="form_historico_adicionar" id="form_historico_adicionar" onsubmit="return false;">
    @csrf
    {!! Form::hidden('cliente', $cliente, []) !!}
    <div class="form-row">
        <div class="form-group col-12"> 
            {!! Form::label('contato', 'Contato', []) !!}
            {!! Form::text('contato', '', ['id' => 'contato', 'class' => 'form-control', 'placeholder' => 'Contato', 'maxlength' => '250']) !!}
        </div>
    </div>
    <div class="form-row">
        <div class="form-check ml-4 mt-2">
            {!! Form::checkbox('retorno_todos', 'sim', null, ['id' => 'retorno_todos', 'class' => 'form-check-input']) !!}
            {!! Form::label('retorno_todos', 'Usar o mesmo retorno para todos os titulos', ['class' => 'form-check-label']) !!}
        </div>
        <div class="form-group col-6"> 
            {!! Form::select('retorno_select', $retorno_possiveis, null, ['id' => 'retorno', 'class' => 'form-control', 'placeholder' => 'Retorno', 'readonly' => 'readonly', 'readonly' => 'readonly']) !!}
            {!! Form::text("observacao", '', ['id' => "observacao_totos", 'class' => 'form-control', 'placeholder' => 'Observação', 'maxlength' => '100']) !!}
        </div>                    
    </div>
    <div class="content-dialog-table">
        <table class="table table-striped table-filter" id="table-titulos-historico">
            <thead>
                <tr>
                    <th>Estabelecimento</th>
                    <th>Cliente</th>
                    <th>Titulo</th>
                    <th data-sort='YYYYMMDD' class="sort-date">Data de Emissão</th>
                    <th data-sort='YYYYMMDD' class="sort-date">Data de Vencimento</th>
                    <th class="tb_number">Valor Original</th>
                    <th class="tb_number">Valor (Saldo)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($titulos as $titulo)
                <tr>
                    <td>{!! $titulo['estabelecimento'] !!}</td>
                    <td>{!! $titulo['cliente'] !!}</td>
                    <td>{!! $titulo['titulo'] !!}</td>
                    <td>{!! $titulo['data_emissao'] !!}</td>
                    <td>{!! $titulo['data_vencimento'] !!}</td>
                    <td>{!! $titulo['valor_original'] !!}</td>
                    <td>{!! $titulo['valor_saldo'] !!}</td>
                    <td>
                        {!! Form::select("retorno[{$titulo['id']}]", $retorno_possiveis, null, ['id' => "retorno_{$titulo['id_campo']}", 'class' => 'form-control retorno_select', 'placeholder' => 'Retorno']) !!}
                        {!! Form::text("observacao[{$titulo['id']}]", '', ['id' => "observacao_{$titulo['id_campo']}", 'class' => 'form-control observacao_email', 'placeholder' => 'Observação', 'maxlength' => '100']) !!}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <button type="button" id="btn-salvar" class="btn btn-primary float-right">Adicionar</button>
</form>
<script>
    $retorno_observacao = {!! json_encode($retorno_observacao) !!};
    $(document).ready( function () {
        $(document).find("#form_historico_adicionar").find("#btn-salvar").off("click");
        $(document).find("#form_historico_adicionar").find("#btn-salvar").on("click", function(){
            salvarHistorico();
        });
        $(document).find("#form_historico_adicionar").find("#retorno_todos").off('change');
        $(document).find("#form_historico_adicionar").find("#retorno_todos").on('change', function(event){
            event.preventDefault();
            event.stopPropagation();
            habilitaTitulos(this);
        });
        $(document).find("#form_historico_adicionar").find("#retorno").off('change');
        $(document).find("#form_historico_adicionar").find("#retorno").on('change', function(event){
            event.preventDefault();
            event.stopPropagation();
            mudaRetornoTitulos(this);
        });
        $(document).find("#form_historico_adicionar").find(".retorno_select").off('change');
        $(document).find("#form_historico_adicionar").find(".retorno_select").on('change', function(event){
            event.preventDefault();
            event.stopPropagation();
            verificarObservacao(this);
        });
        $(document).find("#form_historico_adicionar").find("#observacao_totos").hide();
        $(document).find("#form_historico_adicionar").find("#observacao_totos").off('change');
        $(document).find("#form_historico_adicionar").find("#observacao_totos").on('change', function(event){
            event.preventDefault();
            event.stopPropagation();
            mudaObservacaoTitulos(this);
        });
        $(document).find("#form_historico_adicionar").find(".observacao_email").hide();
    });

    table_historico_titulos_opt = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": -1,
        "processing": true,
        "orderMulti": false,
        "autoWidth": false,
        "language": {
            "decimal":        ",",
            "thousands":      ".",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
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
			{ "class": "tb_number", "type": 'num-fmt', "targets": "tb_number" },
			{ "class": "tb_date", "type": "date", "targets": "sort-date" },
            { "targets": [0, 1, 2, 3, 4, 5, 6], 'width': '150px' },
            { "targets": [-1], "orderable": false, 'width': '200px' }
		],
		"order": [1, 'desc']
    };
    table_historico_titulo = $(document).find("#table-titulos-historico").DataTable(table_historico_titulos_opt);
    
    function salvarHistorico(){
        var form = $(document).find("#form_historico_adicionar");
        form.find('.error-message').remove();
        form.find('div, input, select').removeClass("error-input");
        $.ajax({
            url: '{{ route('historico_financeiro.adicionar') }}',
            type: 'POST',
            data: form.serialize(),
            success: function(callback){
                if(callback.status === "success"){
                    $(document).find('#form_historico_adicionar').parents(".modal").modal("hide");
                    message("Atenção", "Dados salvos com sucesso!");
                    buscaHistorico();
                } else {
                    message("Atenção", callback.message);
                }
            },
            error: function(callback){
                hide_loader();
                var errors = callback.responseJSON.error;
                form.find('.error-message').remove();
                form.find('div, input, select').removeClass("error-input");
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
                if(form.find('.error-message').length){
                    form.find('.error-message').eq(0).focus();
                }
            }
        });
    }

	function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"'],select[name='"+input+"'],textarea[name='"+input+"']");
        if($input.length == 0){
            var $input = $(form).find("#"+input+"");
        }
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('is-invalid');
        
    }
    
    function habilitaTitulos(campo){
        if($(campo).prop("checked") === true){
            $(document).find("#form_historico_adicionar").find(".retorno_select").attr("readonly", "readonly").val('');
            $(document).find("#form_historico_adicionar").find("#retorno").removeAttr("readonly").val('');
            $(document).find("#form_historico_adicionar").find("#observacao_totos").hide();
            $(document).find("#form_historico_adicionar").find(".observacao_email").hide();
        }
        else{
            $(document).find("#form_historico_adicionar").find(".retorno_select").removeAttr("readonly").val('');
            $(document).find("#form_historico_adicionar").find("#retorno").attr("readonly", "readonly").val('');
            $(document).find("#form_historico_adicionar").find("#observacao_totos").hide();
            $(document).find("#form_historico_adicionar").find(".observacao_email").hide();
            $(document).find("#form_historico_adicionar").find(".observacao_email").removeAttr("readonly").val('');
        }
    }

    function mudaRetornoTitulos(campo){
        if($(document).find("#form_historico_adicionar").find("#retorno_todos").prop("checked") === true){
            $(document).find("#form_historico_adicionar").find(".retorno_select option[value='"+$(campo).val()+"']").attr("selected","selected");
            if($.inArray($(campo).val(), $retorno_observacao) > -1){
                $(document).find("#form_historico_adicionar").find("#observacao_totos").show();
                if($(document).find("#form_historico_adicionar").find("#retorno_todos").prop("checked") === true){
                    $(document).find("#form_historico_adicionar").find(".observacao_email").show();
                    $(document).find("#form_historico_adicionar").find(".observacao_email").attr("readonly", "readonly").val('');
                }
            }else{
                $(document).find("#form_historico_adicionar").find("#observacao_totos").hide();
                $(document).find("#form_historico_adicionar").find(".observacao_email").hide();
                $(document).find("#form_historico_adicionar").find(".observacao_email").removeAttr("readonly").val('');
            }
        }
    }

    function mudaObservacaoTitulos(campo){
        if($(document).find("#form_historico_adicionar").find("#retorno_todos").prop("checked") === true){
            $(document).find("#form_historico_adicionar").find(".observacao_email").val($(campo).val());
        }
    }
    
    function verificarObservacao(campo){
        $id = $(campo).attr('id');
        id_observacao = "#"+$id.replace("retorno_", "observacao_");
        if($.inArray($(campo).val(), $retorno_observacao) > -1){
            $(document).find("#form_historico_adicionar").find(id_observacao).show();
        }else{
            $(document).find("#form_historico_adicionar").find(id_observacao).hide();
        }
    }
</script>
@endsection
