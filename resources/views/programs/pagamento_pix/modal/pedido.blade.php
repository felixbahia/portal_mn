@extends('layouts.page-dialog')
@section('content')
<div class="content-buttons">

    <th class='th-criterios-titulo' style="text-align: center;">{{ $titulo }}</th> 
    <input type="button" id="btn_sem" class="btn float-right btn-success"  onclick="SemPedido('{{ $id }}')" value="Sem Pedido" />   
    <input type="button" id="bt_mais" class="btn float-right azul-sistema"  onclick="AbrirTodos('{{ $id }}')" value="Mais Pedidos " />
              
</div>
   
 <div class="content-dialog-table">
    <div class="content-table">
        <table class="table table-striped table-not-edit" id="table_filters">
            <thead>               
                <tr>
                    <th data-toggle="tooltip">Estabel</th>
                    <th class="tb_number">Pedido Portal</th>
                    <th class="tb_number">Pedido Nasajon</th>
                    <th data-toggle="tooltip">Comprador/Contato</th>
                    <th class="tb_number">Valor</th>
                    <th class="sort-date">Emissão</th>                    
                    <th data-toggle="tooltip">Vendedor</th> 
                    <th data-toggle="tooltip">Gerente</th> 
                    <th class='th-criterios-titulo' style="text-align: center;">Gerar Crédito</th>    
                </tr>  
            </thead>
            <tbody>
                @foreach($return as $dados)
                    <tr>
                       
                        <td><div><div data-toggle="tooltip" data-html="true" title="">{{ $dados["estabelecimento"] }}&nbsp;&nbsp;</div></div></td>
                        <td data-order="{{ $dados["numeroPedido"] }}"> <a href="#" onclick="abrirPedido({{  $dados['numeroPedido'] }})"> {{  $dados['numeroPedido'] }}</a> </td> 
                        <td data-order="{{ $dados["pedidoNasajon"] }}">{{  $dados['pedidoNasajon'] }} </td>   
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $dados["cliente"] }}">{{ $dados["cliente"] }}</div></div></td>  
                        <td class="tb_number"> {{  $dados['valor'] }} </td>
                        <td class="tb_date"> {{  $dados['data'] }} </td> 
                        <td><div><div data-toggle="tooltip" data-html="true" title="{{ $dados["vendedor"] }}">{{ $dados["vendedor"] }}</div></div></td> 
                        <td class="td_cliente"> {{  $dados['gerente'] }} </td>
                        <td class="td_acao"><center><a href="#" class="bt-aprove" data-toggle="tooltip" data-html="true" title="Gerar Credito" onclick="CriarCredito('{{ $dados['cpfcnpj'] }}','{{$dados['numeroPedido']}}','{{$dados['pedidoNasajon']}}','{{ $dados['valor'] }}','{{ $dados['idPix'] }}','{{ $dados['estabelecimentoNasajon'] }}','{{ $dados['clienteNasajon'] }}')"></a></center></td>
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

function CriarCredito($cpfcnpj,$pedidoPortal,$pedidoNasajon,$valor,$idPix,$estabelecimento,$clienteNasajon){
    $.ajax({
        
        url: "{{ route("pagamento_pix.cria_credito") }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            pix_id: $idPix,
            cpfcnpj: $cpfcnpj,
            pedidoPortal: $pedidoPortal,
            pedidoNasajon: $pedidoNasajon,
            valor: $valor,
            estabelecimento: $estabelecimento,
            clienteNasajon: $clienteNasajon,

        },
        success: function(callback){
            if(callback.status === "success"){   
                $('.modal').modal('hide');             
                message("Atenção", "Credito criado e associado ao Pix");
                filterPagamentoPix();
                
            } else {
                message("Atenção", callback.message);
            }
        },
        error: function(data){
            message('Atenção', data.responseJSON.error.msg.user);
        }
    });

}


function abrirPedido($id){
    $.ajax({
        url: "{{ route("pedido_portal.detalhes") }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            pedido_id: $id
        },
        success: function(callback){
            $title = "Detalhes do pedido: "+$id; 
            $body = callback;
            $class = "modal-lg";
            createModal("view_pedido", $title, $body, $class);
        }
    });
}      

function AbrirTodos($id) {
    
    $.ajax({
        url: "{{ route("pagamento_pix.modal.todos_pedido") }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            pix_id: $id
        },
        success: function(data){
            $id = "view-pedido";
            title = "Gerar Crédito"; 
            $class = "modal-lg";
            createModal("pagamento_pix-modal-todos-pedido", title, data, 'modal-lg');
        }
    });
}

function SemPedido($idPix) {
    
    $.ajax({
        url: "{{ route("pagamento_pix.modal.sem_pedido") }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            pix_id: $idPix

        },
        success: function(data){
            $id = "view-pedido";
            title = "Gerar Crédito"; 
            $class = "modal-lg";
            createModal("pagamento_pix-modal-sem-pedido", title, data, 'modal-lg');
        }
    });
}



</script>
@endsection       