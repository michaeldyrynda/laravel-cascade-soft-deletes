<?php

namespace Tests\Entities;

use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;

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
