<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class NonSoftDeletingPost extends Model
{
    use CascadeSoftDeletes;

    protected $table = 'posts';

    protected $cascadeDeletes = ['comments'];

    protected $fillable = ['title', 'body'];

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }
}
