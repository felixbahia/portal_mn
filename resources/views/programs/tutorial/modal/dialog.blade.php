@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_video" id="form_video" onsubmit="return false;">
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit"  id="table_dialog_video">
        <thead>
            <tr>
    
                <th >Video</th>
                <th>Descrição</th>
   
            </tr>

  
        </thead>
        <tbody>
            
            @foreach ($dados as $video)
             <tr>
                
               
                <td><a href="#" class="bt-view" title='Visualizar' data-id="{{ $video['id'] }}" onclick="modalVideoTutorial($(this))"></a></td>
                <td>{{ $video['descricao'] }}</td>
              
       
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                
             
                <td></td>
                <td</td>
              
       
             </tr>   
           </tfoot>
    </table>
</div>

</form>
<script>
    table_dialog_video = $('#table_dialog_video').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "orderMulti": false,

        "language": {
            "emptyTable":     "Nenhum registro encontrado",
            "infoPostFix":    "",
            "thousands":      ".",
            "decimal":        ",",
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
                "class": "tb_number", 
                "targets": "tb_number"
            },
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 0, 'asc' ]]
    });

    setTimeout(function(){
        table_dialog_video.draw(false);
    }, 200);


    function modalVideoTutorial($this) {
       
        var id = $this.data("id");
     
        $.ajax({
            url: '{{ route("tutorial.modal.video") }}',
            data: {_token: '{{ csrf_token() }}', id:id},
            method: 'POST',
            success: function(body){
    
                var title = 'Video';
                createModal("modal_video", title, body, '');
            }
        });
    }

</script>
@endsection
