<?php

namespace App\Http\Resources\Comment;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'is_internal' => $this->is_internal, // Para poner un icono de candado o color diferente
            'user_data' => $this->user?->full_name.' - '.$this->user?->role?->description,
            'user_avatar' => $this->user?->photo, // Útil para el chat
            'initials' => getInitials($this->user->full_name),
            'created_at' => Carbon::parse($this->created_at)->format('d-m-Y g:i A'),

            // Devolvemos los adjuntos formateados
            'attachments' => $this->attachments->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'file_path' => $file->file_path, // El front usará un helper para añadir el dominio
                    'mime_type' => $file->mime_type,
                ];
            }),

            // Helper para saber si soy yo el dueño (para editar/borrar)
            'is_mine' => $this->user_id == auth()->id(),
        ];
    }
}
