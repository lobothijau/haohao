<?php

namespace App\Http\Controllers;

use App\Models\UserVocabulary;
use App\Services\SrsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class UserVocabularyController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $vocabularies = $request->user()->vocabularies()
            ->with(['dictionaryEntry', 'sourceStory:id,title_zh,title_id,slug'])
            ->when($request->input('search'), function ($query, $search) {
                $query->whereHas('dictionaryEntry', function ($q) use ($search) {
                    $q->where('simplified', 'like', "%{$search}%")
                        ->orWhere('pinyin', 'like', "%{$search}%")
                        ->orWhere('meaning_id', 'like', "%{$search}%");
                });
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Vocabulary/Index', [
            'vocabularies' => $vocabularies,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request, SrsService $srsService): JsonResponse
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

        $srsService->createCardForVocabulary($vocabulary);

        return response()->json(['vocabulary' => $vocabulary], 201);
    }

    public function destroy(Request $request, UserVocabulary $vocabulary): Response
    {
        abort_unless($vocabulary->user_id === $request->user()->id, 403);

        $vocabulary->delete();

        return response()->noContent();
    }
}
