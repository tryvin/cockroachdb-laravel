<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use YlsIdeas\CockroachDb\Tests\Integration\Database\DatabaseTestCase;

uses(DatabaseTestCase::class);

test('update model with default with count', function () {
    $post = Post::create(['title' => Str::random()]);

    $post->update(['title' => 'new name']);

    expect($post->title)->toBe('new name');
});

test('self referencing existence query', function () {
    $post = Post::create(['title' => 'foo']);

    $comment = tap((new Comment(['id' => 1, 'name' => 'foo']))->commentable()->associate($post))->save();

    (new Comment(['id' => 2, 'name' => 'bar']))->commentable()->associate($comment)->save();

    $comments = Comment::has('replies')->get();

    expect($comments->pluck('id')->all())->toEqual([1]);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->increments('id');
        $table->string('title');
        $table->timestamps();
    });

    Schema::create('comments', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->integer('commentable_id');
        $table->string('commentable_type');
        $table->timestamps();
    });

    Carbon::setTestNow(null);
}

function comments()
{
    return test()->morphMany(Comment::class, 'commentable');
}

function commentable()
{
    return test()->morphTo();
}

function replies()
{
    return test()->morphMany(self::class, 'commentable');
}