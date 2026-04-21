<?php

namespace app\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'group_users');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
