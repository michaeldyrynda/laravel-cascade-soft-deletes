<?php

use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CascadeSoftDeletesIntegrationTest extends PHPUnit_Framework_TestCase
{
    public static function setupBeforeClass()
    {
        $manager = new Manager();
        $manager->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $manager->setEventDispatcher(new Dispatcher(new Container()));

        $manager->setAsGlobal();
        $manager->bootEloquent();

        $manager->schema()->create('posts', function ($table) {
            $table->increments('id');
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
    }


    /** @test */
    public function it_cascades_deletes_when_deleting_a_parent_model()
    {
        $post = Tests\Entities\Post::create([
            'title' => 'How to cascade soft deletes in Laravel',
            'body'  => 'This is how you cascade soft deletes in Laravel',
        ]);

        $post->comments()->saveMany([
            new Tests\Entities\Comment(['body' => 'This is the first test comment']),
            new Tests\Entities\Comment(['body' => 'This is the second test comment']),
            new Tests\Entities\Comment(['body' => 'This is the third test comment']),
        ]);


        $this->assertCount(3, $post->comments);
        $post->delete();
        $this->assertCount(0, Tests\Entities\Comment::where('post_id', $post->id)->get());
    }

    /**
     * @test
     * @expectedException \RuntimeException
     */
    public function it_takes_excepion_to_models_that_do_not_implement_soft_deletes()
    {
        $post = Tests\Entities\NonSoftDeletingPost::create([
            'title' => 'Testing when you can use this trait',
            'body'  => 'Ensure that you can only use this trait if it uses SoftDeletes',
        ]);

        $post->comments()->saveMany([
            new Tests\Entities\Comment(['body' => 'This is the first test comment']),
            new Tests\Entities\Comment(['body' => 'This is the second test comment']),
            new Tests\Entities\Comment(['body' => 'This is the third test comment']),
        ]);

        $this->assertCount(3, $post->comments);
        $post->delete();
    }
}
