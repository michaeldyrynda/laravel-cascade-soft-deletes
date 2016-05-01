# Cascading soft deletes for the Laravel PHP Framework
## v1.0.0

![Travis Build Status](https://travis-ci.org/michaeldyrynda/laravel-cascade-soft-deletes.svg?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/michaeldyrynda/laravel-cascade-soft-deletes/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/michaeldyrynda/laravel-cascade-soft-deletes/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/iatstuti/laravel-cascade-soft-deletes/v/stable)](https://packagist.org/packages/iatstuti/laravel-cascade-soft-deletes)
[![Total Downloads](https://poser.pugx.org/iatstuti/laravel-cascade-soft-deletes/downloads)](https://packagist.org/packages/iatstuti/laravel-cascade-soft-deletes)
[![License](https://poser.pugx.org/iatstuti/laravel-cascade-soft-deletes/license)](https://packagist.org/packages/iatstuti/laravel-cascade-soft-deletes)

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
use Iatstuti\Database\Support\CascadeSoftDeletes;
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

*Note* It's important to know that when you cascade your soft deleted child records, there is no way to know which were deleted by the cascading operation, and which were deleted prior to that. This means that when you restore the blog post, the associated comments will not be.

## Installation

This trait is installed via [Composer](http://getcomposer.org/). To install, simply add to your `composer.json` file:

```
$ composer require iatstuti/laravel-cascade-soft-deletes="1.0.*"
```

## Support

If you are having general issues with this package, feel free to contact me on [Twitter](https://twitter.com/michaeldyrynda).

If you believe you have found an issue, please report it using the [GitHub issue tracker](https://github.com/michaeldyrynda/laravel-cascade-soft-deletes/issues), or better yet, fork the repository and submit a pull request.

If you're using this package, I'd love to hear your thoughts. Thanks!
