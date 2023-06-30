@extends("layouts.app")
@section("content-filter")
<form action="#" name="form_filter_pedidos_pedidos" id="form_filter_pedidos" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="filtro_colapsado">
        <div class="content-fields">
            <div class="row">
                <div class="form-group col-lg-2 col-xl-3">
                    {{ Form::select("estabelecimento", $estabelecimentos, "", ["id" => "estabelecimento_filtro", "class" => "form-control", "placeholder" => "Estabelecimento"]) }}
                </div>
                <div class="form-group col-sm-6 col-xl-3">
                    {{ Form::text("nome_campanha", "", ["id" => "nome_campanha", "class" => "form-control", "placeholder" => "Nome da Campanha"]) }}
                </div>
                <div class="form-group col-lg-2 col-xl-3">
                    {{ Form::text("data_inicio", date("d/m/Y"), ["id" => "data_inicio", "class" => "form-control data", "placeholder" => "Início do período"]) }}
                </div>
                <div class="form-group col-lg-2 col-xl-3">
                    {{ Form::text("data_fim", date("d/m/Y"), ["id" => "data_fim", "class" => "form-control data", "placeholder" => "Fim do período"]) }}
                </div>
            </div>
        </div>
    </div>
    <div class="filtro-linha">
        <div id="row">
            <div class="col-lg-8" id="filtros-show-param">
            </div>
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create-campanha" id="btn-create-campanha" class="btn-create">Nova Campanha</button>
    </div>
</form>	
@endsection
@section("content")
<div class="content-table">
	    <table class="table table-striped table-filter-pedido-portal table-not-edit table-not-view" id="table-filters-campanha">
        <thead>
            <tr>
                <th>Nome</th>
                <th class="tb_date">Criação</th>
                <th class="tb_date">Início</th>
                <th class="tb_date">Fim</th>
                <th class="tb_number">Com(%). Repres.</th>
                <th class="tb_number">Com(%). vend. Interno</th>
                <th class="tb_number">Com(%). Gerentes</th>
                <th class="ativo">Ativo</th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
                <th class="td_acao"></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section("script-footer")
    table_filters_campanha_setup = {
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": false,
        "pageLength": 15,
        "processing": true,
        "orderMulti": false,
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
                "targets": "tb_date",
                "className": "date_format",
                "width": "70px"
            },
            {
                "class": "tb_number", 
                type: "num-fmt", 
                targets: "tb_number", 
                width: "70px" 
            },
            {
                "targets": "td_acao",
                "class": "td_acao",
                "width": "5px",
                "orderable": false
            },
            {
                "targets": "ativo",
                "class": "ativo",
                "width": "10px",
                "orderable": false
            }
        ]
    };

    table_filters_campanha = $("#table-filters-campanha").DataTable(table_filters_campanha_setup);

	$(document).ready( function () {
        $(document).find("#bt-search-cliente-busca").off("click");

        $(document).find(".data").mask("00/00/0000");
        $(document).find(".data").datepicker({
            language: "pt-BR",
            format: "dd/mm/yyyy",
            zIndex: 100,
            autoHide: true
        });

        $(document).find("#btn-create-campanha").on("click", function(){
            showModalCriarCampanha();
        });

        $(document).find("#btn-filterform").on("click", function(){
            filterAjax($(document).find("#form_filter_pedidos").serialize());
        });

		$('#framework').multiselect({
			nonSelectedText: 'Select Framework',
			enableFiltering: true,
			enableCaseInsensitiveFiltering: true,
			buttonWidth:'400px'
		});
    });

    function showModalCriarCampanha() {
        chamadaModalCampanha();
    }

    function filterAjax(data_form){
        var form = $(document).find('#table-filters-campanha');
        table_filters_campanha.clear().draw();

        $.ajax({
            url: "{{ route("campanha.filtro") }}",
            dataType: "json",
            data: data_form,
            method: "POST",
            success: function(response){
                var fields_filter = [];
                data = response.response.retorno;

                for(var field in data){
                    var temp_field = [
                        "<div><div data-toggle='tooltip' data-html='true' title='' data-placement='right' data-original-title='" + data[field].nome + "''>" + data[field].nome + "</div></div>",
                        data[field].criacao,
                        data[field].inicio_campanha,
                        data[field].fim_campanha,
                        data[field].comissao_representante,
                        data[field].comissao_vendedor_interno,
                        data[field].comissao_gerente,
                        createBtAtivo(data[field]),
                        createBtViewCampanha(data[field]),
                        createBtEditCampanha(data[field]),
                        createBtDeleteCampanha(data[field]),
                        createBtExportarCampanha(data[field]),
                    ];
                    fields_filter.push(temp_field);
                }
                table_filters_campanha.rows.add(fields_filter).draw().nodes();
            },
            error: function(callback){
                if(callback.responseJSON.error){
                    var errors = callback.responseJSON.error;
    
                    form.find(".error-message").remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }else{
                    message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                }
            }
        });
    }

    function createBtAtivo($this){
        html = "";
        if ($this.ativo === true){
            html = "<center><i class=\"fa fa-check check-icon\" aria-hidden=\"true\"></i></center>";
        }else{
            html = "<center><i class=\"fa fa-times error-icon\" aria-hidden=\"true\"></i></center>";
        }
        return html;
    }

    function createBtViewCampanha($this){
        html = "<a href=\"#\" class=\"bt-view\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Visualizar\" onclick=\"viewModalCampanha('"+$this.id+"')\"></a>";
        
        return html;
    }

    function createBtExportarCampanha($this){
        html = '<div class="dt-buttons"><a data-toggle="tooltip" data-html="true" title="Exportar em Excel" class="btn btn-secondary buttons-excel buttons-html5" onclick="gerarExportacao(\''+$this.nome+'\')"  style="margin-top: -25px" tabindex="0" aria-controls="table-filters-campanha" type="button"></a> </div>';
        
        return html;
    }

    function viewModalCampanha($id){
        $.ajax({
            url: "{{ route("campanha.consultar") }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                id: $id
            },
            success: function(data){
                $id = "view-consulta-campanha";
                $title = "Detalhes da Campanha"; 
                $body = data;
                $class = "modal-lg";
                createModal($id, $title, $body, $class);
            }
        });
    }

    function createBtEditCampanha($this){
        html = '';
        if ($this.editar === true){
            html = "<a href=\"#\" class=\"bt-edit\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Editar\" onclick=\"showModalEditarCampanha('"+$this.id+"')\"></a>";
        }

        return html;
    }

    function showModalEditarCampanha($id) {
        $.ajax({
            url: "{{ Route("campanha.modal.editar") }}",
            type: "POST",
            data: {
                _token: "{{csrf_token()}}",
                id: $id
            },
            success: function(data){
                createModal("modal_campanha_editar", "Editar Campanha", data, "modal-lg");
            }
        });
        
    }

    function createBtDeleteCampanha($this){
        var html = "";

        if ($this.excluir === true){
            var html = "<a href=\"#\" class=\"bt-delete\" data-toggle=\"tooltip\" data-html=\"true\" title=\"Desativar\" onclick=\"ExcluirCampanha('"+$this.nome+"','"+$this.id+"')\"></a>";
        }

        return html;
    }

    function ExcluirCampanha($nome,$id){
        var $class = "dialog_option_deletar";
		var $name_option_sim = "aprovar_exclusao_campanha_sim";
		var $option_sim = "Sim";
		var $name_option_nao = "aprovar_exclusao_campanha_nao";
		var $option_nao = "Não";
		message_sim_nao("Atenção", "Finalizar a campanha '"+$nome+"' ? \n (Isso irá desvincular todos os produtos da campanha)", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);
		
        $(document).off("aprovar_exclusao_campanha_nao");
		$(document).on("aprovar_exclusao_campanha_nao", function(){
			return false;
		});

        $(document).off("aprovar_exclusao_campanha_sim");
		$(document).on("aprovar_exclusao_campanha_sim", function(){
            $.ajax({
                url: "{{ Route("campanha.excluir") }}",
                type: "POST",
                data: {
                    _token: "{{csrf_token()}}",
                    id: $id
                },
                success: function(data){
                    if(data.status == 'success'){
                        message('Atenção','<div class="text-success">'+data.message+'</div>');
                        filterAjax($(document).find("#form_filter_pedidos").serialize());
                    }else{
                        message('Atenção','<div class="text-danger">'+data.message+'</div>');
                        filterAjax($(document).find("#form_filter_pedidos").serialize());
                    }
                },
                error: function(callback){
                    message('Atenção','<div class="text-danger">Algo deu errado, por favor contate o responsável.</div>');
                }
            });
        });
    }
    
    function chamadaModalCampanha(){
        $.ajax({
            url: "{{ route("campanha.modal.inserir") }}",
            data: {_token: "{{ csrf_token() }}"},
            method: "GET",
            success: function(body){
                createModal("modal_campanha_inserir", 'Inserir Nova Campanha', body, "modal-lg");
            },
            error: function(callback){
                message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
            }
        });
    }

    function showErrorsInputs(form, input, message){
        var $input = $(form).find("input[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');

        var $select = $(form).find("select[name='"+input+"']");
        $select.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $select.addClass('error-input');
    }

    
	function gerarExportacao(nome){
        $('<form action="{{ route('campanha.export_excel') }}" method="POST" target="_blank">\
			<input type="hidden" name="_token" value="{{ csrf_token() }}">\
			<input type="hidden" name="nome" value="'+nome+'"/>\
		</form>').appendTo('body').submit().remove();   
    }

@endsection
