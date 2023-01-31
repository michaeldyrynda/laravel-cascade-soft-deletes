<?php

namespace Tests\Entities;

use Tests\Entities\Post;

class ChildPost extends Post
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }
}