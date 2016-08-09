<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;

class PostType extends Model
{
    protected $fillable = ['label'];

    public function post()
    {
        return $this->belongsTo('Test\Entities\Post');
    }

    public function authors()
    {
        return $this->belongsToMany('Tests\Entities\Author', 'authors__post_types', 'posttype_id', 'author_id');
    }
}
