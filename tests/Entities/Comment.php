<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;
use Tests\Entities\Post;

class Comment extends Model
{
    protected $fillable = ['body'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
