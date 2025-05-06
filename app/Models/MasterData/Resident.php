<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resident extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'gender',
        'birth_date',
        'death_date',
        'address',
        'religion',
        'phone',
        'email',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(\App\Models\User::class, 'resident_id');
    }

    // Accessor untuk foto default
    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : asset('images/default-avatar.png');
    }
}
