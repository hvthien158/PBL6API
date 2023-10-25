<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $table = 'departments';
    protected $fillable = [
        'department_name',
        'address',
        'email',
        'phone_number'
    ];
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }
}