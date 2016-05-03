<?php

namespace Tests\Entities;

use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvalidRelationshipPost extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = ['comments', 'invalidRelationship'];

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment');
    }

    public function invalidRelationship()
    {
        return;
    }
}
