<?php

use PHPUnit\Framework\TestCase;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Dyrynda\Database\Support\CascadeSoftDeleteException;

class CascadeSoftDeletesIntegrationTest extends TestCase
{
    public static function setupBeforeClass(): void
    {
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

            $table->foreign('author_id')->references('id')->on('authors');
            $table->foreign('posttype_id')->references('id')->on('post_types');
        });

        $manager->schema()->create('soft_delete_comments', function ($table) {
            $table->increments('id');
            $table->integer('post_id')->unsigned();
            $table->string('body');
            $table->timestamps();
            $table->softDeletes();
        });

        $manager->schema()->create('has_soft_deletes_pivots', function ($table) {
            $table->increments('id');
            $table->string('label');
            $table->timestamps();
        });

        $manager->schema()->create('authors__has_soft_deletes_pivots', function ($table) {
            $table->increments('id');
            $table->integer('author_id');
            $table->integer('hassoftdeletespivot_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('author_id')->references('id')->on('authors');
            $table->foreign('hassoftdeletespivot_id')->references('id')->on('has_soft_deleted_pivots');
        });

    }

    /** @test */
    public function it_cascades_deletes_when_deleting_a_parent_model()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to cascade soft deletes in Laravel',
            'body' => 'This is how you cascade soft deletes in Laravel',
        ]);

        $this->attachCommentsToPost($post);

        $this->assertCount(3, $post->comments);
        $post->delete();
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());


    }


    /** @test */
    public function it_cascades_deletes_entries_from_pivot_table()
    {
        $author = Tests\Entities\Author::create(['name' => 'ManyToManyTestAuthor']);

        $this->attachPostTypesToAuthor($author);
        $this->assertCount(2, $author->posttypes);

        $author->delete();

        $pivotEntries = Manager::table('authors__post_types')
            ->where('author_id', $author->id)
            ->get();

        $this->assertCount(0, $pivotEntries);


    }

    /** @test */
    public function it_cascades_deletes_when_force_deleting_a_parent_model()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to cascade soft deletes in Laravel',
            'body' => 'This is how you cascade soft deletes in Laravel',
        ]);

        $this->attachCommentsToPost($post);

        $this->assertCount(3, $post->comments);
        $post->forceDelete();
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());
        $this->assertCount(0, Tests\Entities\Post::withTrashed()->where('id', $post->id)->get());


    }

    /**
     * @test
     */
    public function it_takes_exception_to_models_that_do_not_implement_soft_deletes()
    {
        $this->expectException(CascadeSoftDeleteException::class);
        $this->expectExceptionMessage('Tests\Entities\NonSoftDeletingPost does not implement Illuminate\Database\Eloquent\SoftDeletes');

        $post = Tests\Entities\NonSoftDeletingPost::create([
            'title' => 'Testing when you can use this trait',
            'body' => 'Ensure that you can only use this trait if it uses SoftDeletes',
        ]);

        $this->attachCommentsToPost($post);

        $post->delete();
    }

    /**
     * @test
     */
    public function it_takes_exception_to_models_trying_to_cascade_deletes_on_invalid_relationships()
    {
        $this->expectException(CascadeSoftDeleteException::class);
        $this->expectExceptionMessage('Relationships [invalidRelationship, anotherInvalidRelationship] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation');

        $post = Tests\Entities\InvalidRelationshipPost::create([
            'title' => 'Testing invalid cascade relationships',
            'body' => 'Ensure you can only use this trait if the model defines valid relationships',
        ]);

        $this->attachCommentsToPost($post);

        $post->delete();
    }

    /** @test */
    public function it_ensures_that_no_deletes_are_performed_if_there_are_invalid_relationships()
    {
        $post = Tests\Entities\InvalidRelationshipPost::create([
            'title' => 'Testing deletes are not executed',
            'body' => 'If an invalid relationship is encountered, no deletes should be perofrmed',
        ]);

        $this->attachCommentsToPost($post);

        try {
            $post->delete();
        } catch (CascadeSoftDeleteException $e) {
            $this->assertNotNull(Tests\Entities\InvalidRelationshipPost::find($post->id));
            $this->assertCount(3, Tests\Entities\Comment::where('post_id', $post->id)->get());
        }
    }

    /** @test */
    public function it_can_accept_cascade_deletes_as_a_single_string()
    {
        $post = Tests\Entities\PostWithStringCascade::create([
            'title' => 'Testing you can use a string for a single relationship',
            'body' => 'This falls more closely in line with how other things work in Eloquent',
        ]);

        $this->attachCommentsToPost($post);

        $post->delete();

        $this->assertNull(Tests\Entities\Post::find($post->id));
        $this->assertCount(1, Tests\Entities\Post::withTrashed()->where('id', $post->id)->get());
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());


    }

    /**
     * @test
     */
    public function it_handles_situations_where_the_relationship_method_does_not_exist()
    {
        $this->expectException(CascadeSoftDeleteException::class);
        $this->expectExceptionMessage('Relationship [comments] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation');

        $post = Tests\Entities\PostWithMissingRelationshipMethod::create([
            'title' => 'Testing that missing relationship methods are accounted for',
            'body' => 'In this way, you need not worry about Laravel returning fatal errors',
        ]);

        $post->delete();
    }

    /** @test */
    public function it_handles_soft_deletes_inherited_from_a_parent_model()
    {
        $post = Tests\Entities\ChildPost::create([
            'title' => 'Testing child model inheriting model trait',
            'body' => 'This should allow a child class to inherit the soft deletes trait',
        ]);

        $this->attachCommentsToPost($post);

        $post->delete();

        $this->assertNull(Tests\Entities\ChildPost::find($post->id));
        $this->assertCount(1, Tests\Entities\ChildPost::withTrashed()->where('id', $post->id)->get());
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());


    }

    /** @test */
    public function it_handles_grandchildren()
    {
        $author = Tests\Entities\Author::create([
            'name' => 'Testing grandchildren are deleted',
        ]);

        $this->attachPostsAndCommentsToAuthor($author);

        $author->delete();

        $this->assertNull(Tests\Entities\Author::find($author->id));
        $this->assertCount(1, Tests\Entities\Author::withTrashed()->where('id', $author->id)->get());
        $this->assertCount(0, Tests\Entities\Post::where('author_id', $author->id)->get());

        $deletedPosts = Tests\Entities\Post::withTrashed()->where('author_id', $author->id)->get();
        $this->assertCount(2, $deletedPosts);

        foreach ($deletedPosts as $deletedPost) {
            $this->assertCount(0, Tests\Entities\Comment::where('post_id', $deletedPost->id)->get());
        }


    }

    /** @test */
    public function it_cascades_a_has_one_relationship()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'Cascade a has one relationship',
            'body' => 'This is how you cascade a has one relationship',
        ]);

        $type = new Tests\Entities\PostType(['label' => 'Test']);

        $post->postType()->save($type);

        $post->delete();
        $this->assertCount(0, Tests\Entities\PostType::where('id', $type->id)->get());


    }

    /** @test */
    public function it_restores_only_the_parent_model_when_child_not_implementing_soft_delete()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to do basic restore soft delete in Laravel',
            'body' => "It only restores parent model when child model doesn't implementing soft deletes",
        ]);

        $this->attachCommentsToPost($post);
        $this->assertCount(3, $post->comments);
        $post->delete();
        $post->restore();
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());
        $this->assertCount(1, Tests\Entities\Post::where('id', $post->id)->get());


    }

    /** @test */
    public function it_restores_the_parent_and_child_model_when_child_implementing_soft_delete()
    {
        $author = Tests\Entities\Author::create([
            'name' => 'Testing child can be restored',
        ]);

        $this->attachPostsToAuthor($author);

        $author->delete();

        $this->assertNull(Tests\Entities\Author::find($author->id));
        $this->assertCount(1, Tests\Entities\Author::withTrashed()->where('id', $author->id)->get());
        $this->assertCount(0, Tests\Entities\Post::where('author_id', $author->id)->get());

        $deletedPosts = Tests\Entities\Post::withTrashed()->where('author_id', $author->id)->get();
        $this->assertCount(2, $deletedPosts);

        $author->restore();

        $this->assertCount(1, Tests\Entities\Author::where('id', $author->id)->get());
        $this->assertCount(2, Tests\Entities\Post::where('author_id', $author->id)->get());


    }

    /** @test */
    public function it_restores_only_the_parent_model_when_pivot_not_implementing_soft_delete()
    {
        $author = Tests\Entities\Author::create(['name' => 'ManyToManyTestAuthor']);

        $this->attachPostTypesToAuthor($author);
        $this->assertCount(2, $author->posttypes);


        $author->delete();
        $author->restore();

        $pivotEntries = Manager::table('authors__post_types')
            ->where('author_id', $author->id)
            ->get();
        $this->assertCount(1, Tests\Entities\Author::where('id', $author->id)->get());
        $this->assertCount(0, $pivotEntries);


    }

    /** @test */
    public function it_restores_the_parent_and_pivot_model_when_pivot_implementing_soft_delete()
    {
        $author = Tests\Entities\Author::create(['name' => 'ManyToManyTestAuthor']);

        $this->attachHasSoftDeletesPivotsToAuthor($author);
        $this->assertCount(2, $author->hassoftdeletespivots);


        $author->delete();
        $author->restore();

        $pivotEntries = Manager::table('authors__has_soft_deletes_pivots')
            ->where('author_id', $author->id)
            ->get();
        $this->assertCount(1, Tests\Entities\Author::where('id', $author->id)->get());
        $this->assertCount(2, $pivotEntries);


    }

    /** @test */
    public function it_restores_the_parent_and_child_model_when_child_implementing_soft_delete_but_not_the_grandchild()
    {
        $author = Tests\Entities\Author::create([
            'name' => 'Testing child can be restored',
        ]);

        $this->attachPostsAndCommentsToAuthor($author);

        $author->delete();

        $this->assertNull(Tests\Entities\Author::find($author->id));
        $this->assertCount(1, Tests\Entities\Author::withTrashed()->where('id', $author->id)->get());
        $this->assertCount(0, Tests\Entities\Post::where('author_id', $author->id)->get());

        $deletedPosts = Tests\Entities\Post::withTrashed()->where('author_id', $author->id)->get();
        $this->assertCount(2, $deletedPosts);

        $author->restore();

        $this->assertCount(1, Tests\Entities\Author::where('id', $author->id)->get());
        $this->assertCount(2, Tests\Entities\Post::where('author_id', $author->id)->get());

        foreach ($deletedPosts as $deletedPost) {
            $this->assertCount(0, Tests\Entities\Comment::where('post_id', $deletedPost->id)->get());
        }



    }

    /** @test */
    public function it_restores_the_parent_the_child_and_the_grandchild_model_when_all_implementing_soft_delete()
    {
        $author = Tests\Entities\Author::create([
            'name' => 'Testing child can be restored',
        ]);

        $this->attachPostsAndSoftDeleteCommentsToAuthor($author);

        $author->delete();

        $this->assertNull(Tests\Entities\Author::find($author->id));
        $this->assertCount(1, Tests\Entities\Author::withTrashed()->where('id', $author->id)->get());
        $this->assertCount(0, Tests\Entities\Post::where('author_id', $author->id)->get());

        $deletedPosts = Tests\Entities\Post::withTrashed()->where('author_id', $author->id)->get();
        $this->assertCount(2, $deletedPosts);

        $author->restore();

        $this->assertCount(1, Tests\Entities\Author::where('id', $author->id)->get());
        $this->assertCount(2, Tests\Entities\Post::where('author_id', $author->id)->get());

        foreach ($deletedPosts as $deletedPost) {
            $this->assertCount(3, Tests\Entities\SoftDeleteComment::where('post_id', $deletedPost->id)->get());
        }



    }

    /** @test */
    public function it_cannot_restore_parent_and_child_model_when_force_deleting_a_parent_model()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to cascade soft deletes in Laravel',
            'body' => "It cannot restore parent model when force deleted the parent model",
        ]);

        $this->attachCommentsToPost($post);

        $this->assertCount(3, $post->comments);
        $post->forceDelete();
        $post->restore();
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());
        $this->assertCount(0, Tests\Entities\Post::withTrashed()->where('id', $post->id)->get());


    }

    /**
     * Attach some post types to the given author.
     *
     * @return void
     */
    public function attachPostTypesToAuthor($author)
    {
        $author->posttypes()->saveMany([

            Tests\Entities\PostType::create([
                'label' => 'First Post Type',
            ]),

            Tests\Entities\PostType::create([
                'label' => 'Second Post Type',
            ]),
        ]);
    }

    /**
     * Attach some post types to the given author.
     *
     * @return void
     */
    public function attachHasSoftDeletesPivotsToAuthor($author)
    {
        $author->hassoftdeletespivots()->saveMany([

            Tests\Entities\HasSoftDeletesPivot::create([
                'label' => 'First Pivot',
            ]),

            Tests\Entities\HasSoftDeletesPivot::create([
                'label' => 'Second Pivot',
            ]),
        ]);
    }

    /**
     * Attach some dummy posts (w/ comments) to the given author.
     *
     * @return void
     */
    private function attachPostsAndCommentsToAuthor($author)
    {
        $author->posts()->saveMany([
            $this->attachCommentsToPost(
                Tests\Entities\Post::create([
                    'title' => 'First post',
                    'body' => 'This is the first test post',
                ])
            ),
            $this->attachCommentsToPost(
                Tests\Entities\Post::create([
                    'title' => 'Second post',
                    'body' => 'This is the second test post',
                ])
            ),
        ]);

        return $author;
    }

    /**
     * Attach some dummy posts (w/ comments) to the given author.
     *
     * @return void
     */
    private function attachPostsAndSoftDeleteCommentsToAuthor($author)
    {
        $author->posts()->saveMany([
            $this->attachSoftDeleteCommentsToPost(
                Tests\Entities\Post::create([
                    'title' => 'First post',
                    'body' => 'This is the first test post',
                ])
            ),
            $this->attachSoftDeleteCommentsToPost(
                Tests\Entities\Post::create([
                    'title' => 'Second post',
                    'body' => 'This is the second test post',
                ])
            ),
        ]);

        return $author;
    }

    /**
     * Attach some dummy posts (w/ comments) to the given author.
     *
     * @return void
     */
    private function attachPostsToAuthor($author)
    {
        $author->posts()->saveMany([
            Tests\Entities\Post::create([
                'title' => 'First post',
                'body' => 'This is the first test post',
            ]),
            Tests\Entities\Post::create([
                'title' => 'Second post',
                'body' => 'This is the second test post',
            ])
        ]);

        return $author;
    }

    /**
     * Attach some dummy comments to the given post.
     *
     * @return void
     */
    private function attachCommentsToPost($post)
    {
        $post->comments()->saveMany([
            new Tests\Entities\Comment(['body' => 'This is the first test comment']),
            new Tests\Entities\Comment(['body' => 'This is the second test comment']),
            new Tests\Entities\Comment(['body' => 'This is the third test comment']),
        ]);

        return $post;
    }

    /**
     * Attach some dummy comments to the given post.
     *
     * @return void
     */
    private function attachSoftDeleteCommentsToPost($post)
    {
        $post->softDeleteComments()->saveMany([
            new Tests\Entities\SoftDeleteComment(['body' => 'This is the first test comment']),
            new Tests\Entities\SoftDeleteComment(['body' => 'This is the second test comment']),
            new Tests\Entities\SoftDeleteComment(['body' => 'This is the third test comment']),
        ]);

        return $post;
    }


}