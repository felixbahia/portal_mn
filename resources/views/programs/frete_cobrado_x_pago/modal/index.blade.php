@extends('layouts.page-dialog')

@section('content')

<div class="content-dialog-table mt-5" style='float: none;'>
    <div class="content-table notas-importadas">
        <table class='table table-striped table-filter table-not-edit table-not-view' id='table-filter-frete-detalhe'>
            <thead>
                <tr>
                    <th rowspan='2'>Nota</th>
                    <th rowspan='2'>CTE</th>
                    <th rowspan='2'>Nat. Op.</th>
                    <th rowspan='2' class='nome'>Cliente</th>
                    <th rowspan='2'>Origem</th>
                    <th rowspan='2'>Destino</th>
                    <th rowspan='2' class='nome'>transportadora</th>
                    <th rowspan='2' class='mes-col tb_number'>Valor da nota</th>
                    <th colspan='2'>volume</th>
                    <th colspan='2'>Peso</th>
                    <th colspan='5'>Frete</th>
                </tr>
                <tr>
                    <th class='lancadas-col tb_number'>Pago</th>
                    <th class='lancadas-col tb_number'>Transportado</th>
                    <th class='notas-col tb_number'>Pago</th>
                    <th class='notas-col tb_number'>Transportado</th>
                    <th class='compras-col tb_number'>Cobrado</th>
                    <th class='compras-col tb_number'>% Cobrado</th>
                    <th class='compras-col tb_number'>Pago</th>
                    <th class='compras-col tb_number'>% Pago</th>
                    <th class='compras-col tb_number'>Diferença</th>
                </tr>
            </thead>
            <tbody>
            @foreach($resultado as $nota)
                <tr>
                    <td>{!! $nota['nota'] !!}</td>
                    <td>
                        <a href='#' onclick="showCteDetalhesNasajon('{{ $nota['cte_id'] }}')">{{ $nota['cte'] }}</a>
                    </td>
                    <td>{!! $nota['natureza_operacao'] !!}</td>
                    <td>{!! $nota['cliente'] !!}</td>
                    <td>{!! $nota['origem'] !!}</td>
                    <td>{!! $nota['destino'] !!}</td>
                    <td>{!! $nota['transportador'] !!}</td>
                    <td>{{ $nota['valor_nota'] }}</td>
                    <td>{{ $nota['volume_transportado'] }}</td>
                    <td>{{ $nota['volume_cobrado'] }}</td>
                    <td>{{ $nota['peso_transportado'] }}</td>
                    <td>{{ $nota['peso_cobrado'] }}</td>
                    <td>{{ $nota['frete_cobrado'] }}</td>
                    <td>{{ $nota['frete_adicional'] }}</td>
                    <td>{{ $nota['frete_pago'] }}</td>
                    <td>{!! $nota['porcentagem_frete_pago'] !!}</td>
                    <td>{{ $nota['diferenca'] }}</td>
                </tr>
            @endforeach
            </tbody>
            <tfoot>
                <td colspan='7'>TOTAL</td>
                <td></td>
                <td>{{ $total['volume_transportado'] }}</td>
                <td>{{ $total['volume_cobrado'] }}</td>
                <td>{{ $total['peso_transportado'] }}</td>
                <td>{{ $total['peso_cobrado'] }}</td>
                <td>{{ $total['frete_cobrado'] }}</td>
                <td></td>
                <td>{{ $total['frete_pago'] }}</td>
                <td></td>
                <td>{{ $total['diferenca'] }}</td>
            </tfoot>
        </table>
    </div>
</div>
<script>
    $(document).ready(function(){
        $(document).find('#btn-excel-modal').on('click', function(){
            exportXlsxModal();
        });
        
        setTimeout(function(){
            table_filters_frete_modal.draw();
        }, 1000);
    });

    table_filters_frete_modal = $('#table-filter-frete-detalhe').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "scrollX": false,
        "scrollCollapse": true,
        "paging": false,
        "autoWidth": true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                footer: true,
                title: '',
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];

                    $('row c', sheet).each(function() {
                    var numero=$(this).parent().index() ;
                        var residuo = numero%2;
                        if (numero==0){           
                            $(this).attr('s','22');
                        }else if (numero>0){
                            if(residuo ==0  ){
                            $(this).attr('s','25');
                            }else{
                            $(this).attr('s','32');
                            }
                        }
                    });

                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    var downrows = 1;
                    var clRow = $('row', sheet);
                    
                    clRow.each(function () {
                        var attr = $(this).attr('r');
                        var ind = parseInt(attr);
                        ind = ind + downrows;
                        $(this).attr("r",ind);
                    });
            
                    $('row c ', sheet).each(function () {
                        var attr = $(this).attr('r');
                        var pre = attr.substring(0, 1);
                        var ind = parseInt(attr.substring(1, attr.length));
                        ind = ind + downrows;
                        $(this).attr("r", pre + ind);
                    });
            
                    function Addrow(index,data) {
                        msg='<row r="'+index+'">'
                        for(i=0;i<data.length;i++){
                            var key=data[i].k;
                            if(key == "H" || key == "I"){
                                var value=data[i].v;
                                msg += '<c t="inlineStr" r="' + key + index + '" s="42">';
                                msg += '<is>';
                                msg +=  '<t>'+value+'</t>';
                                msg+=  '</is>';
                                msg+='</c>';
                            }else if(key == 'J' || key == 'K'){
                                var key=data[i].k;
                                var value=data[i].v;
                                msg += '<c t="inlineStr" r="' + key + index + '" s="38">';
                                msg += '<is>';
                                msg +=  '<t>'+value+'</t>';
                                msg+=  '</is>';
                                msg+='</c>';
                            } else if(key == 'L' || key == 'M' || key == 'N' || key == 'O' || key == 'P'){
                                var key=data[i].k;
                                var value=data[i].v;
                                msg += '<c t="inlineStr" r="' + key + index + '" s="22">';
                                msg += '<is>';
                                msg +=  '<t>'+value+'</t>';
                                msg+=  '</is>';
                                msg+='</c>';
                            }else{
                                var key=data[i].k;
                                var value=data[i].v;
                                msg += '<c t="inlineStr" r="' + key + index + '" s="22">';
                                msg += '<is>';
                                msg +=  '<t>'+value+'</t>';
                                msg+=  '</is>';
                                msg+='</c>';
                            }
                        }
                        msg += '</row>';
                        return msg;
                    }

                    var r1 = Addrow(1, [
                        { k: 'A', v: '' },
                        { k: 'B', v: '' },
                        { k: 'C', v: '' },
                        { k: 'D', v: '' },
                        { k: 'E', v: '' },
                        { k: 'F', v: '' },
                        { k: 'G', v: '' },
                        { k: 'H', v: 'Volume' },
                        { k: 'I', v: '' },
                        { k: 'J', v: 'Peso' },
                        { k: 'K', v: '' },
                        { k: 'L', v: 'Frete' },
                        { k: 'M', v: '' },
                        { k: 'N', v: '' },
                        { k: 'O', v: '' },
                        { k: 'P', v: '' }
                    ]);
                    
                    sheet.childNodes[0].childNodes[1].innerHTML = r1 + sheet.childNodes[0].childNodes[1].innerHTML;
            
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();

                            if(column === 7 || column === 8 || column === 9 || column === 10 || column === 11 || column === 12 || column === 14 || column === 16){

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
                        },
                        footer: function(data) {
                            data = $('<p>' + data + '</p>').text();
                            return $.isNumeric(data.replace(',', '.')) ? data.replace( /[$,]/g, '.' ) : data;
                        }
                    }
                },
            },
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
        "columnDefs": [
            {
                "class": "mes-col tb_number",
                "targets": "mes-col tb_number"
            },
            {
                "class": "lancadas-col tb_number",
                "targets": "lancadas-col tb_number"
            },
            {
                "class": "notas-col tb_number",
                "targets": "notas-col tb_number"
            },
            {
                "class": "compras-col tb_number",
                "targets": "compras-col tb_number"
            },
        ],
        "order": [ 0, 'asc' ]
    });

    function exportXlsxModal(){
        $('<form action="{{ route('frete_cobrado_x_pago.modal.export') }}" method="POST" target="_blank">\
            <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="estabelecimento" value="{{ $fields['estabelecimento'] }}">\
                <input type="hidden" name="data_inicio" value="{{ $fields['data_inicio'] }}" />\
                <input type="hidden" name="data_fim" value="{{ $fields['data_fim'] }}"  />\
                <input type="hidden" name="transportadora" value="{{ $fields['transportadora']??'' }}"  />\
                <input type="hidden" name="entrega" value="{{ $fields['entrega']??'' }}"  />\
                <input type="hidden" name="cif" value="{{ $fields['cif']??'' }}"  />\
                <input type="hidden" name="fob" value="{{ $fields['fob']??'' }}"  />\
            </form>').appendTo('body').submit().remove();
    }


    function showModalNota($this){
		var url = $($this).data("route");
		var $id = $($this).data("id");
		var modal_class = $($this).data("modal");
		var title = $($this).data("title_modal");
		$.ajax({
			url: url,
			method: 'POST',
			data: {_token: "{{ csrf_token() }}", id_nota: $id, id: $id},
			success: function(body){
				createModal('modal_nota_detalhe_lupa', title, body, modal_class);
			}
		});
	}

    
function showCteDetalhesNasajon($id){
    $.ajax({
        url: '{{ route('notas_importadas.modal.notas.cte')}}',
        type: 'POST',
        data: {
            _token: '{{csrf_token()}}',
            id: $id,
        },
        success: function(body){
            createModal("nota_detalhes", "Detalhes da CTE", body, 'modal-lg');
            $(".troca-aba").on("click", function(e){
                e.preventDefault();
                $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
            })

        }
    });
}
</script>
@endsection
