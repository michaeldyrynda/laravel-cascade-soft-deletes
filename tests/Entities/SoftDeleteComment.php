<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeleteComment extends Model
{
    use SoftDeletes;
    protected $fillable = ['body'];

    protected $table = 'soft_delete_comments';

    public function post()
    {
        return $this->belongsTo('Tests\Entities\Post');
    }
}