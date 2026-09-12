<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_session',
        'conversation_id',
        'role',
        'content',
        'design_id',
        'image_url',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}
