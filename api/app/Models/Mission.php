<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use HasFactory;

    public function getImageAttribute($value)
    {
        return $value ? env("APP_STORAGE_URL", "/") . '/storage' . $value : null;
    }

    public function levels()
    {
        return $this->hasMany(MissionLevel::class);
    }

    public function nextLevel()
    {
        return $this->hasOne(MissionLevel::class)
            ->orderBy('level');
    }

    public function type()
    {
        return $this->belongsTo(MissionType::class);
    }
}
