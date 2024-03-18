<?php

use Dyrynda\Database\Support\CascadeSoftDeleteException;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Tests\Entities\Author;
use Tests\Entities\ChildPost;
use Tests\Entities\Comment;
use Tests\Entities\InvalidRelationshipPost;
use Tests\Entities\NonSoftDeletingPost;
use Tests\Entities\Post;
use Tests\Entities\PostType;
use Tests\Entities\PostWithMissingRelationshipMethod;
use Tests\Entities\PostWithStringCascade;

beforeAll(function () {
    $manager = new Manager();
    $manager->addConnection([
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    $manager->setEventDispatcher(new Dispatcher(new Container()));

    $manager->setAsGlobal();
    $manager->bootEloquent();

    $manager->schema()->create('authors', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
        $table->softDeletes();
    });

    $manager->schema()->create('posts', function ($table) {
        $table->increments('id');
        $table->integer('author_id')->unsigned()->nullable();
        $table->string('title');
        $table->string('body');
        $table->timestamps();
        $table->softDeletes();
    });

    $manager->schema()->create('comments', function ($table) {
        $table->increments('id');
        $table->integer('post_id')->unsigned();
        $table->string('body');
        $table->timestamps();
    });

    $manager->schema()->create('post_types', function ($table) {
        $table->increments('id');
        $table->integer('post_id')->unsigned()->nullable();
        $table->string('label');
        $table->timestamps();
    });

    $manager->schema()->create('authors__post_types', function ($table) {
        $table->increments('id');
        $table->integer('author_id');
        $table->integer('posttype_id');
        $table->timestamps();

        $table->foreign('author_id')->references('id')->on('author');
        $table->foreign('posttype_id')->references('id')->on('post_types');
    });
});

it('cascades deletes when deleting a parent model', function () {
    $post = Post::create([
        'title' => 'How to cascade soft deletes in Laravel',
        'body' => 'This is how you cascade soft deletes in Laravel',
    ]);

    attachCommentsToPost($post);

    expect($post->comments)->toHaveCount(3);

    $post->delete();

    expect(Comment::where('post_id', $post->id)->get())->toHaveCount(0);
});

it('cascades deletes entries from pivot table', function () {
    $author = Author::create(['name' => 'ManyToManyTestAuthor']);

    attachPostTypesToAuthor($author);
    expect($author->posttypes)->toHaveCount(2);

    $author->delete();

    $pivotEntries = Manager::table('authors__post_types')
        ->where('author_id', $author->id)
        ->get();

    expect($pivotEntries)->toHaveCount(0);
});

it('cascades deletes when force deleting a parent model', function () {
    $post = Post::create([
        'title' => 'How to cascade soft deletes in Laravel',
        'body' => 'This is how you cascade soft deletes in Laravel',
    ]);

    attachCommentsToPost($post);

    expect($post->comments)->toHaveCount(3);

    $post->forceDelete();

    expect(Comment::where('post_id', $post->id)->get())->toHaveCount(0);
    expect(Post::withTrashed()->where('id', $post->id)->get())->toHaveCount(0);
});

it('takes exception to models that do not implement soft deletes', function () {
    $post = NonSoftDeletingPost::create([
        'title' => 'Testing when you can use this trait',
        'body' => 'Ensure that you can only use this trait if it uses SoftDeletes',
    ]);

    attachCommentsToPost($post);

    $post->delete();
})->throws(
    CascadeSoftDeleteException::class,
    'Tests\Entities\NonSoftDeletingPost does not implement Illuminate\Database\Eloquent\SoftDeletes'
);

it('takes exception to models trying to cascade deletes on invalid relationships', function () {
    $post = InvalidRelationshipPost::create([
        'title' => 'Testing invalid cascade relationships',
        'body' => 'Ensure you can only use this trait if the model defines valid relationships',
    ]);

    attachCommentsToPost($post);

    $post->delete();
})->throws(
    CascadeSoftDeleteException::class,
    'Relationships [invalidRelationship, anotherInvalidRelationship] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation'
);

it('ensures that no deletes are performed if there are invalid relationships', function () {
    $post = InvalidRelationshipPost::create([
        'title' => 'Testing deletes are not executed',
        'body' => 'If an invalid relationship is encountered, no deletes should be perofrmed',
    ]);

    attachCommentsToPost($post);

    try {
        $post->delete();
    } catch (CascadeSoftDeleteException) {
        expect(InvalidRelationshipPost::find($post->id))->not->toBeNull();
        expect(Comment::where('post_id', $post->id)->get())->toHaveCount(3);
    }
});

it('can accept cascade deletes as a single string', function () {
    $post = PostWithStringCascade::create([
        'title' => 'Testing you can use a string for a single relationship',
        'body' => 'This falls more closely in line with how other things work in Eloquent',
    ]);

    attachCommentsToPost($post);

    $post->delete();

    expect(Post::find($post->id))->toBeNull();
    expect(Post::withTrashed()->where('id', $post->id)->get())->toHaveCount(1);
    expect(Comment::where('post_id', $post->id)->get())->toHaveCount(0);
});

it('handles situations where the relationship method does not exist', function () {
    $post = PostWithMissingRelationshipMethod::create([
        'title' => 'Testing that missing relationship methods are accounted for',
        'body' => 'In this way, you need not worry about Laravel returning fatal errors',
    ]);

    $post->delete();
})->throws(
    CascadeSoftDeleteException::class,
    'Relationship [comments] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation'
);

it('handles soft deletes inherited from a parent model', function () {
    $post = ChildPost::create([
        'title' => 'Testing child model inheriting model trait',
        'body' => 'This should allow a child class to inherit the soft deletes trait',
    ]);

    attachCommentsToPost($post);

    $post->delete();

    expect(ChildPost::find($post->id))->toBeNull();
    expect(ChildPost::withTrashed()->where('id', $post->id)->get())->toHaveCount(1);
    expect(Comment::where('post_id', $post->id)->get())->toHaveCount(0);
});

it('handles grandchildren', function () {
    $author = Author::create([
        'name' => 'Testing grandchildren are deleted',
    ]);

    attachPostsAndCommentsToAuthor($author);

    $author->delete();

    expect(Author::find($author->id))->toBeNull();
    expect(Author::withTrashed()->where('id', $author->id)->get())->toHaveCount(1);
    expect(Post::where('author_id', $author->id)->get())->toHaveCount(0);

    $deletedPosts = Post::withTrashed()->where('author_id', $author->id)->get();

    expect($deletedPosts)->toHaveCount(2);

    foreach ($deletedPosts as $deletedPost) {
        expect(Comment::where('post_id', $deletedPost->id)->get())->toHaveCount(0);
    }
});

it('cascades a has one relationship', function () {
    $post = Post::create([
        'title' => 'Cascade a has one relationship',
        'body' => 'This is how you cascade a has one relationship',
    ]);

    $type = new PostType(['label' => 'Test']);

    $post->postType()->save($type);

    $post->delete();

    expect(PostType::where('id', $type->id)->get())->toHaveCount(0);
});

/**
 * Attach some post types to the given author.
 *
 * @return void
 */
function attachPostTypesToAuthor($author)
{
    $author->posttypes()->saveMany([
        PostType::create([
            'label' => 'First Post Type',
        ]),

        PostType::create([
            'label' => 'Second Post Type',
        ]),
    ]);
}

/**
 * Attach some dummy posts (w/ comments) to the given author.
 *
 * @return void
 */
function attachPostsAndCommentsToAuthor($author)
{
    $author->posts()->saveMany([
        attachCommentsToPost(Post::create([
            'title' => 'First post',
            'body' => 'This is the first test post',
        ])),
        attachCommentsToPost(Post::create([
            'title' => 'Second post',
            'body' => 'This is the second test post',
        ])),
    ]);

    return $author;
}

/**
 * Attach some dummy comments to the given post.
 *
 * @return void
 */
function attachCommentsToPost($post)
{
    $post->comments()->saveMany([
        new Comment(['body' => 'This is the first test comment']),
        new Comment(['body' => 'This is the second test comment']),
        new Comment(['body' => 'This is the third test comment']),
    ]);

    return $post;
}
