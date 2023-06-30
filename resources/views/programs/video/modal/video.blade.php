@extends('layouts.page-dialog')

@section('content')

<div class="container">
 <form action="" name="form_video" id="form_video" onsubmit="return false;">
        @csrf
     <div class="form-row">
                   
                      
                        <div data-toggle='tooltip' data-html='true' data-placement='right'>
                            
                            <video width="440" height="240" controls>

                               
                                    <source src={{URL::asset($videos[0]['video']) }} >
                             Navegador Não suporta Video.
                          </video>
                               
                        </div>
                        
                 
        </div> 
</form>
</div> 
<script>
    $(document).ready( function (event) {
   
        form_modal_edit = $(document).find('#form_video');
    });
    

</script>
@endsection