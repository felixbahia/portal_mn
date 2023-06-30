@extends('layouts.page-dialog')
@section('content')
   
 <div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit" id="table_filters">
            <thead>               
                <tr>
                    <th data-toggle="tooltip">Estabel</th>
                    <th>Código Produto</th>
                    <th>Peça Pai</th>
                    <th>Peça</th>
                    <th>Endereço</th>
                    <th class="tb_number">Quantidade</th> 
                </tr>  
            </thead>
            <tbody>
                @foreach($return as $dados)
                    <tr>
                       
                        <td><div><div data-toggle="tooltip" data-html="true" title="">{{ $dados["estabelecimento"] }}&nbsp;&nbsp;</div></div></td>
                        <td data-order="{{ $dados["produtocodigo"] }}">{{  $dados['produtocodigo'] }} </td> 
                        <td data-order="{{ $dados["pecapai"] }}">{{  $dados['pecapai'] }} </td>   
                        <td data-order="{{ $dados["peca"] }}">{{  $dados['peca'] }} </td>  
                        <td data-order="{{ $dados["endereco"] }}">{{  $dados['endereco'] }} </td>  
                        <td data-order="{{ $dados["quantidade"] }}">{{  $dados['quantidade'] }} </td>
                    </tr>
                @endforeach
            </tbody>
           
        </table>
    </div>
</div>
<script>
$(document).ready( function () {
    var table_filters = $('#table_filters').DataTable({
        "scrollX": false,
        "searching": false,
        "lengthChange": false,
        "searching": false,
        "info": false,
        "pageLength": 20,
        "orderMulti": false,
        "autowidth": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '',
                footer: true,
                customize: function( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('row c[r^="C"]', sheet).attr( 's', '2' );
                },
                exportOptions: {
                    columns: ':visible',
                    format: {
                        body: function(data, row, column, node) {
                            data = $('<p>' + data + '</p>').text();
                            if(column === 5 || column === 6 || column === 9){
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
        
        "columnDefs": [
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number","width": "100px" },
            { "class": "tb_date", targets: "tb_date"},
            {
                'targets': 'td_acao',
                'class': 'td_acao',
                'width': '5px',
                "orderable": false
            },
            { targets: 0, width: '10px'},
        ],
        "order": [[ 1, 'asc' ]]
    });
    
    setTimeout(function(){
        table_filters.draw();
    }, 200);
});


</script>
@endsection       