@extends('layouts.page-dialog')

@section('content')

<div class="container">
 <form action="" name="form_video_tutorial" id="form_video_tutorial" onsubmit="return false;">
        @csrf
     <div class="form-row">
                   
                        @foreach($videos as $key => $video )
                        <div data-toggle='tooltip' data-html='true' data-placement='right'>
                            
                            <video width="440" height="240" controls>

                                <source src={{URL::asset($video['video']) }} >
                             Navegador Não suporta Video.
                          </video>
                               
                        </div>
                        @endforeach
                 
        </div> 
</form>
</div> 
<script>
    $(document).ready( function (event) {
   
        form_modal_edit = $(document).find('#form_video_tutorial');
    });
    

</script>
@endsection