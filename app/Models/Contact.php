<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'user_id',
        'phone_number',
        'residence'
    ];
    public function user(): BelongsTo{
        return $this->belongsTo(User::class);
    }
}
