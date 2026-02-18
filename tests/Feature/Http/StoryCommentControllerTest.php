<?php

use App\Models\Comment;
use App\Models\Story;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('displays comments on the story show page', function () {
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);

    $response = $this->get(route('stories.show', $story->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Stories/Show')
        ->has('comments', 1)
        ->where('comments.0.body', $comment->body)
    );
});

it('allows guests to view story page with comments', function () {
    $story = Story::factory()->create();
    Comment::factory()->count(3)->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);

    $response = $this->get(route('stories.show', $story->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('comments', 3)
    );
});

it('allows authenticated users to post a comment', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('stories.comments.store', $story), [
        'body' => 'This is a great story!',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('comments', [
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
        'user_id' => $user->id,
        'body' => 'This is a great story!',
    ]);
});

it('requires authentication to post a comment', function () {
    $story = Story::factory()->create();

    $response = $this->post(route('stories.comments.store', $story), [
        'body' => 'Test comment',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseCount('comments', 0);
});

it('validates body is required when posting a comment', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('stories.comments.store', $story), [
        'body' => '',
    ]);

    $response->assertSessionHasErrors(['body']);
    $this->assertDatabaseCount('comments', 0);
});

it('validates body max length of 1000 characters', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('stories.comments.store', $story), [
        'body' => str_repeat('a', 1001),
    ]);

    $response->assertSessionHasErrors(['body']);
    $this->assertDatabaseCount('comments', 0);
});

it('allows users to update their own comment', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
        'user_id' => $user->id,
        'body' => 'Original body',
    ]);

    $response = $this->actingAs($user)->put(
        route('stories.comments.update', [$story, $comment]),
        ['body' => 'Updated body'],
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'body' => 'Updated body',
    ]);
});

it('prevents users from updating another users comment', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
        'user_id' => $otherUser->id,
        'body' => 'Original body',
    ]);

    $response = $this->actingAs($user)->put(
        route('stories.comments.update', [$story, $comment]),
        ['body' => 'Hacked body'],
    );

    $response->assertForbidden();
    $this->assertDatabaseHas('comments', [
        'id' => $comment->id,
        'body' => 'Original body',
    ]);
});

it('allows users to delete their own comment', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(
        route('stories.comments.destroy', [$story, $comment]),
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('prevents users from deleting another users comment', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->delete(
        route('stories.comments.destroy', [$story, $comment]),
    );

    $response->assertForbidden();
    $this->assertDatabaseHas('comments', ['id' => $comment->id]);
});

it('allows admin to delete any comment', function () {
    Role::findOrCreate('admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);

    $response = $this->actingAs($admin)->delete(
        route('stories.comments.destroy', [$story, $comment]),
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});

it('requires authentication to update a comment', function () {
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);

    $response = $this->put(
        route('stories.comments.update', [$story, $comment]),
        ['body' => 'Updated body'],
    );

    $response->assertRedirect(route('login'));
});

it('requires authentication to delete a comment', function () {
    $story = Story::factory()->create();
    $comment = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);

    $response = $this->delete(
        route('stories.comments.destroy', [$story, $comment]),
    );

    $response->assertRedirect(route('login'));
});

it('passes isAdmin flag as false for regular users', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->get(route('stories.show', $story->slug));

    $response->assertInertia(fn ($page) => $page
        ->where('isAdmin', false)
    );
});

it('passes isAdmin flag as true for admin users', function () {
    Role::findOrCreate('admin');
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $story = Story::factory()->create();

    $response = $this->actingAs($admin)->get(route('stories.show', $story->slug));

    $response->assertInertia(fn ($page) => $page
        ->where('isAdmin', true)
    );
});

it('allows authenticated user to post a reply with parent_id', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $parent = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);

    $response = $this->actingAs($user)->post(route('stories.comments.store', $story), [
        'body' => 'This is a reply!',
        'parent_id' => $parent->id,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('comments', [
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
        'user_id' => $user->id,
        'parent_id' => $parent->id,
        'body' => 'This is a reply!',
    ]);
});

it('rejects replying to a reply (single-level nesting)', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $parent = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);
    $reply = Comment::factory()->reply($parent)->create();

    $response = $this->actingAs($user)->post(route('stories.comments.store', $story), [
        'body' => 'Nested reply attempt',
        'parent_id' => $reply->id,
    ]);

    $response->assertSessionHasErrors(['parent_id']);
});

it('rejects invalid parent_id', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();

    $response = $this->actingAs($user)->post(route('stories.comments.store', $story), [
        'body' => 'Reply to nothing',
        'parent_id' => 99999,
    ]);

    $response->assertSessionHasErrors(['parent_id']);
});

it('loads replies nested under parent comments on story show', function () {
    $story = Story::factory()->create();
    $parent = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);
    $reply = Comment::factory()->reply($parent)->create();

    $response = $this->get(route('stories.show', $story->slug));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('comments', 1)
        ->where('comments.0.id', $parent->id)
        ->has('comments.0.replies', 1)
        ->where('comments.0.replies.0.id', $reply->id)
    );
});

it('cascade deletes replies when parent comment is deleted', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $parent = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
        'user_id' => $user->id,
    ]);
    $reply = Comment::factory()->reply($parent)->create();

    $response = $this->actingAs($user)->delete(
        route('stories.comments.destroy', [$story, $parent]),
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('comments', ['id' => $parent->id]);
    $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
});

it('allows user to edit own reply', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $parent = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);
    $reply = Comment::factory()->reply($parent)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->put(
        route('stories.comments.update', [$story, $reply]),
        ['body' => 'Updated reply'],
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('comments', [
        'id' => $reply->id,
        'body' => 'Updated reply',
    ]);
});

it('allows user to delete own reply without affecting parent', function () {
    $user = User::factory()->create();
    $story = Story::factory()->create();
    $parent = Comment::factory()->create([
        'commentable_type' => Story::class,
        'commentable_id' => $story->id,
    ]);
    $reply = Comment::factory()->reply($parent)->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(
        route('stories.comments.destroy', [$story, $reply]),
    );

    $response->assertRedirect();
    $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
    $this->assertDatabaseHas('comments', ['id' => $parent->id]);
});
