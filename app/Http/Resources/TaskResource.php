<?php

// app/Http/Resources/TaskResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'deadline' => $this->deadline,
            'reminder' => $this->reminder,
            'priority' => $this->priority,
            'status' => $this->status,
            'attachments' => AttachmentResource::collection($this->attachments),
        ];
    }
}
