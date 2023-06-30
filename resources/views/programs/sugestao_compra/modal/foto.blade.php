@extends('layouts.page-dialog')

@section('content')

<div class="container">
 <form action="" name="form_sugestao_compra_foto" id="form_sugestao_compra_foto" onsubmit="return false;">
        @csrf
     <div class="form-row">
                   
                        @foreach($fotos as $key => $foto )
                        <div data-toggle='tooltip' data-html='true' data-placement='right'>
                                <img src={{ $foto['foto'] }} style="width: 400px; height: 300px">
                        </div>
                        @endforeach
                 
        </div> 
</form>
</div> 
<script>
    $(document).ready( function (event) {
   
        form_modal_edit = $(document).find('#form_sugestao_compra_foto');
    });
    

</script>
@endsection