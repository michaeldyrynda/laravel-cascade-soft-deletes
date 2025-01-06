<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $fillable = ['body'];

    public function post()
    {
        return $this->belongsTo('Tests\Entities\Post');
    }
}
