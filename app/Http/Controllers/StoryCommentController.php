<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoryCommentController extends Controller
{
    public function store(StoreCommentRequest $request, Story $story): RedirectResponse
    {
        $story->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $request->validated('parent_id'),
            'body' => $request->validated('body'),
        ]);

        return back();
    }

    public function update(UpdateCommentRequest $request, Story $story, Comment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id, 403);

        $comment->update([
            'body' => $request->validated('body'),
        ]);

        return back();
    }

    public function destroy(Request $request, Story $story, Comment $comment): RedirectResponse
    {
        abort_unless(
            $comment->user_id === $request->user()->id || $request->user()->hasRole('admin'),
            403,
        );

        $comment->delete();

        return back();
    }
}
