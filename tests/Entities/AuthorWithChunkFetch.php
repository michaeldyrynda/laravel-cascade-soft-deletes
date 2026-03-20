<?php

namespace Tests\Entities;

class AuthorWithChunkFetch extends Author
{
    protected $table = 'authors';

    protected $fetchMethod = 'chunk';

    public function posts()
    {
        return $this->hasMany('Tests\Entities\Post', 'author_id');
    }

    public function posttypes()
    {
        return $this->belongsToMany('Tests\Entities\PostType', 'authors__post_types', 'author_id', 'posttype_id');
    }
}
