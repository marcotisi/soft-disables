<?php

use Illuminate\Database\Connection;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use MarcoTisi\SoftEnable\SoftEnable;
use MarcoTisi\SoftEnable\SoftEnablingScope;

class DatabaseEloquentSoftEnableIntegrationTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->boolean('enabled')->default(1);
            $table->integer('group_id')->nullable();
            $table->string('email')->unique();
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->boolean('enabled')->default(1);
            $table->integer('user_id');
            $table->string('title');
            $table->timestamps();
        });

        $this->schema()->create('comments', function ($table) {
            $table->increments('id');
            $table->boolean('enabled')->default(1);
            $table->integer('owner_id')->nullable();
            $table->string('owner_type')->nullable();
            $table->integer('post_id');
            $table->string('body');
            $table->timestamps();
        });

        $this->schema()->create('addresses', function ($table) {
            $table->increments('id');
            $table->boolean('enabled')->default(1);
            $table->integer('user_id');
            $table->string('address');
            $table->timestamps();
        });

        $this->schema()->create('groups', function ($table) {
            $table->increments('id');
            $table->boolean('enabled')->default(1);
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('users');
        $this->schema()->drop('posts');
        $this->schema()->drop('comments');
    }

    /**
     * Tests...
     */
    public function testSoftEnableAreNotRetrieved()
    {
        $this->createUsers();

        $users = SoftEnableTestUser::all();

        $this->assertCount(1, $users);
        $this->assertEquals(2, $users->first()->id);
        $this->assertNull(SoftEnableTestUser::find(1));
    }

    public function testSoftEnableAreNotRetrievedFromBaseQuery()
    {
        $this->createUsers();

        $query = SoftEnableTestUser::query()->toBase();

        $this->assertInstanceOf(Builder::class, $query);
        $this->assertCount(1, $query->get());
    }

    public function testSoftEnableAreNotRetrievedFromBuilderHelpers()
    {
        $this->createUsers();

        $count = 0;
        $query = SoftEnableTestUser::query();
        $query->chunk(2, function ($user) use (&$count) {
            $count += count($user);
        });
        $this->assertEquals(1, $count);

        $query = SoftEnableTestUser::query();
        $this->assertCount(1, $query->pluck('email')->all());

        Paginator::currentPageResolver(function () {
            return 1;
        });

        $query = SoftEnableTestUser::query();
        $this->assertCount(1, $query->paginate(2)->all());

        $query = SoftEnableTestUser::query();
        $this->assertCount(1, $query->simplePaginate(2)->all());

        $this->assertEquals(0, SoftEnableTestUser::where('email', 'taylorotwell@gmail.com')->increment('id'));
        $this->assertEquals(0, SoftEnableTestUser::where('email', 'taylorotwell@gmail.com')->decrement('id'));
    }

    public function testWithDisabledReturnsAllRecords()
    {
        $this->createUsers();

        $this->assertCount(2, SoftEnableTestUser::withDisabled()->get());
        $this->assertInstanceOf(Eloquent::class, SoftEnableTestUser::withDisabled()->find(1));
    }

    public function testEnabledColumnCasting()
    {
        $this->createUsers();
        $this->assertSame(false, SoftEnableTestUser::withDisabled()->find(1)->enabled);
        $this->assertSame(true, SoftEnableTestUser::find(2)->enabled);
    }

    public function testEnableEnablesRecords()
    {
        $this->createUsers();
        $taylor = SoftEnableTestUser::withDisabled()->find(1);

        $this->assertTrue($taylor->isDisabled());
        $this->assertFalse($taylor->isEnabled());

        $taylor->enable();

        $users = SoftEnableTestUser::all();

        $this->assertCount(2, $users);
        $this->assertTrue($users->find(1)->enabled);
        $this->assertTrue($users->find(2)->enabled);
    }

    public function testOnlyDisabledOnlyReturnsDisabledRecords()
    {
        $this->createUsers();

        $users = SoftEnableTestUser::onlyDisabled()->get();

        $this->assertCount(1, $users);
        $this->assertEquals(1, $users->first()->id);
    }

    public function testOnlyWithoutDisabledOnlyReturnsDisabledRecords()
    {
        $this->createUsers();

        $users = SoftEnableTestUser::withoutDisabled()->get();

        $this->assertCount(1, $users);
        $this->assertEquals(2, $users->first()->id);

        $users = SoftEnableTestUser::withDisabled()->withoutDisabled()->get();

        $this->assertCount(1, $users);
        $this->assertEquals(2, $users->first()->id);
    }

    public function testFirstOrNew()
    {
        $this->createUsers();

        $result = SoftEnableTestUser::firstOrNew(['email' => 'taylorotwell@gmail.com']);
        $this->assertNull($result->id);

        $result = SoftEnableTestUser::withDisabled()->firstOrNew(['email' => 'taylorotwell@gmail.com']);
        $this->assertEquals(1, $result->id);
    }

    public function testFindOrNew()
    {
        $this->createUsers();

        $result = SoftEnableTestUser::findOrNew(1);
        $this->assertNull($result->id);

        $result = SoftEnableTestUser::withDisabled()->findOrNew(1);
        $this->assertEquals(1, $result->id);
    }

    public function testFirstOrCreate()
    {
        $this->createUsers();

        $result = SoftEnableTestUser::withDisabled()->firstOrCreate(['email' => 'taylorotwell@gmail.com']);
        $this->assertEquals('taylorotwell@gmail.com', $result->email);
        $this->assertCount(1, SoftEnableTestUser::all());

        $result = SoftEnableTestUser::firstOrCreate(['email' => 'foo@bar.com']);
        $this->assertEquals('foo@bar.com', $result->email);
        $this->assertCount(2, SoftEnableTestUser::all());
        $this->assertCount(3, SoftEnableTestUser::withDisabled()->get());
    }

    public function testUpdateOrCreate()
    {
        $this->createUsers();

        $result = SoftEnableTestUser::updateOrCreate(['email' => 'foo@bar.com'], ['email' => 'bar@baz.com']);
        $this->assertEquals('bar@baz.com', $result->email);
        $this->assertCount(2, SoftEnableTestUser::all());

        $result = SoftEnableTestUser::withDisabled()->updateOrCreate(['email' => 'taylorotwell@gmail.com'], ['email' => 'foo@bar.com']);
        $this->assertEquals('foo@bar.com', $result->email);
        $this->assertCount(2, SoftEnableTestUser::all());
        $this->assertCount(3, SoftEnableTestUser::withDisabled()->get());
    }

    public function testHasOneRelationshipCanBeSoftEnabled()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $abigail->address()->create(['address' => 'Laravel avenue 43']);

        // disable on builder
        $abigail->address()->disable();

        $abigail = $abigail->fresh();

        $this->assertNull($abigail->address);
        $this->assertEquals('Laravel avenue 43', $abigail->address()->withDisabled()->first()->address);

        // enable
        $abigail->address()->withDisabled()->enable();

        $abigail = $abigail->fresh();

        $this->assertEquals('Laravel avenue 43', $abigail->address->address);

        // disable on model
        $abigail->address->disable();

        $abigail = $abigail->fresh();

        $this->assertNull($abigail->address);
        $this->assertEquals('Laravel avenue 43', $abigail->address()->withDisabled()->first()->address);
    }

    public function testBelongsToRelationshipCanBeSoftEnabled()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $group = SoftEnableTestGroup::create(['name' => 'admin']);
        $abigail->group()->associate($group);
        $abigail->save();

        // disable on builder
        $abigail->group()->disable();

        $abigail = $abigail->fresh();

        $this->assertNull($abigail->group);
        $this->assertEquals('admin', $abigail->group()->withDisabled()->first()->name);

        // enable
        $abigail->group()->withDisabled()->enable();

        $abigail = $abigail->fresh();

        $this->assertEquals('admin', $abigail->group->name);

        // disable on model
        $abigail->group->disable();

        $abigail = $abigail->fresh();

        $this->assertNull($abigail->group);
        $this->assertEquals('admin', $abigail->group()->withDisabled()->first()->name);
    }

    public function testHasManyRelationshipCanBeSoftEnabled()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $abigail->posts()->create(['title' => 'First Title']);
        $abigail->posts()->create(['title' => 'Second Title']);

        // disable on builder
        $abigail->posts()->where('title', 'Second Title')->disable();

        $abigail = $abigail->fresh();

        $this->assertCount(1, $abigail->posts);
        $this->assertEquals('First Title', $abigail->posts->first()->title);
        $this->assertCount(2, $abigail->posts()->withDisabled()->get());

        // enable
        $abigail->posts()->withDisabled()->enable();

        $abigail = $abigail->fresh();

        $this->assertCount(2, $abigail->posts);
    }

    public function testSecondLevelRelationshipCanBeSoftEnabled()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post = $abigail->posts()->create(['title' => 'First Title']);
        $post->comments()->create(['body' => 'Comment Body']);

        $abigail->posts()->first()->comments()->disable();

        $abigail = $abigail->fresh();

        $this->assertCount(0, $abigail->posts()->first()->comments);
        $this->assertCount(1, $abigail->posts()->first()->comments()->withDisabled()->get());
    }

    public function testWhereHasWithDisabledRelationship()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post = $abigail->posts()->create(['title' => 'First Title']);

        $users = SoftEnableTestUser::where('email', 'taylorotwell@gmail.com')->has('posts')->get();
        $this->assertEquals(0, count($users));

        $users = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->has('posts')->get();
        $this->assertEquals(1, count($users));

        $users = SoftEnableTestUser::where('email', 'doesnt@exist.com')->orHas('posts')->get();
        $this->assertEquals(1, count($users));

        $users = SoftEnableTestUser::whereHas('posts', function ($query) {
            $query->where('title', 'First Title');
        })->get();
        $this->assertEquals(1, count($users));

        $users = SoftEnableTestUser::whereHas('posts', function ($query) {
            $query->where('title', 'Another Title');
        })->get();
        $this->assertEquals(0, count($users));

        $users = SoftEnableTestUser::where('email', 'doesnt@exist.com')->orWhereHas('posts', function ($query) {
            $query->where('title', 'First Title');
        })->get();
        $this->assertEquals(1, count($users));

        // With Post Disabled...

        $post->disable();
        $users = SoftEnableTestUser::has('posts')->get();
        $this->assertEquals(0, count($users));
    }

    /**
     * @group test
     */
    public function testWhereHasWithNestedDisabledRelationshipAndOnlyDisabledCondition()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post = $abigail->posts()->create(['title' => 'First Title']);
        $post->disable();

        $users = SoftEnableTestUser::has('posts')->get();
        $this->assertEquals(0, count($users));

        $users = SoftEnableTestUser::whereHas('posts', function ($q) {
            $q->onlyDisabled();
        })->get();
        $this->assertEquals(1, count($users));

        $users = SoftEnableTestUser::whereHas('posts', function ($q) {
            $q->withDisabled();
        })->get();
        $this->assertEquals(1, count($users));
    }

    /**
     * @group test
     */
    public function testWhereHasWithNestedDisabledRelationship()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post = $abigail->posts()->create(['title' => 'First Title']);
        $comment = $post->comments()->create(['body' => 'Comment Body']);
        $comment->disable();

        $users = SoftEnableTestUser::has('posts.comments')->get();
        $this->assertEquals(0, count($users));

        $users = SoftEnableTestUser::doesntHave('posts.comments')->get();
        $this->assertEquals(1, count($users));
    }

    /**
     * @group test
     */
    public function testWhereHasWithNestedDisabledRelationshipAndWithDisabledCondition()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUserWithDisabledPosts::where('email', 'abigailotwell@gmail.com')->first();
        $post = $abigail->posts()->create(['title' => 'First Title']);
        $post->disable();

        $users = SoftEnableTestUserWithDisabledPosts::has('posts')->get();
        $this->assertEquals(1, count($users));
    }

    /**
     * @group test
     */
    public function testWithCountWithNestedDisabledRelationshipAndOnlyDisabledCondition()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post1 = $abigail->posts()->create(['title' => 'First Title']);
        $post1->disable();
        $post2 = $abigail->posts()->create(['title' => 'Second Title']);
        $post3 = $abigail->posts()->create(['title' => 'Third Title']);

        $user = SoftEnableTestUser::withCount('posts')->orderBy('postsCount', 'desc')->first();
        $this->assertEquals(2, $user->posts_count);

        $user = SoftEnableTestUser::withCount(['posts' => function ($q) {
            $q->onlyDisabled();
        }])->orderBy('postsCount', 'desc')->first();
        $this->assertEquals(1, $user->posts_count);

        $user = SoftEnableTestUser::withCount(['posts' => function ($q) {
            $q->withDisabled();
        }])->orderBy('postsCount', 'desc')->first();
        $this->assertEquals(3, $user->posts_count);

        $user = SoftEnableTestUser::withCount(['posts' => function ($q) {
            $q->withDisabled()->where('title', 'First Title');
        }])->orderBy('postsCount', 'desc')->first();
        $this->assertEquals(1, $user->posts_count);

        $user = SoftEnableTestUser::withCount(['posts' => function ($q) {
            $q->where('title', 'First Title');
        }])->orderBy('postsCount', 'desc')->first();
        $this->assertEquals(0, $user->posts_count);
    }

    public function testOrWhereWithSoftEnableConstraint()
    {
        $this->createUsers();

        $users = SoftEnableTestUser::where('email', 'taylorotwell@gmail.com')->orWhere('email', 'abigailotwell@gmail.com');
        $this->assertEquals(['abigailotwell@gmail.com'], $users->pluck('email')->all());
    }

    public function testMorphToWithDisabled()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post1 = $abigail->posts()->create(['title' => 'First Title']);
        $post1->comments()->create([
            'body' => 'Comment Body',
            'owner_type' => SoftEnableTestUser::class,
            'owner_id' => $abigail->id,
        ]);

        $abigail->disable();

        $comment = SoftEnableTestCommentWithDisabled::with(['owner' => function ($q) {
            $q->withoutGlobalScope(SoftEnablingScope::class);
        }])->first();

        $this->assertEquals($abigail->email, $comment->owner->email);

        $comment = SoftEnableTestCommentWithDisabled::with(['owner' => function ($q) {
            $q->withDisabled();
        }])->first();

        $this->assertEquals($abigail->email, $comment->owner->email);

        $comment = TestCommentWithoutSoftEnable::with(['owner' => function ($q) {
            $q->withDisabled();
        }])->first();

        $this->assertEquals($abigail->email, $comment->owner->email);
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testMorphToWithBadMethodCall()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post1 = $abigail->posts()->create(['title' => 'First Title']);

        $post1->comments()->create([
            'body' => 'Comment Body',
            'owner_type' => SoftEnableTestUser::class,
            'owner_id' => $abigail->id,
        ]);

        TestCommentWithoutSoftEnable::with(['owner' => function ($q) {
            $q->thisMethodDoesNotExist();
        }])->first();
    }

    public function testMorphToWithConstraints()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post1 = $abigail->posts()->create(['title' => 'First Title']);
        $post1->comments()->create([
            'body' => 'Comment Body',
            'owner_type' => SoftEnableTestUser::class,
            'owner_id' => $abigail->id,
        ]);

        $comment = SoftEnableTestCommentWithDisabled::with(['owner' => function ($q) {
            $q->where('email', 'taylorotwell@gmail.com');
        }])->first();

        $this->assertEquals(null, $comment->owner);
    }

    public function testMorphToWithoutConstraints()
    {
        $this->createUsers();

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();
        $post1 = $abigail->posts()->create(['title' => 'First Title']);
        $comment1 = $post1->comments()->create([
            'body' => 'Comment Body',
            'owner_type' => SoftEnableTestUser::class,
            'owner_id' => $abigail->id,
        ]);

        $comment = SoftEnableTestCommentWithDisabled::with('owner')->first();

        $this->assertEquals($abigail->email, $comment->owner->email);

        $abigail->disable();
        $comment = SoftEnableTestCommentWithDisabled::with('owner')->first();

        $this->assertEquals(null, $comment->owner);
    }

    public function testModelEvents()
    {
        $this->createUsers();

        $results = [
            'enabling' => false,
            'enabled' => false,
            'disabling' => false,
            'disabled' => false,
        ];

        SoftEnableTestUser::enabling(function() use (&$results) {
            $results['enabling'] = true;
        });

        SoftEnableTestUser::enabled(function() use (&$results) {
            $results['enabled'] = true;
        });

        SoftEnableTestUser::disabling(function() use (&$results) {
            $results['disabling'] = true;
        });

        SoftEnableTestUser::disabled(function() use (&$results) {
            $results['disabled'] = true;
        });

        $taylor = SoftEnableTestUser::withDisabled()->where('email', 'taylorotwell@gmail.com')->first();
        $taylor->enable();

        $this->assertTrue($results['enabling']);
        $this->assertTrue($results['enabled']);

        $taylor->disable();

        $this->assertTrue($results['disabling']);
        $this->assertTrue($results['disabled']);
    }

    public function testReturnFalseStopDisabling()
    {
        SoftEnableTestUser::flushEventListeners();
        $this->createUsers();

        SoftEnableTestUser::disabling(function() {
            return false;
        });

        $abigail = SoftEnableTestUser::where('email', 'abigailotwell@gmail.com')->first();

        $abigail->disable();

        $this->assertFalse($abigail->isDisabled());
    }

    public function testReturnFalseStopEnabling()
    {
        SoftEnableTestUser::flushEventListeners();
        $this->createUsers();

        SoftEnableTestUser::enabling(function() {
            return false;
        });

        $taylor = SoftEnableTestUser::withDisabled()->where('email', 'taylorotwell@gmail.com')->first();

        $taylor->enable();

        $this->assertFalse($taylor->isEnabled());
    }

    /**
     * Helpers...
     */
    protected function createUsers()
    {
        $taylor = SoftEnableTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        $abigail = SoftEnableTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);

        $taylor->disable();
    }

    /**
     * Get a database connection instance.
     *
     * @return Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class TestUserWithoutSoftEnable extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(SoftEnableTestPost::class, 'user_id');
    }
}

/**
 * Eloquent Models...
 */
class SoftEnableTestUser extends Eloquent
{
    use SoftEnable;

    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(SoftEnableTestPost::class, 'user_id');
    }

    public function address()
    {
        return $this->hasOne(SoftEnableTestAddress::class, 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(SoftEnableTestGroup::class, 'group_id');
    }
}

class SoftEnableTestUserWithDisabledPosts extends Eloquent
{
    use SoftEnable;

    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(SoftEnableTestPost::class, 'user_id')->withDisabled();
    }
}

/**
 * Eloquent Models...
 */
class SoftEnableTestPost extends Eloquent
{
    use SoftEnable;

    protected $table = 'posts';
    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(SoftEnableTestComment::class, 'post_id');
    }
}

/**
 * Eloquent Models...
 */
class TestCommentWithoutSoftEnable extends Eloquent
{
    protected $table = 'comments';
    protected $guarded = [];

    public function owner()
    {
        return $this->morphTo();
    }
}

/**
 * Eloquent Models...
 */
class SoftEnableTestComment extends Eloquent
{
    use SoftEnable;

    protected $table = 'comments';
    protected $guarded = [];

    public function owner()
    {
        return $this->morphTo();
    }
}

class SoftEnableTestCommentWithDisabled extends Eloquent
{
    use SoftEnable;

    protected $table = 'comments';
    protected $guarded = [];

    public function owner()
    {
        return $this->morphTo();
    }
}

/**
 * Eloquent Models...
 */
class SoftEnableTestAddress extends Eloquent
{
    use SoftEnable;

    protected $table = 'addresses';
    protected $guarded = [];
}

/**
 * Eloquent Models...
 */
class SoftEnableTestGroup extends Eloquent
{
    use SoftEnable;

    protected $table = 'groups';
    protected $guarded = [];

    public function users()
    {
        $this->hasMany(SoftEnableTestUser::class);
    }
}
