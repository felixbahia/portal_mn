@extends('layouts.page-dialog')

@section('content')
<div class="content-filter-dialog">	
    <form action="" name="form_pecas" id="form_pecas" onsubmit="return false;">
        @csrf
        {!! Form::hidden('filtro', $filtro, ['id' => 'filtro']) !!}
        <div class="form-row">
            <div class="form-group col-sm-12">
                {!! Form::label('codigo_peca', 'Peça', []) !!}
                {!! Form::text('codigo_peca', '', ['id' => 'codigo_peca', 'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
            <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
        </div>
    </form>
</div>
<div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped" id="table-filters-pecas">
            <thead>
                <th>Peça</th>
                <th class="tb_number">Quantidade</th>
                <th class="tb_unidade">Unidade</th>
                <th>Ajuste</th>
            </thead>
            <tbody>
                @foreach($lotes as $lote)
                    <tr>
                        <td>{{ $lote['peca'] }}</td>
                        <td class="tb_number">{{ $lote['quantidade'] }}</td>
                        <td class="tb_unidade">{{ $lote['unidade'] }}</td>
                        <td>{!! Form::text('ajuste-'.$lote['peca'], $lote['ajuste'], ['id' => 'ajuste-'.$lote['peca'], 'class' => 'form-control text-right number', 'style' => 'height: inherit', 'data-produto_lote' => $lote['produto_lote'], 'data-peca' => $lote['peca'], "data-valor_original" => $lote['quantidade'], 'onchange' => 'guardarValor($(this))']) !!}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>

            </tfoot>
        </table>
    </div>
</div>
<div class="col-sm-12 mt-5" id="button-bottom">
    {{ Form::button('Salvar', array('class' => 'btn btn-primary float-right', 'id' => 'btn-salvar_pecas')) }}
</div> 
<script>
    temp_obj = {};
    array_pecas = [];
    retorno_obj = {};
    
    @foreach($lotes as $lote)
        temp_obj['{{ $lote['peca'] }}'] = {
            produto_lote : "{{ $lote['produto_lote'] }}",
            peca : "{{ $lote['peca'] }}",
            quantidade : "{{ $lote['quantidade'] }}",
            ajuste : "{{ $lote['ajuste'] }}",
        };
        array_pecas.push("{{ $lote['peca'] }}");
    @endforeach
    $(document).ready( function () {
        initTable();
        $(document).find(".number").maskMoney({thousands:'', decimal:','});
        
        form_modal_busca = $(document).find("#form_pecas");
        form_modal_busca.find("#btn-filterform").off('click');
        form_modal_busca.find("#btn-filterform").on('click', function(){
            filter(form_modal_busca);
        });

        $(document).find("#btn-salvar_pecas").off('click');
        $(document).find("#btn-salvar_pecas").on('click', function(){
            salvarPecas(form_modal_busca);
        });
    });

    function initTable(){
        table_filters_pecas_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "orderMulti": false,
            "scrollCollapse": true,
            "scrollY": "45vh",
            "autoWidth": false,
            "drawCallback": function(settings) {
                $(document).find('.detalhe_produto').tooltip({
                    container: 'body',
                    html: true,
                    show: true,
                    trigger: 'manual'
                });
            },
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Produto inserido",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum Produto inserido",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { targets: 1, width: '250px'},
                { targets: 3, width: '450px'},
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "tb_unidade", targets: "tb_unidade", width: "150px"}
            ]
        };
        table_pecas = '';
        table_pecas = $(document).find('#table-filters-pecas').DataTable(table_filters_pecas_options);
        table_pecas.draw();
    }

    function filter(form_modal_busca){
        table_pecas.clear().draw();
        data_form_modal_busca = form_modal_busca.serialize();
        $.ajax({
            url: '{{ route('produto.ajuste_estoque.digitacao.filter_pecas')}}',
            data: data_form_modal_busca,
            method: 'POST',
            success: function(data){  
                linhas = [];
                for (var fields in data.response.lotes){
                    temp_array = [
                        data.response.lotes[fields].peca,
                        data.response.lotes[fields].quantidade,
                        data.response.lotes[fields].unidade,
                        inputAjuste(data.response.lotes[fields]),
                    ];
    
                    table_pecas.row.add(temp_array).draw();
                }
                $(document).find(".number").maskMoney({thousands:'', decimal:','});
            },
            error: function(data){
                var errors = data.responseJSON.error;
                for(var field in errors){

                }
            }
        });
    }

    function inputAjuste($value){
        var html = "<input id=\"ajuste-"+$value.peca+"\" class=\"form-control text-right number\" style=\"height: inherit\" data-produto_lote=\""+$value.produto_lote+"\" data-peca=\""+$value.peca+"\" data-valor_original=\""+$value.quantidade+"\" name=\"ajuste-"+$value.peca+"\" type=\"text\" value=\""+temp_obj[$value.peca]+"\" onchange=\"guardarValor($(this))\" autocomplete=\"off\">";

        return html;
    }

    function guardarValor($this){
        temp_obj[$this.data("peca")]['ajuste'] = $this.val();
    }

    function salvarPecas(form_modal_busca){
        retorno = {};
        array_pecas.forEach(function imprimir(item){
            if(temp_obj[item]['quantidade'] !== temp_obj[item]['ajuste']){
                retorno[item] = {
                    'produto_lote' : temp_obj[item]['produto_lote'],
                    'peca' : temp_obj[item]['peca'],
                    'quantidade' : temp_obj[item]['quantidade'],
                    'ajuste' : temp_obj[item]['ajuste'],
                }
            }
        });

        obj_pecas = retorno;

        $(form_modal_busca).parents('.modal').modal('hide');
    }
</script>
@endsection