<?php

namespace Tests\Entities;

use Illuminate\Database\Eloquent\Model;


class HasSoftDeletesPivot extends Model
{
    protected $fillable = ['label'];


    public function authors()
    {
        return $this->belongsToMany('Tests\Entities\Author', 'authors__has_soft_deleted_pivots', 'hassoftdeletespivot_id', 'author_id')->withTimestamps()->withPivot(['deleted_at']);
    }

}