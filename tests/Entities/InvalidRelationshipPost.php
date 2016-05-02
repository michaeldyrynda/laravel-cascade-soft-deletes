<?php

namespace Tests\Entities;

use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tests\Entities\Comment;

class InvalidRelationshipPost extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = ['comments', 'invalidRelationship'];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function invalidRelationship()
    {
        return;
    }
}
