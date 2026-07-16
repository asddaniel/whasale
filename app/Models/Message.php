<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'message',
        'data',
        'media_path', // Nouvelle colonne pour stocker le lien local du fichier
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function aiReply(): HasOne
    {
        return $this->hasOne(AiReplyCustomer::class);
    }
}
