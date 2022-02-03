<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(DatabaseTestCase::class);

test('where and where or behavior', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\UserWhereTest $firstUser */
    $firstUser = UserWhereTest::create([
        'name' => 'test-name',
        'email' => 'test-email',
        'address' => 'test-address',
    ]);

    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\UserWhereTest $secondUser */
    $secondUser = UserWhereTest::create([
        'name' => 'test-name1',
        'email' => 'test-email1',
        'address' => 'test-address1',
    ]);

    expect($firstUser->is(UserWhereTest::where('name', '=', $firstUser->name)->first()))->toBeTrue();
    expect($firstUser->is(UserWhereTest::where('name', $firstUser->name)->first()))->toBeTrue();
    expect($firstUser->is(UserWhereTest::where('name', $firstUser->name)->where('email', $firstUser->email)->first()))->toBeTrue();
    expect(UserWhereTest::where('name', $firstUser->name)->where('email', $secondUser->email)->first())->toBeNull();
    expect($secondUser->is(UserWhereTest::where('name', 'wrong-name')->orWhere('email', $secondUser->email)->first()))->toBeTrue();
    expect($firstUser->is(UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email'])->first()))->toBeTrue();
    expect(UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email1'])->first())->toBeNull();
    $this->assertTrue(
        $secondUser->is(
            UserWhereTest::where(['name' => 'wrong-name', 'email' => 'test-email1'], null, null, 'or')->first()
        )
    );

    $this->assertSame(
        1,
        UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email1'])
            ->orWhere(['name' => 'test-name1', 'address' => 'wrong-address'])->count()
    );

    $this->assertTrue(
        $secondUser->is(
            UserWhereTest::where(['name' => 'test-name', 'email' => 'test-email1'])
                ->orWhere(['name' => 'test-name1', 'address' => 'wrong-address'])
                ->first()
        )
    );
});

test('first where', function () {
    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\UserWhereTest $firstUser */
    $firstUser = UserWhereTest::create([
        'name' => 'test-name',
        'email' => 'test-email',
        'address' => 'test-address',
    ]);

    /** @var \YlsIdeas\CockroachDb\Tests\Integration\Database\UserWhereTest $secondUser */
    $secondUser = UserWhereTest::create([
        'name' => 'test-name1',
        'email' => 'test-email1',
        'address' => 'test-address1',
    ]);

    expect($firstUser->is(UserWhereTest::firstWhere('name', '=', $firstUser->name)))->toBeTrue();
    expect($firstUser->is(UserWhereTest::firstWhere('name', $firstUser->name)))->toBeTrue();
    expect($firstUser->is(UserWhereTest::where('name', $firstUser->name)->firstWhere('email', $firstUser->email)))->toBeTrue();
    expect(UserWhereTest::where('name', $firstUser->name)->firstWhere('email', $secondUser->email))->toBeNull();
    expect($firstUser->is(UserWhereTest::firstWhere(['name' => 'test-name', 'email' => 'test-email'])))->toBeTrue();
    expect(UserWhereTest::firstWhere(['name' => 'test-name', 'email' => 'test-email1']))->toBeNull();
    $this->assertTrue(
        $secondUser->is(
            UserWhereTest::firstWhere(['name' => 'wrong-name', 'email' => 'test-email1'], null, null, 'or')
        )
    );
});

test('sole', function () {
    $expected = UserWhereTest::create([
        'name' => 'test-name',
        'email' => 'test-email',
        'address' => 'test-address',
    ]);

    expect($expected->is(UserWhereTest::where('name', 'test-name')->sole()))->toBeTrue();
});

test('sole fails for multiple records', function () {
    UserWhereTest::create([
        'name' => 'test-name',
        'email' => 'test-email',
        'address' => 'test-address',
    ]);

    UserWhereTest::create([
        'name' => 'test-name',
        'email' => 'other-email',
        'address' => 'other-address',
    ]);

    $this->expectException(MultipleRecordsFoundException::class);

    UserWhereTest::where('name', 'test-name')->sole();
});

test('sole fails if no records', function () {
    try {
        UserWhereTest::where('name', 'test-name')->sole();
    } catch (ModelNotFoundException $exception) {
        //
    }

    expect($exception->getModel())->toBe(UserWhereTest::class);
});

test('chunk map', function () {
    UserWhereTest::create([
        'name' => 'first-name',
        'email' => 'first-email',
        'address' => 'first-address',
    ]);

    UserWhereTest::create([
        'name' => 'second-name',
        'email' => 'second-email',
        'address' => 'second-address',
    ]);

    DB::enableQueryLog();

    $results = UserWhereTest::orderBy('id')->chunkMap(function ($user) {
        return $user->name;
    }, 1);

    expect($results)->toHaveCount(2);
    expect($results[0])->toBe('first-name');
    expect($results[1])->toBe('second-name');
    expect(DB::getQueryLog())->toHaveCount(3);
});

// Helpers
function defineDatabaseMigrationsAfterDatabaseRefreshed()
{
    Schema::create('users', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('email');
        $table->string('address');
    });
}