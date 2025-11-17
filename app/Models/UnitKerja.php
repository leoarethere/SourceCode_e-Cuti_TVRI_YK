<?php

namespace App\Models;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitKerja extends Model
{
    protected $fillable = ['nama', 'ketua_user_id'];

    // Relasi ke Ketua (User)
    public function ketua(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ketua_user_id');
    }

    // Relasi ke Anggota (Employee)
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}