<?php

namespace App\Http\Controllers;

use App\Models\UserVocabulary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserVocabularyController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'dictionary_entry_id' => ['required', 'exists:dictionary_entries,id'],
            'source_story_id' => ['nullable', 'exists:stories,id'],
            'source_sentence_id' => ['nullable', 'exists:story_sentences,id'],
        ]);

        $vocabulary = $request->user()->vocabularies()->firstOrCreate(
            ['dictionary_entry_id' => $validated['dictionary_entry_id']],
            [
                'source_story_id' => $validated['source_story_id'] ?? null,
                'source_sentence_id' => $validated['source_sentence_id'] ?? null,
            ],
        );

        return response()->json(['vocabulary' => $vocabulary], 201);
    }

    public function destroy(Request $request, UserVocabulary $vocabulary): Response
    {
        abort_unless($vocabulary->user_id === $request->user()->id, 403);

        $vocabulary->delete();

        return response()->noContent();
    }
}
