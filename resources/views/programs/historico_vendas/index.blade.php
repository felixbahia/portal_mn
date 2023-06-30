@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}@if(Auth::user()->hasRole('Cliente')) - Cliente: {{ $cliente_nome }}@endif</h3>
    <div class="content-fields">
        @if(!Auth::user()->hasRole('Cliente'))
        <div class="form-group col-lg-3 col-xl-4">
            <div class="input-group">
                {{ Form::text('cliente_nome', CustomView::retornaClientePadraoNome(), ['id' => 'cliente_filtro', 'class' => 'form-control input-label', 'placeholder' => 'Nome do Cliente']) }}
                {{ Form::hidden('cliente', CustomView::retornaClientePadraoId(), ['id' => 'cliente_id_filtro', 'class' => 'form-control', 'placeholder' => 'Código do Cliente']) }}
                <span class="input-group-addon border rounded-right" id="bt-search-cliente-busca" data-route="{{ route("cliente.index.dialog") }}"><i id="bt-view-cliente" class="bt-view m-2"></i></span>
            </div>
        </div>
        @endif
		<div class="col-lg-2">
            {!! Form::text('data_inicio', '', ['id' => 'data_inicio', 'class' => 'form-control data', 'placeholder' => 'Data Início DD/MM/AAAA', 'maxlength' => '20']) !!}
		</div>
		<div class="col-lg-2">
            {!! Form::text('data_fim', '', ['id' => 'data_fim', 'class' => 'form-control data', 'placeholder' => 'Data Fim DD/MM/AAAA', 'maxlength' => '20']) !!}
		</div>
        <div class="col-lg-2">
            {!! Form::select('tipo_operacao', $tipo_operacao, '', ['tipo_operacao']) !!}
        </div>
        <div class="col-lg-2">
            <input type="text" name="numero_nota" id="numero_nota" value="" placeholder="Nº da nota">
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection

@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-notas">
        <thead>
            <tr>
                <th class="text_estabelecimento">Estabelecimento</th>
                <th class="text_string">Cliente</th>
                <th class="text_number">Nº da nota</th>
                <th>Tipo de Operação</th>
                <th class="text_date">Data de Emissão</th>
                <th class="text_date">Data de saída</th>
                <th class="text_number">Valor</th>
                <th class="text_string">Vendedor</th>
                <th class="td_acao">NF & Boleto</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('script-footer')
    
    $(document).ready(function (){

        $(document).find("#bt-search-cliente-busca").off("click");
        $(document).find("#bt-search-cliente-busca").on("click", function(event){
            event.stopPropagation();
            showModalClienteBusca($(this).data("route"));
            return false;
        });
        $(document).find("#cliente_filtro").autocomplete(optionsAutoCompleteClienteFiltro());

        $(document).find('#btn-filterform').on('click', function(){
            filterDados();
        });

		$('.data').mask('00/00/0000');
		$('.data').datepicker({
			language: 'pt-BR',
			format: 'dd/mm/yyyy',
			endDate: new Date(),
			zIndex: 100,
			autoHide: true
		});
		$('#data_inicio').on('pick.datepicker', function (e) {
			if($('#data_fim').datepicker('getDate') < e.date){
				$('#data_fim').val('');
			}
			$('#data_fim').datepicker('setStartDate', e.date);
			$('#data_fim').datepicker('update');
		});
		carregarData();

        $(document).find(".btn-clear").on("click", function(){
            $form = $(this).parents('form');
            $.ajax({
                url: '{{ route('cliente.apagaClientePadrao') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(){
                    $form.find('input').not('[class^=btn-]').not('[name=data]').not('[name=_token]').val('');
                    $form.find('select').val($form.find('select').find('option').eq(0).val());
                    $form.find('input[name=data]').val('{{ date("m/Y") }}');
                }
            });
            
        });
    });

    function showModalClienteBusca(url){
        var title = "Busca de Clientes";
        $.ajax({
            url: url,
            method: 'GET',
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
        $(document).find("#cliente_filtro").val($dados.find("td").eq(1).text() + " - " +$dados.find("td").eq(3).text());
        $(document).find("#cliente_searsh_show").modal("hide");
        $.ajax({
            url: '{{ route('cliente.salvaClientePadrao') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                codcad: $dados.find("td").eq(0).text()
            }
        });
        filterDados();
    }
    function optionsAutoCompleteClienteFiltro(){
        $(document).find(".error-message").remove();
        return {
            source: function (request, response) {
                request._token = "{{ csrf_token() }}";
                request.busca_pedido = true;
                request.bloqueado = false;
                $.post("{{ route('clientes.autocomplete') }}", request, response);
            },
            delay: 700,
            minLength: 2,
            open: function( event, ui ){
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
                $(document).find("#cliente_id_filtro").val(ui.item.value);
                $(document).find("#cliente_filtro").val(ui.item.label);
                $.ajax({
                    url: '{{ route('cliente.salvaClientePadrao') }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        codcad: ui.item.value
                    }
                });
                filterDados();
                return false;
            }
        };
    }
    function filterDados(){
        form_filter = $('#form_filter');
        var argumentos = form_filter.serialize();
        
        $.ajax({
            url: '{{ route('historico_vendas.filtro') }}',
            type: 'POST',
            data: argumentos,
            success: function(data){
                var result_array = [];
                table_filters.clear().draw();
                for (var field in data){
                    var temp_array = [
                        data[field].estabelecimento_nome,
                        ajusteTamanhoTable(data[field].cliente),
                        createLinkNf(data[field]),
                        data[field].tipo_operacao,
                        data[field].dtemis,
                        data[field].dtsaida + ' ' + createBtEntrega(data[field]),
                        data[field].valtotdoc,
                        data[field].nome_vendedor,
                        createBtView(data[field])
                    ];
                    result_array.push(temp_array);
                }
                table_filters.rows.add(result_array).draw();
            }
        });
    }
	function carregarData(){
		var d = new Date();
		var anoC = d.getFullYear();
		var mesC = d.getMonth();
	
		var d1 = new Date (anoC, mesC, 1);
		var d2 = new Date (anoC, mesC+1, 0);
		$('#data_inicio').val(dataAtualFormatada(d1));
		$('#data_fim').val(dataAtualFormatada(d2));
	}
	function dataAtualFormatada(data){
			dia  = data.getDate().toString().padStart(2, '0'),
			mes  = (data.getMonth()+1).toString().padStart(2, '0'), //+1 pois no getMonth Janeiro começa com zero.
			ano  = data.getFullYear();
		return dia+"/"+mes+"/"+ano;
	}

    table_filters = $('#table-filters-notas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "pageLength": 15,
        "orderMulti": false,
        "dom": 'Bfrtip',
        "buttons": [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
							if(column === 6){
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
                },
            },
        ],
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
            }        },
        "columnDefs": [
            {
                "targets": "text_number",
                "className": 'number_format'
            },
            {
                "targets": "text_date",
                "className": 'date_format'
            },
            {
                "targets": "text_estabelecimento",
                "width": '15%'
            },
            {
                "targets": "text_string",
                "width": '30%'
            },
            {
                "targets": ["text_date", "td_acao"],
                "width": '5%'
            }
        ],
        "order": [[ 3, 'desc' ]]
    });

    function showNotasDetalhes(estabelecimento, nota_fiscal, data){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                documento: nota_fiscal,
                link_pedido: true,
                data: data,
                origem: 'PROLOGOS'
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }

    function createLinkNf($this){
        var html = "";
        if($this.numero_nota !== ""){

            if($this.origem == 'prologos'){
                html = "<a href='#' onclick=\"showNotasDetalhes('" + $this.estabelecimento + "', '" + $this.num_doc + "', '" + $this.dt_doc + "')\">" + $this.numnfe + "</a>";
            }

            else if($this.origem == 'nasajon'){
                html = "<a href='#' onclick=\"showNotasDetalhesNasajon('" + $this.id_nota + "')\">" + $this.numnfe + "</a>";
            }

            else{
                html = $this.numero_nota;
            }
        }

        return html;
    }

    function createBodyPopOver($this){
        var $return = "";
        $return = "<img src='"+$this+"' width='250' class='rounded mx-auto d-block' alt='Canhoto'>";
        return $return;
    }

    function showNotasDetalhesNasajon($id_nota){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
                link_pedido: true,
                origem: 'NASAJON'
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                })

            }

        });
    }

    function createBtView($this){
        if($this.origem == 'nasajon'){
            var html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' title='Ver NF & Boleto' onclick=\"verDocumentos('"+$this.id_nota+"');\"></a>";
        }
        else{
            html = '';
        }

        return html;
    }

    function createBtEntrega($this){
        if($this.dtsaida.length > 0 && $this.nota_id_ocorrencia === true){
            var html = "<a href=\"#\" class=\"bt-entrega\" data-toggle='tooltip' data-html='true' title='Ocorrência de Entrega' onclick=\"verOcorrencia('"+$this.id_nota+"');\"></a>";
        }else{
            html = '';
        }
        return html;
    }

    function verDocumentos($id_nota){

        $.ajax({
            url: '{{ route('notas_nasajon.modal.documentos')}}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_nota: $id_nota
            },
            success: function(body){
                createModal("nota_documentos", "Documentos da DANFE", body, '');
            }
        });

    }


    function verOcorrencia($id_nota){
        $.ajax({
            url: '{{ route('ocorrencia_entrega.modal.abertura')}}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id_nota: $id_nota
            },
            success: function(body){
                createModal("ocorrencias_de_entrega", "Ocorrência de Entrega", body, 'modal-lg');
            }
        });

    }

    function ajusteTamanhoTable($value){
        $html = "<div><div data-toggle='tooltip' data-html='true' data-placement='right' title='"+$value+"'>"+$value+"</div></div>";

        return $html;
    }

@endsection
