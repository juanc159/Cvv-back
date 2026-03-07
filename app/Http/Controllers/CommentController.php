<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\CommentStoreRequest;
use App\Http\Resources\Comment\CommentFormResource;
use App\Http\Resources\Comment\CommentListResource;
use App\Repositories\CommentRepository;
use App\Traits\HttpResponseTrait;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    use HttpResponseTrait;

    public function __construct(
        protected CommentRepository $commentRepository,
    ) {}

    public function paginate(Request $request)
    {
        return $this->execute(function () use ($request) {
            $data = $this->commentRepository->paginate($request->all());
            $tableData = CommentListResource::collection($data);

            return [
                'code' => 200,
                'tableData' => $tableData,
                'lastPage' => $data->lastPage(),
                'totalData' => $data->total(),
                'totalPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
            ];
        });
    }

    public function create()
    {
        return $this->execute(function () {
            return [
                'code' => 200,
            ];
        });
    }

    public function store(CommentStoreRequest $request)
    {
        return $this->runTransaction(function () use ($request) {

            // 1. Preparamos los datos para el Comentario (Texto)
            // Quitamos 'attachments' para que el Repository no intente guardarlo en la tabla comments
            $dataForRepo = $request->except(['attachments']);

            // Ajuste del namespace polimórfico (como lo tenías)
            if (! str_contains($dataForRepo['commentable_type'], 'App\\Models\\')) {
                $dataForRepo['commentable_type'] = 'App\\Models\\'.$dataForRepo['commentable_type'];
            }

            // Conversión de booleano si viene como string (seguridad extra)
            if (isset($dataForRepo['is_internal'])) {
                $dataForRepo['is_internal'] = filter_var($dataForRepo['is_internal'], FILTER_VALIDATE_BOOLEAN);
            }

            // 2. Guardamos el Comentario usando tu Repository intacto
            // El repo devolverá la instancia del modelo creado
            $dataForRepo['user_id'] = auth()->id();

            $comment = $this->commentRepository->store($dataForRepo);

            // 3. Lógica de Guardado de Adjuntos (Aquí en el controlador)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {

                    // Generamos un nombre único: timestamp_nombreoriginal
                    $filename = time().'_'.$file->getClientOriginalName();

                    // Guardamos en storage/app/public/comments/{id_comentario}
                    $path = $file->storeAs("comments/{$comment->id}", $filename, 'public');

                    // Creamos el registro en la tabla hija usando la relación
                    // Nota: Asegúrate de tener la relación attachments() en tu modelo Comment
                    $comment->attachments()->create([
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ]);
                }
            }

            // 4. Retornamos respuesta con la data fresca y sus relaciones
            $data = new CommentListResource($comment->load(['attachments', 'user']));

            return [
                'code' => 200,
                'message' => 'Nota agregada correctamente',
                'data' => $data,
            ];
        });
    }

    public function edit($id)
    {
        return $this->execute(function () use ($id) {
            $data = $this->commentRepository->find($id);
            $form = new CommentFormResource($data);

            return [
                'code' => 200,
                'form' => $form,
            ];
        });
    }

    public function update(CommentStoreRequest $request, $id)
    {
        return $this->runTransaction(function () use ($request, $id) {

            // 1. Buscamos el comentario primero para validar propiedad
            $comment = $this->commentRepository->find($id);

            if (! $comment) {
                return response()->json(['message' => 'El comentario no existe'], 404);
            }

            // 2. VALIDACIÓN DE PROPIEDAD: Solo el dueño puede editar
            // Asumiendo que usas la autenticación estándar de Laravel
            if ($comment->user_id !== auth()->id()) {
                return response()->json(['message' => 'No tienes permiso para editar este comentario'], 403);
            }

            // 3. Preparamos los datos
            // Solo nos interesa el cuerpo y la privacidad.
            // Usamos except(['attachments']) para asegurar que no intente procesar archivos
            $dataForRepo = $request->except(['attachments']);

            // Ajuste de namespace si viene en el request (para pasar la validación del Request)
            if (isset($dataForRepo['commentable_type']) && ! str_contains($dataForRepo['commentable_type'], 'App\\Models\\')) {
                $dataForRepo['commentable_type'] = 'App\\Models\\'.$dataForRepo['commentable_type'];
            }

            // Conversión de booleano
            if (isset($dataForRepo['is_internal'])) {
                $dataForRepo['is_internal'] = filter_var($dataForRepo['is_internal'], FILTER_VALIDATE_BOOLEAN);
            }

            // 4. Actualizamos usando el repositorio
            // Al pasar el $id, el repo hace un $comment->fill($data) y save()
            $updatedComment = $this->commentRepository->store($dataForRepo, $id);

            return [
                'code' => 200,
                'message' => 'Comentario actualizado correctamente',
                // Devolvemos el recurso para actualizar la UI inmediatamente
                'data' => new CommentListResource($updatedComment->load(['attachments', 'user'])),
            ];
        });
    }

    public function delete($id)
    {
        return $this->runTransaction(function () use ($id) {
            $data = $this->commentRepository->find($id);

            // VALIDACIÓN DE PROPIEDAD: Solo el dueño puede eliminar.
            if ($data && $data->user_id !== auth()->id()) {
                return response()->json(['message' => 'No tienes permiso para eliminar este comentario'], 403);
            }

            if ($data) {
                $data->delete();
                $msg = 'Registro eliminado correctamente';
            } else {
                $msg = 'El registro no existe';
            }

            return [
                'code' => 200,
                'message' => $msg,
            ];
        }, 200);
    }

    public function changeStatus(Request $request)
    {
        return $this->runTransaction(function () use ($request) {
            $model = $this->commentRepository->changeState($request->input('id'), strval($request->input('value')), $request->input('field'));

            ($model->is_active == 1) ? $msg = 'habilitada' : $msg = 'inhabilitada';

            return [
                'code' => 200,
                'message' => 'Nota '.$msg.' con éxito',
            ];
        });
    }
}
