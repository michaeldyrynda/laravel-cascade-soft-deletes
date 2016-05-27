<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;
use Tests\Entities\Post;
use Tests\Entities\Comment;

class ChildPost extends Post
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }
}
