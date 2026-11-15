<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['conversation_id', 'role', 'content', 'mode', 'tools'];

    protected $casts = ['tools' => 'array'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'conversation_id');
    }
}
