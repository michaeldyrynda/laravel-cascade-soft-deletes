<?php

namespace Tests\Entities;

use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostWithStringCascade extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $table = 'posts';

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = 'comments';

    protected $fillable = ['title', 'body'];

    public function comments()
    {
        return $this->hasMany('Tests\Entities\Comment', 'post_id');
    }
}
