<?php

namespace Tests\Entities;

use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostWithMissingRelationshipMethod extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $table = 'posts';

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = 'comments';

    protected $fillable = ['title', 'body'];
}
