@extends('layouts.page-dialog')

@section('content')
<div class="content-table-ajax">
    <table class="table table-striped table-exportFilter" id="table-lancamentos">
        <thead>
            <tr>
                <th>Cod. Cad.</th>
                <th>Nome Fornecedor</th>
                <th>N. Documento</th>
                <th>Cod. Bco.</th>
                <th>Nome Bco.</th>
                <th>Agência Bco.</th>
                <th>Conta Bco.</th>
                <th>Valor Lançamento</th>
                <th>Data Lançamento</th>
                <th>Status</th>
                <th>Conta crédito</th>
                <th>Conta débito</th>
                @if(intval($status) === 1)
                <th>Editar</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $key => $value)
            <tr>
                <td class="tb_number">{!! $value["codcad"] !!}</td>
                <td>{!! $value["nome_fornecedor"] !!}</td>
                <td class="tb_number">{!! $value["ndoc"] !!}</td>
                <td class="tb_number">{!! $value["codbco"] !!}</td>
                <td>{!! $value["nome_banco"] !!}</td>
                <td class="tb_number">{!! $value["agencia_banco"] !!}</td>
                <td class="tb_number">{!! $value["conta_banco"] !!}</td>
                <td class="tb_number">{!! $value["valor_baixa"] !!}</td>
                <td class="tb_date">{{ date("d/m/Y", strtotime($value["datahora_baixa"])) }}</td>
                <td>{!! $value["status_texto"] !!}</td>
                <td class="tb_number">
                    @if(empty($value["contactb_credito"]))
                    <input type="text" name="contactb_credito" id="contactb_credito" data-row="{{ $value["id"] }}" data-lote_id="{{ $value["lote_id"] }}" value="{{ $value["contactb_credito"] }}" />
                    @else
                    {{ $value["contactb_credito"] }}
                    @endif
                </td>
                <td class="tb_number">
                    @if(empty($value["contactb_debito"]))
                    <input type="text" name="contactb_debito" id="contactb_debito" data-row="{{ $value["id"] }}" data-lote_id="{{ $value["lote_id"] }}" value="{{ $value["contactb_debito"] }}" />
                    @else
                    {{ $value["contactb_debito"] }}
                    @endif
                </td>
                @if(intval($status) === 1)
                <td>
                    @if((empty($value["contactb_debito"]) || empty($value["contactb_credito"])) && intval($value["status"]) === 1 )
                    <button name="bt-salvar" id="bt-salvar" class="bt-salvar">Salvar</button>
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<script>
    $('[data-toggle="tooltip"]').tooltip();
    var table_lancamentos = $("#table-lancamentos").DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        //"responsive": true,
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
            {
                "targets": ($('#table-lancamentos thead th').length - 1),
                "orderable": false
            }
        ]
    });
    $(document).find(".bt-salvar").off("click");
    $(document).find(".bt-salvar").on("click", function(event){
        event.stopPropagation();
        sendInputsSalvar($(this).parents("tr"));
    });
    table_lancamentos.on('draw', function () {
        $(document).find(".bt-salvar").off("click");
        $(document).find(".bt-salvar").on("click", function(event){
            event.stopPropagation();
            sendInputsSalvar($(this).parents("tr"));
        });
    });
    function sendInputsSalvar(row){
        var input_debito = $(row).find("#contactb_debito");
        var input_credito = $(row).find("#contactb_credito");
        var id = 0;
        var lote_id = 0;
        if(input_debito.length){
            if(input_debito.val() == "0" || input_debito.val() == ""){
                input_debito.focus();
                return false;
            }
            id = input_debito.data("row");
            lote_id = input_debito.data("lote_id");
        }
        if(input_credito.length){
            if(input_credito.val() == "0" || input_credito.val() == ""){
                input_credito.focus();
                return false;
            }
            id = input_credito.data("row");
            lote_id = input_credito.data("lote_id");
        }
        $.ajax({
            url: '{{ route("lotes_lancamentos.alter_lancamentos") }}',
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}", lancamentos_id: id, lotes_id: lote_id, debito: input_debito.val(), credito: input_credito.val()},
            method: 'POST',
            success: function(data){
                if(data.message == ""){
                    message("Atenção", "Dados salvos com sucesso!");
                }else{
                    message("Atenção", data.message);
                }
                getTable();
            },
            error: function(data){
                var errors = data.responseJSON.errors;
                if(errors.credito){
                    message("Atenção", errors.credito[0]);
                }
                if(errors.debito){
                    message("Atenção", errors.debito[0]);
                }
            }
        });
    }
    function getTable(){
        table_lancamentos.clear().draw();
        $.ajax({
            url: '{{ route("lotes_lancamentos.get_table", ["lote"=>$lote, "status"=>$status]) }}',
            dataType: 'json',
            data: {_token: "{{ csrf_token() }}"},
            method: 'POST',
            success: function(data){
                var fields_filter = [];
                for(var field in data){
                    var temp_field = [
                        data[field].codcad,
                        data[field].nome_fornecedor,
                        data[field].ndoc,
                        data[field].codbco,
                        data[field].nome_banco,
                        data[field].agencia_banco,
                        data[field].conta_banco,
                        data[field].valor_baixa,
                        data[field].datahora_baixa,
                        data[field].status_texto,
                        data[field].contactb_credito,
                        data[field].contactb_debito,
                        data[field].bt_salvar,
                    ];
                    fields_filter.push(temp_field);
                }
                table_lancamentos.rows.add(fields_filter).draw().nodes();
                $(document).find(".bt-salvar").off("click");
                $(document).find(".bt-salvar").on("click", function(event){
                    event.stopPropagation();
                    sendInputsSalvar($(this).parents("tr"));
                });
                $('[data-toggle="tooltip"]').tooltip();
            }
        });
    }
</script>
@endsection