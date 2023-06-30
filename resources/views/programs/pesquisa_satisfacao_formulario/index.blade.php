@extends("layouts.app")
@section("content-filter")
<form action="#" name="form_filter_pedidos_pedidos" id="form_filter_pedidos" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <button name="btn-create" id="btn-create" class="btn-create">Adicionar Nova Questão</button>
    </div>
</form>	
@endsection
@section("content")
<div class="content-table">
    <table class="table table-striped table-filter-pedido-portal table-not-edit table-not-view" id="table-pesquisa-satisfacao-formulario">
        <thead>
            <tr>
                <th class="td_pergunta">Pergunta</th>
                <th class="tb_number">Ordem</th>
                <th>Tipo</th>
                <th class="tb_btn">Editar</th>
                <th class="tb_btn">Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section("script-footer")
    table_filters_pesquisa = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
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
            {
                "targets": 'td_pergunta',
                 width: '45%',
            },
            {
                "targets": 'tb_btn',
                 width: '5%',
            },
            {
                "class": "tb_number",
                'targets': "tb_number",
            },
        ]
    };

	$(document).ready( function () {
        table_filters_formulario_pesquisa = $("#table-pesquisa-satisfacao-formulario").DataTable(table_filters_pesquisa);
        table_filters_formulario_pesquisa.on('draw', function () {
            $(document).find(".btn-create").off("click");
            $(document).find(".btn-create").on("click", function(event){
                event.stopPropagation();
                showModalInserirQuestao();
            });
            $(document).find(".bt-edit").off("click");
            $(document).find(".bt-edit").on("click", function(event){
                event.stopPropagation();
                showModalEditarQuestao($(this));
            });
        });
        $(document).find("#btn-filterform").on("click", function(){
            filtroAjax();
        });
        filtroAjax();
    });

    function filtroAjax(){
        $.ajax({
            url: "{{ route("pesquisa_satisfacao_formulario.carrega_formulario") }}",
            dataType: "json",
            data: '',
            method: "GET",
            success: function(data){
                var retorno = data.response.dados;
                table_filters_formulario_pesquisa.clear().draw();
                if(retorno.length > 0){
                    var fields_filter = [];
                    for(var field in retorno){
                        var temp_field = [
                            "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + retorno[field].pergunta + "''>" + retorno[field].pergunta + "</div></div>",
                            retorno[field].ordem_pergunta,
                            "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + retorno[field].tipo_resposta + "''>" + retorno[field].tipo_resposta + "</div></div>",
                            createBtEdit(retorno[field]),
                            createBtDelete(retorno[field])
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters_formulario_pesquisa.rows.add(fields_filter).draw().nodes();
                }
            },
            error: function(callback){
                if(callback.responseJSON.errors){
                    var errors = callback.responseJSON.errors;
    
                    form.find(".error-message").remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }else{
                    message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                }
            },
            statusCode: {
                409: function() {
                    window.location.reload();
                },
                419: function() {
                    window.location.reload();
                }
            }
        });
    }

    function createBtEdit($this){
        var html = "";
        html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" title=\"Editar\" data-id=\""+$this.id+"\"></a>";
        return html;
    }

    function createBtDelete($this){
        var html = "";
        var html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Excluir\" onclick=\"escolhaExcluir('"+$this.pergunta+"', '"+$this.id+"')\"></a>";
        return html;
    }

    function escolhaExcluir(pergunta,id){
        var $class = "dialog_option_deletar";
        var $name_option_sim = "excluir_questao_sim";
        var $option_sim = "Sim";
        var $name_option_nao = "excluir_questao_nao";
        var $option_nao = "Não";

        message_sim_nao("Atenção", "Deseja excluir esta pergunta '"+pergunta+"'?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);
        
        $(document).off("excluir_questao_nao");
        $(document).on("excluir_questao_nao", function(){
            return;
        });

        $(document).off("excluir_questao_sim");
        $(document).on("excluir_questao_sim", function(){
            modalExcluirQuestao(id);
        });
    }

    function modalExcluirQuestao($id){
        $.ajax({
            url: "{{ Route("pesquisa_satisfacao_formulario.excluir_questao") }}",
            type: "POST",
            data: {
                _token: "{{csrf_token()}}",
                id: $id
            },
            success: function(data){
                message("Atenção", data.message);
                filtroAjax();
            }
        });
    }

    function showModalInserirQuestao(){
        $.ajax({
            url: "{{ route('pesquisa_satisfacao_formulario.modal.adicionar_questao') }}",
            method: 'GET',
            data: '',
            success: function(body){
                createModal('modal_formulario_pesquisa_adicionar', 'Gravar Nova Qestão', body, '');
            }
        });
    }

    function showModalEditarQuestao($this){
        var $id = $($this).data("id");
        $.ajax({
            url: "{{ route('pesquisa_satisfacao_formulario.modal.editar_questao') }}",
            method: 'POST',
            data: {_token: "{{ csrf_token() }}", id: $id},
            success: function(body){
                createModal('modal_formulario_pesquisa_editar', 'Editar Qestão', body, '');
            }
        });
    }
    
@endsection