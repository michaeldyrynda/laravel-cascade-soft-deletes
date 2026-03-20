<?php

namespace Tests\Entities;

class PostWithChunkFetch extends Post
{
    protected $table = 'posts';

    protected $fetchMethod = 'chunk';

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }

    public function postType()
    {
        return $this->hasOne('Tests\Entities\PostType', 'post_id');
    }
}
