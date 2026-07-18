<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoogleAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'service_account_json', // On remplace les anciens jetons par le JSON complet
        'is_active',
    ];

    protected $casts = [
        // Chiffrement automatique en base de données pour protéger vos clés privées Google !
        'service_account_json' => 'encrypted:array',
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}