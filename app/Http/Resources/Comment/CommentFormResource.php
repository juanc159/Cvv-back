<?php

namespace App\Http\Resources\Comment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentFormResource extends JsonResource
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
            'company_id' => $this->company_id,
            'commentable_id' => $this->commentable_id,
            'commentable_type' => $this->commentable_type,
            'body' => $this->body,
            'user_id' => $this->user_id,
        ];
    }
}
