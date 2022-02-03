<?php

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(DatabaseTestCase::class);

test('custom casting', function () {
    if (! class_exists(AsStringable::class)) {
        $this->markTestSkipped('Class AsStringable does not exist for the test to continue');
    }

    $model = new TestEloquentModelWithCustomCasts();

    $model->array_object = ['name' => 'Taylor'];
    $model->collection = collect(['name' => 'Taylor']);
    $model->stringable = Str::of('Taylor');

    $model->save();

    $model = $model->fresh();

    expect($model->array_object->toArray())->toEqual(['name' => 'Taylor']);
    expect($model->collection->toArray())->toEqual(['name' => 'Taylor']);
    expect((string) $model->stringable)->toEqual('Taylor');

    $model->array_object['age'] = 34;
    $model->array_object['meta']['title'] = 'Developer';

    $model->save();

    $model = $model->fresh();

    $this->assertEquals([
        'name' => 'Taylor',
        'age' => 34,
        'meta' => ['title' => 'Developer'],
    ], $model->array_object->toArray());
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('test_eloquent_model_with_custom_casts', function (Blueprint $table) {
        $table->increments('id');
        $table->text('array_object');
        $table->text('collection');
        $table->string('stringable');
        $table->timestamps();
    });
}
