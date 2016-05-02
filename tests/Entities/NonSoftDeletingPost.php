<?php

namespace Tests\Entities;

use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Tests\Entities\Comment;

class NonSoftDeletingPost extends Model
{
    use CascadeSoftDeletes;

    protected $cascadeDeletes = ['comments'];

    protected $fillable = ['title', 'body'];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
