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
}
