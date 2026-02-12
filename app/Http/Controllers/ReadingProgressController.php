<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReadingProgressController extends Controller
{
    public function store(Request $request, Story $story): JsonResponse
    {
        $validated = $request->validate([
            'last_sentence_position' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ReadingStatus::class)],
        ]);

        $now = now();
        $status = ReadingStatus::from($validated['status']);

        $progress = $request->user()->readingProgress()->updateOrCreate(
            ['story_id' => $story->id],
            [
                'last_sentence_position' => $validated['last_sentence_position'],
                'status' => $status,
                'started_at' => $status !== ReadingStatus::NotStarted
                    ? $request->user()->readingProgress()->where('story_id', $story->id)->value('started_at') ?? $now
                    : null,
                'completed_at' => $status === ReadingStatus::Completed ? $now : null,
            ],
        );

        return response()->json(['progress' => $progress]);
    }
}
