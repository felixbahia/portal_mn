@extends('layouts.app')

@section('content-filter')

<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <input type="hidden" name="grupo_exato" id="grupo_exato" value=""/>
    <input type="hidden" name="subgrupo_exato" id="subgrupo_exato" value=""/>
    <h3>{{ CustomView::programaName() }}</h3>
        <div class="content-fields">
            <div class="row">
                <div class="col-sm-2">
                    {{ Form::text('descricao', '', ['id' => 'descricao', 'placeholder' => 'Descrição', 'maxlength' => '250']) }}
                </div> 
                <div class="col-sm-2">
                    {{ Form::text('marca', '', ['id' => 'marca', 'placeholder' => 'Marca', 'maxlength' => '250']) }}
                </div>
                <div class="col-sm-2">
                    {{ Form::text('linha', '', ['id' => 'linha', 'placeholder' => 'Linha', 'maxlength' => '250']) }}
                </div>
                <div class="col-sm-2">
                    {{ Form::text('grupo', '', ['id' => 'grupo', 'placeholder' => 'Grupo', 'maxlength' => '250']) }}
                </div>
                <div class="col-sm-2">
                    {{ Form::text('subgrupo', '', ['id' => 'subgrupo', 'placeholder' => 'Subgrupo', 'maxlength' => '250']) }}
                </div>                
                <div class="col-sm-2">
                    {{ Form::text('codigo_produto', '', ['id' => 'codigo_produto', 'placeholder' => 'Código de produto', 'maxlength' => '250']) }}
                </div>                                        
                
                <div class="col-sm-2">
                    {{ Form::text('margem_abaixo', '', ['id' => 'margem_abaixo', 'placeholder' => 'Margem abaixo de', 'maxlength' => '250']) }}
                </div>
                <div class="col-sm-2">
                    {{ Form::text('margem_acima', '', ['id' => 'margem_acima', 'placeholder' => 'Margem acima de', 'maxlength' => '250']) }}
                </div>
                <div class="col-sm-2">
                    {{ Form::select('status', ['true' => 'Ativo', 'false' => 'Inativo'], 'true', ['id' => 'status']) }}
                </div>
            </div>
        </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        <button name="btn-create" id="btn-create" class="btn-create">Atualização em massa</button>
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-precos">
        <thead>
            <tr>
                <th class='marca' rowspan='2'>Marca</th>
                <th class='grupo' rowspan='2'>Grupo</th>
                <th class='subgrupo' rowspan='2'>Subgrupo</th>
                <th class='linha' rowspan='2'>Linha</th>
                <th class="total_itens number_format" rowspan='2'>Itens</th>
                <th class="codigo_produto" rowspan='2'>Cód. prod.</th>
                <th class="descricao" rowspan='2'>Descrição</th>
                <th colspan='2'>Preços</th>
                <th colspan='2'>Última compra</th>
                <th colspan='2'>Margens</th>
                <th class='botao' rowspan='2'></th>
                <th class='botao' rowspan='2'></th>
            </tr>
            <tr>
                <th class='number_format'>Real</th>
                <th class='number_format'>Dólar</th>
                <th class='number_format'>Real</th>
                <th class='number_format'>Dólar</th>
                <th class='number_format'>Real</th>
                <th class='number_format'>Dólar</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    $(document).ready( function () {
        $("#grupo").on('change', function(){
            $("#grupo_exato").val('')
        });

        $("#subgrupo").on('change', function(){
            $("#subgrupo_exato").val('')
        });

        $('#margem_abaixo').maskMoney({thousands:'.', decimal:','});
        $('#margem_acima').maskMoney({thousands:'.', decimal:','});

        $('#btn-filterform').on('click', function(){
            filterAjax($('#form_filter').serialize(), false);
        });

        $("#nome").autocomplete(optionsAutoComplete("nome"));
        $("#marca").autocomplete(optionsAutoCompleteMarca());
        $("#grupo").autocomplete(optionsAutoCompleteGrupo());
        $("#subgrupo").autocomplete(optionsAutoCompleteSubgrupo());
        $("#linha").autocomplete(optionsAutoCompleteLinha());
        $("#descricao").autocomplete(optionsAutoComplete("nome"));

        table_filters.on('draw', function () {
            $('[data-toggle="tooltip"]').tooltip('hide');
            $('[data-toggle="tooltip"]').tooltip();
        });

        table_filters.columns('.codigo_produto').visible(false);
        table_filters.columns('.descricao').visible(false);

        $(document).find("#btn-create").on("click", function(){
            modalAtualizacaoMassa();
        });

	});

    function optionsAutoComplete($name, element = null){

        return {
            source: function (request, response) {
                request.name = $name;
                request._token = "{{ csrf_token() }}";
                request.term = request.term.toLowerCase(); 
                $.post("{{ route('produto.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 3,
            open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($('.modal').css('z-index')) + 1));
        	},
            select: function( event, ui ) {
                $("#" + $name + "_exato").val(true);
                setTimeout(function(){
                    filterAjax($('#form_filter').serialize(), false);
                }, 100);
            }
        };
    }

    function optionsAutoCompleteGrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.grupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteSubgrupo(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.subgrupo.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteLinha(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.linha.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

	function optionsAutoCompleteMarca(){
		return {
			source: function (request, response) {
				request._token = "{{ csrf_token() }}";
				$.post("{{ route('produto.marca.autocomplete') }}", request, response);
			},
			delay: 700,
			minLength: 3,
			open: function( event, ui ){
				$('.ui-autocomplete').css("z-index", (parseInt($(document).find('#modal_adicionar').css('z-index')) + 1));
			},
			select: function( event, ui ) {
				setTimeout(function(){
					table_filters.draw();
				}, 100);
			}
		};
	}

    table_filters = $('#table-filters-precos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "language": {
            "decimal":        ",",
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
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
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '{{ CustomView::programaName() }}',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                autoFilter: true,
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    columns: ':visible',
                    format: {
                        body: function ( data, row, column, node ) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 5 || column === 6 || column === 7 || column === 8){
                                if(data != ''){
                                    numero = data.replace('.','').replace(',','');
                                    inteiro = Math.floor(numero.length - 2);
                                    decimal = Math.floor(numero.length);
                                    data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                }else{
                                    data = '';
                                }
                            }
                            return data;
                        }
                    }
                }
            },
        ],
        "columnDefs": [
            {
                'targets': 'number_format',
                "className": 'number_format',
            },
            {
                'targets': 'nome',
                'width': '20%'
            },
            {
                'targets': 'grupo_th',
                'className': 'text-center grupo'
            },
            {
                'targets': 'botao',
                'className': 'text-center botao'              
            }
        ],
        "order": [[ 1, 'asc' ], [2, 'asc']]
    });

    function showErrorsInputs(form, input, message){
        
        var $input = $(form).find("input[name='"+input+"'], select[name='"+input+"']");
        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');

    }

    function filterAjax(data_form, voltaPagina = true){
        var $return;
        var page = table_filters.page.info().page;
        table_filters.clear().draw();
        $('[data-toggle="tooltip"]').tooltip('hide');
	    var form = $("#form_filter");

        form.find('.error-message').remove();
        form.find('input, select').removeClass('error-input');
        $.ajax({
            url: "{{ route('atualizacao_preco.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(data){

                if(data.mostrar_codigo){
                    table_filters.columns('.codigo_produto').visible(true);
                    table_filters.columns('.descricao').visible(true);
                }
                else{
                    table_filters.columns('.codigo_produto').visible(false);
                    table_filters.columns('.descricao').visible(false);
                }
                    
                var linhas = data.linhas;

                if(linhas.length > 0){

                    var fields_filter = [];

                    for(var field in linhas){

                        var temp_field = [
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].marca+"\">"+linhas[field].marca+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].grupo+"\">"+linhas[field].grupo+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].subgrupo+"\">"+linhas[field].subgrupo+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].linha+"\">"+linhas[field].linha+"</div></div>",
                            criarLinkProdutos(linhas[field].total_itens, linhas[field].hash_precos),
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].codigo_produto+"\">"+linhas[field].codigo_produto+"</div></div>",
                            "<div><div data-toggle=\"tooltip\" data-html=\"true\" title=\""+linhas[field].descricao+"\">"+linhas[field].descricao+"</div></div>",
                            linhas[field].preco_real,
                            linhas[field].preco_dolar,
                            linhas[field].compra_real,
                            linhas[field].compra_dolar,
                            linhas[field].margem_real,
                            linhas[field].margem_dolar,
                            criarBotaoEdicao(linhas[field].hash),
                            criarBotaoEspecificacoes(linhas[field].hash)
                        ];

                        fields_filter.push(temp_field);

                    }

                    table_filters.rows.add(fields_filter).draw();
                    table_filters.columns.adjust().draw();
                    table_filters.draw();
                    if(voltaPagina == true){
                        table_filters.page( page ).draw( 'page' );
                    }

                    var filtro = [];

                }
                $('[data-toggle="tooltip"]').tooltip();
            },
            error: function(data){
                hide_loader();
                if((data.responseJSON.errors)){
                    var errors = data.responseJSON.errors;
                    form.find('.error-message').remove();
                    for(var field in errors){
                        showErrorsInputs(form, field, errors[field])
                    }
                }else{
                    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamente mais tarde!");
                }
            }
        });
    }

    function criarBotaoEdicao($hash){
        var html = "<a href=\"#\" class=\"bt-edit-money\" title='Editar preço' data-hash='"+$hash+"' onclick=\"modal($(this).data('hash'))\"></a>";

        return html;
    }

    function criarBotaoEspecificacoes($hash){
        var html = "<a href=\"#\" class=\"bt-edit\" title='Editar especificações' data-hash='"+$hash+"' onclick=\"modalEspecificacoes($(this).data('hash'))\"></a>";

        return html;
    }

    function criarLinkProdutos($total_itens, $hash){
        var html = "<a href=\"#\" title='Produtos contidos' data-hash='"+$hash+"' onclick=\"modalProdutos($(this).data('hash'))\">" + $total_itens + "</a>"
        
        return html;
    }

    function modal($hash){
        $.ajax({
            url: '{{ Route("atualizacao_preco.modal.editar") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            success: function(data){
                createModal('modal_editar_precos', 'Editar preços', data, 'modal-lg');
            }
        });
    }

    function modalProdutos($hash){
        $.ajax({
            url: '{{ Route("atualizacao_preco.modal.produtos") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            success: function(data){
                createModal('modal_produtos', 'Produtos contidos neste agrupamento', data, 'modal-lg');
            }
        });
    }

    function modalEspecificacoes($hash){
        $.ajax({
            url: '{{ Route("atualizacao_preco.modal.edicao_especificacoes") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                hash: $hash
            },
            success: function(data){
                createModal('modal_especificacoes', 'Editar especificações', data, 'modal-lg');
            }
        });
    }

    function modalAtualizacaoMassa() {
        $.ajax({
            url: "{{ route("atualizacao_preco.modal.atualizacao_massa") }}",
            data: {_token: "{{ csrf_token() }}"},
            method: "POST",
            success: function(body){
                createModal("modal_atualizacao_massa", 'Atualização em massa', body, "modal-lg");
            },
            error: function(callback){
                if(callback.status === 422){
                    if(callback.responseJSON.error){
                        message("Alerta", callback.responseJSON.error.msg.user);
                    }else{
                        message("Atenção", "Ocorreu uma instabilidade contate o setor responsavel.");
                    }
                }else{
                    window.location.reload();
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
@endsection