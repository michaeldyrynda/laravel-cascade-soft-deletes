<?php

namespace Tests\Entities;

class PostWithLazyFetch extends Post
{
    protected $table = 'posts';

    protected $fetchMethod = 'lazy';

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }

    public function postType()
    {
        return $this->hasOne('Tests\Entities\PostType', 'post_id');
    }
}
