<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('without events registers booted listeners for later', function () {
    $model = AutoFilledModel::withoutEvents(function () {
        return AutoFilledModel::create();
    });

    expect($model->project)->toBeNull();

    $model->save();

    expect($model->project)->toBe('Laravel');
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('auto_filled_models', function (Blueprint $table) {
        $table->increments('id');
        $table->text('project')->nullable();
    });
}

function boot()
{
    parent::boot();

    static::saving(function ($model) {
        $model->project = 'Laravel';
    });
}