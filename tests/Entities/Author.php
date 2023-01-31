<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;

class Author extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    public $dates = ['deleted_at'];

    protected $cascadeDeletes = ['posts', 'posttypes'];

    protected $fillable = ['name'];

    public function posts()
    {
        return $this->hasMany('Tests\Entities\Post');
    }

    public function posttypes()
    {
        return $this->belongsToMany('Tests\Entities\PostType', 'authors__post_types', 'author_id', 'posttype_id');
    }

    public function hassoftdeletespivots()
    {
        return $this->belongsToMany('Tests\Entities\HasSoftDeletesPivot', 'authors__has_soft_deletes_pivots', 'author_id', 'hassoftdeletespivot_id')->withTimestamps()->withPivot(['deleted_at']);
    }
}