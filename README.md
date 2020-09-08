# Cascading soft deletes for the Laravel PHP Framework

[![Build Status](https://github.com/michaeldyrynda/laravel-cascade-soft-deletes/workflows/run-tests/badge.svg)](https://github.com/michaeldyrynda/laravel-cascade-soft-deletes/actions?query=workflow%3Arun-tests)
[![Latest Stable Version](https://poser.pugx.org/dyrynda/laravel-cascade-soft-deletes/v/stable)](https://packagist.org/packages/dyrynda/laravel-cascade-soft-deletes)
[![Total Downloads](https://poser.pugx.org/dyrynda/laravel-cascade-soft-deletes/downloads)](https://packagist.org/packages/dyrynda/laravel-cascade-soft-deletes)
[![License](https://poser.pugx.org/dyrynda/laravel-cascade-soft-deletes/license)](https://packagist.org/packages/dyrynda/laravel-cascade-soft-deletes)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/michaeldyrynda/laravel-cascade-soft-deletes)

## Introduction

In scenarios when you delete a parent record - say for example a blog post - you may want to also delete any comments associated with it as a form of self-maintenance of your data.

Normally, you would use your database's foreign key constraints, adding an `ON DELETE CASCADE` rule to the foreign key constraint in your comments table.

It may be useful to be able to restore a parent record after it was deleted. In those instances, you may reach for Laravel's [soft deleting](https://laravel.com/docs/5.2/eloquent#soft-deleting) functionality.

In doing so, however, you lose the ability to use the cascading delete functionality that your database would otherwise provide. That is where this package aims to bridge the gap in functionality when using the `SoftDeletes` trait.

## Code Samples

```php
<?php

namespace App;

use App\Comment;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    protected $cascadeDeletes = ['comments'];

    protected $dates = ['deleted_at'];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
```

Now you can delete an `App\Post` record, and any associated `App\Comment` records will be deleted. If the `App\Comment` record implements the `CascadeSoftDeletes` trait as well, it's children will also be deleted and so on.

```php
$post = App\Post::find($postId)
$post->delete(); // Soft delete the post, which will also trigger the delete() method on any comments and their children.
```

**Note**: It's important to know that when you cascade your soft deleted child records, there is no way to know which were deleted by the cascading operation, and which were deleted prior to that. This means that when you restore the blog post, the associated comments will not be.

Because this trait hooks into the `deleting` Eloquent model event, we can prevent the parent record from being deleted as well as any child records, if any exception is triggered. A `LogicException` will be triggered if the model does not use the `Illuminate\Database\Eloquent\SoftDeletes` trait, or if any of the defined `cascadeDeletes` relationships do not exist, or do not return an instance of `Illuminate\Database\Eloquent\Relations\Relation`.

**Additional Note**:  If you already have existing event listeners in place for a model that is going to cascade soft deletes, you can adjust the priority or firing order of events to have CascadeSoftDeletes fire after your event.  To do this you can set the priority of your deleting event listener to be 1.

`MODEL::observe( MODELObserver::class, 1 );`  The second param is the priority.

`MODEL::deleting( MODELObserver::class, 1 );`

As of right now this is not documented in the Larvel docs, but just know that the default priority is `0` for all listeners, and that `0` is the lowest priority.  Passing a param of greater than `0` to your listener will cause your listener to fire before listeners with default priority of `0`


## Installation

This trait is installed via [Composer](http://getcomposer.org/). To install, simply add to your `composer.json` file:

```
$ composer require dyrynda/laravel-cascade-soft-deletes
```

## Support

If you are having general issues with this package, feel free to contact me on [Twitter](https://twitter.com/michaeldyrynda).

If you believe you have found an issue, please report it using the [GitHub issue tracker](https://github.com/michaeldyrynda/laravel-cascade-soft-deletes/issues), or better yet, fork the repository and submit a pull request.

If you're using this package, I'd love to hear your thoughts. Thanks!

## Treeware

You're free to use this package, but if it makes it to your production environment you are required to buy the world a tree.

It’s now common knowledge that one of the best tools to tackle the climate crisis and keep our temperatures from rising above 1.5C is to plant trees. If you support this package and contribute to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.

You can buy trees [here](https://plant.treeware.earth/michaeldyrynda/laravel-cascade-soft-deletes)

Read more about Treeware at [treeware.earth](https://treeware.earth)
