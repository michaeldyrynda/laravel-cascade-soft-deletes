<?php

namespace Tests\Entities;

class ChildPost extends Post
{
    protected $table = 'posts';

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }
}
