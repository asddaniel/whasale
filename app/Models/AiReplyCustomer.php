<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReplyCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'response',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
