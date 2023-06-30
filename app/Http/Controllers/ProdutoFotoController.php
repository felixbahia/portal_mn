<?php

namespace App\Http\Controllers;

use App\ProdutoFoto; 

use App\Http\Requests\ProdutoFotoRequest;
use App\Http\Requests\ProdutoFotoEditarRequest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class ProdutoFotoController extends Controller
{
    //
    public function index(Request $request) {
        if(Auth::user()->hasPermissionTo("programas App\ProdutoFoto") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ProdutoFoto');

        return view('programs.produto_foto.index');

    }

    public function filtro(Request $request){
        set_time_limit(300);
        ini_set('memory_limit','1024M');
        $fields = $request->only('grupo', 'codigo_produto', 'descricao', 'marca', 'linha');

        $produtoFotoQuery = ProdutoFoto::with('produto_especificacao');

        if (isset($fields['codigo_produto'])){
            $produtoFotoQuery->where('codigo_produto', 'ilike', '%' . $fields['codigo_produto'] . '%');
        }

        if(isset($fields['grupo']) || isset($fields['descricao']) || isset($fields['marca']) || isset($fields['linha'])){

            $produtoFotoQuery->whereHas('produto_especificacao', function($query) use ($fields){
                $query->join('produto_grupos','produto_especificacaos.produto_grupos_id', '=', 'produto_grupos.id');
                if (isset($fields['grupo'])){
                    $query->where('produto_grupos.descricao', 'ilike', $fields['grupo']);
                }

                if (isset($fields['descricao'])){
                    $query->where('produto_especificacaos.descricao', 'ilike', $fields['descricao']);
                }

                if (isset($fields['marca'])){
                    $query->where('marca', 'ilike', $fields['marca']);
                }

                if (isset($fields['linha'])){
                    $query->where('linha', 'ilike', $fields['linha']);
                }

            });

        }

        $produtoFotosObj = $produtoFotoQuery->get();

        $return = [];

        $produtoFotosObj->each(function($produtoFoto) use (&$return){

            $linha = [];

            $linha['grupo'] = $produtoFoto->produto_especificacao->grupo;
            $linha['codigo_produto'] = $produtoFoto->codigo_produto;
            $linha['descricao'] = $produtoFoto->produto_especificacao->descricao;
            $linha['hash'] = Crypt::encrypt($produtoFoto->codigo_produto);

            if(Storage::exists('public/produto_fotos/' . $produtoFoto->filename) && Storage::exists('public/produto_fotos/' . $produtoFoto->thumb_filename)){
                $foto = "<a href='" . Storage::url('public/produto_fotos/' . $produtoFoto->filename) . "?" . time() . "' class='foto-produto'> <img src='" . Storage::url('public/produto_fotos/' . $produtoFoto->thumb_filename) . "?". time(). "' /></a>"; 
            }
            else{
                $foto = "";
            }

            $linha['foto'] = $foto;

            $return[] = $linha;

        });

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'data' => $return 
            ]
        ], 
        220);
    }

    public function adicionarModal(){
        return view('programs.produto_foto.modal.adicionar');
    }

    public function editarModal(Request $request){
        $produtoFoto = ProdutoFoto::with('produto_especificacao')->where('codigo_produto', Crypt::decrypt($request->hash))->first(); 
        $produto = [];

        $produto['codigo_produto'] = $produtoFoto->codigo_produto;
        $produto['descricao'] = $produtoFoto->produto_especificacao->descricao;
        $produto['marca'] = $produtoFoto->produto_especificacao->marca;
        $produto['linha'] = $produtoFoto->produto_especificacao->linha;
        $produto['grupo'] = $produtoFoto->produto_especificacao->grupo;
        $produto['hash'] = Crypt::encrypt($produtoFoto->codigo_produto);        

        if(Storage::exists('public/produto_fotos/' . $produtoFoto->filename)){
            $produto['foto'] = "<img src='" . Storage::url('public/produto_fotos/' . $produtoFoto->filename) . "?" . time() . "' height='150' width='150'>";
        }
        else{
            $produto['foto'] = "";
        }

        return view('programs.produto_foto.modal.editar')->with('produto', $produto);
    }

    public function excluirModal(Request $request){
        $produtoFoto = ProdutoFoto::with('produto_especificacao')->where('codigo_produto', Crypt::decrypt($request->hash))->first(); 
        $produto = [];

        $produto['codigo_produto'] = $produtoFoto->codigo_produto;
        $produto['descricao'] = $produtoFoto->produto_especificacao->descricao;
        $produto['marca'] = $produtoFoto->produto_especificacao->marca;
        $produto['linha'] = $produtoFoto->produto_especificacao->linha;
        $produto['grupo'] = $produtoFoto->produto_especificacao->grupo;
        $produto['hash'] = Crypt::encrypt($produtoFoto->codigo_produto);

        if(Storage::exists('public/produto_fotos/' . $produtoFoto->filename)){
            $produto['foto'] = "<img src='" . Storage::url('public/produto_fotos/' . $produtoFoto->filename) . "?" . time() . "' height='150' width='150'>";
        }
        else{
            $produto['foto'] = "";
        }

        return view('programs.produto_foto.modal.excluir')->with('produto', $produto);
    }

    public function salvar(ProdutoFotoRequest $request){
        
        ini_set('memory_limit', '2M');
        ini_set('post_max_size', '2M');
        ini_set('upload_max_filesize', '2M');

        $fields = $request->only('codigo_produto');
        $foto = imagecreatefromstring(file_get_contents($request->file('foto')));

        if(imagesx($foto) != imagesy($foto)){
            $size = min(imagesx($foto), imagesy($foto));

            if(imagesx($foto) > imagesy($foto)){
                if(imagesx($foto) > $size + imagesx($foto)*0.4){
                    $foto = imagecrop($foto, ['x' => $size*0.4, 'y' => 0, 'width' => $size, 'height' => $size]);
                }
                else{
                    $foto = imagecrop($foto, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
                }
            }
            else if(imagesx($foto) <  imagesy($foto)){
                if(imagesy($foto) > $size + imagesy($foto)*0.4){
                    $foto = imagecrop($foto, ['x' => 0, 'y' => $size*0.4, 'width' => $size, 'height' => $size]);
                }
                else{
                    $foto = imagecrop($foto, ['x' => 0, 'y' => $size, 'width' => $size, 'height' => $size]);
                }
            }
        }

        $foto = imagescale($foto, 300);

        $filename = $fields['codigo_produto'] . '.jpg';
        $thumb_filename = $fields['codigo_produto'] . '_thumb.jpg';

        $produtoFotoObj = new ProdutoFoto;
        
        ob_start();
        imagejpeg($foto);
        $jpg = ob_get_contents();
        ob_end_clean();

        if(Storage::put('public/produto_fotos/' . $filename, $jpg)){
            $thumb = imagecreatefromstring(file_get_contents($request->file('foto')));

            if(imagesx($thumb) != imagesy($thumb)){
                $size = min(imagesx($thumb), imagesy($thumb));
    
                if(imagesx($thumb) > imagesy($thumb)){
                    if(imagesx($thumb) > $size + imagesx($thumb)*0.4){
                        $thumb = imagecrop($thumb, ['x' => $size*0.4, 'y' => 0, 'width' => $size, 'height' => $size]);
                    }
                    else{
                        $thumb = imagecrop($thumb, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
                    }
                }
                else if(imagesx($thumb) < imagesy($thumb)){
                    if(imagesy($thumb) > $size + imagesy($thumb)*0.4){
                        $thumb = imagecrop($thumb, ['x' => 0, 'y' => $size*0.4, 'width' => $size, 'height' => $size]);
                    }
                    else{
                        $thumb = imagecrop($thumb, ['x' => 0, 'y' => $size, 'width' => $size, 'height' => $size]);
                    }
                }
            }

            $thumb = imagescale($thumb, 150);

            ob_start();
            imagejpeg($thumb);
            $jpg_thumb = ob_get_contents();
            ob_end_clean();

            Storage::put('public/produto_fotos/' . $thumb_filename, $jpg_thumb);

            $produtoFotoObj->codigo_produto = $fields['codigo_produto'];
            $produtoFotoObj->filename = $filename;
            $produtoFotoObj->thumb_filename = $thumb_filename;
            $produtoFotoObj->created_by = Auth::user()->id;
            $produtoFotoObj->save();

            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => [
                    'data' => [] 
                ]
            ], 
            220);
        }
        else{
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'foto' => 'Ocorreu um erro ao salvar o arquivo'
                ],
                'response' => [
                    'data' => [] 
                ]
            ], 
            422);
        }
        
    }

    public function editar(ProdutoFotoEditarRequest $request){

        ini_set('memory_limit', '2M');
        ini_set('post_max_size', '2M');
        ini_set('upload_max_filesize', '2M');

        $fields = $request->only('codigo_produto', 'hash');
        $foto = imagecreatefromstring(file_get_contents($request->file('foto')));

        if(imagesx($foto) != imagesy($foto)){
            $size = min(imagesx($foto), imagesy($foto));

            if(imagesx($foto) > imagesy($foto)){
                if(imagesx($foto) > $size + imagesx($foto)*0.4){
                    $foto = imagecrop($foto, ['x' => $size*0.4, 'y' => 0, 'width' => $size, 'height' => $size]);
                }
                else{
                    $foto = imagecrop($foto, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
                }
            }
            else if(imagesx($foto) <  imagesy($foto)){
                if(imagesy($foto) > $size + imagesy($foto)*0.4){
                    $foto = imagecrop($foto, ['x' => 0, 'y' => $size*0.4, 'width' => $size, 'height' => $size]);
                }
                else{
                    $foto = imagecrop($foto, ['x' => 0, 'y' => $size, 'width' => $size, 'height' => $size]);
                }
            }
        }

        $foto = imagescale($foto, 300);

        $produtoFotoObj = ProdutoFoto::where('codigo_produto', Crypt::decrypt($fields['hash']))->first();

        $filename = $produtoFotoObj->codigo_produto . '.jpg';
        $thumb_filename = $produtoFotoObj->codigo_produto . '_thumb.jpg';

        ob_start();
        imagejpeg($foto);
        $jpg = ob_get_contents();
        ob_end_clean();

        if(Storage::exists('public/produto_fotos/' . $produtoFotoObj->filename)){
            Storage::delete('public/produto_fotos/' . $produtoFotoObj->filename);
        }

        if(Storage::put('public/produto_fotos/' . $filename, $jpg)){

            if(Storage::exists('public/produto_fotos/' . $thumb_filename)){
                Storage::delete('public/produto_fotos/' . $thumb_filename);
            }

            $thumb = imagecreatefromstring(file_get_contents($request->file('foto')));

            if(imagesx($thumb) != imagesy($thumb)){
                $size = min(imagesx($thumb), imagesy($thumb));
    
                if(imagesx($thumb) > imagesy($thumb)){
                    if(imagesx($thumb) > $size + imagesx($thumb)*0.4){
                        $thumb = imagecrop($thumb, ['x' => $size*0.4, 'y' => 0, 'width' => $size, 'height' => $size]);
                    }
                    else{
                        $thumb = imagecrop($thumb, ['x' => 0, 'y' => 0, 'width' => $size, 'height' => $size]);
                    }
                }
                else if(imagesx($thumb) <  imagesy($thumb)){
                    if(imagesy($thumb) > $size + imagesy($thumb)*0.4){
                        $thumb = imagecrop($thumb, ['x' => 0, 'y' => $size*0.4, 'width' => $size, 'height' => $size]);
                    }
                    else{
                        $thumb = imagecrop($thumb, ['x' => 0, 'y' => $size, 'width' => $size, 'height' => $size]);
                    }
                }
            }

            $thumb = imagescale($thumb, 150);

            ob_start();
            imagejpeg($thumb);
            $jpg_thumb = ob_get_contents();
            ob_end_clean();

            Storage::put('public/produto_fotos/' . $thumb_filename, $jpg_thumb);

            $produtoFotoObj->codigo_produto = $produtoFotoObj->codigo_produto;
            $produtoFotoObj->filename = $filename;
            $produtoFotoObj->thumb_filename = $thumb_filename;
            $produtoFotoObj->updated_by = Auth::user()->id;
            $produtoFotoObj->save();

            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => [
                    'data' => [] 
                ]
            ], 
            220);
        }
        else{
            return response()->json([
                'status' => 'error',
                'message' => '',
                'error' => [
                    'foto' => 'Ocorreu um erro ao salvar o arquivo'
                ],
                'response' => [
                    'data' => [] 
                ]
            ], 
            422);
        }
        
    }

    public function excluir(Request $request){

        $fields = $request->only('hash');

        $produtoFotoObj = ProdutoFoto::where('codigo_produto', Crypt::decrypt($fields['hash']))->first();
        $produtoFotoObj->delete();

        if(Storage::exists('public/produto_fotos/' . $produtoFotoObj->codigo_produto . '.png')){
            Storage::delete('public/produto_fotos/' . $produtoFotoObj->codigo_produto . '.png');
        }

        return response()->json([
            'status' => 'success',
            'message' => '',
            'error' => [],
            'response' => [
                'data' => [] 
            ]
        ],
        220);
    }

}
