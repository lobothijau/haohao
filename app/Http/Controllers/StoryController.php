<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Story;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoryController extends Controller
{
    public function index(Request $request): Response
    {
        $stories = Story::query()
            ->where('is_published', true)
            ->with('categories')
            ->when($request->input('hsk_level'), fn ($query, $level) => $query->where('hsk_level', $level))
            ->when($request->input('category'), function ($query, $slug) {
                $query->whereHas('categories', fn ($q) => $q->where('slug', $slug));
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title_zh', 'like', "%{$search}%")
                        ->orWhere('title_id', 'like', "%{$search}%");
                });
            })
            ->when($request->input('sort'), function ($query, $sort) {
                match ($sort) {
                    'hsk_level' => $query->orderBy('hsk_level'),
                    'difficulty_score' => $query->orderBy('difficulty_score'),
                    default => $query->latest(),
                };
            }, fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Stories/Index', [
            'stories' => $stories,
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'filters' => $request->only(['hsk_level', 'category', 'search', 'sort']),
        ]);
    }

    public function show(Story $story): Response
    {
        abort_unless($story->is_published, 404);

        $story->load(['sentences.words.dictionaryEntry', 'categories']);

        $user = auth()->user();
        $progress = null;
        $savedVocabularyIds = [];

        if ($user) {
            $progress = $story->readingProgress()
                ->where('user_id', $user->id)
                ->first();

            $savedVocabularyIds = $user->vocabularies()
                ->whereIn('dictionary_entry_id', function ($query) use ($story) {
                    $query->select('dictionary_entry_id')
                        ->from('sentence_words')
                        ->whereIn('story_sentence_id', function ($q) use ($story) {
                            $q->select('id')
                                ->from('story_sentences')
                                ->where('story_id', $story->id);
                        });
                })
                ->pluck('dictionary_entry_id')
                ->toArray();
        }

        return Inertia::render('Stories/Show', [
            'story' => $story,
            'sentences' => $story->sentences,
            'progress' => $progress,
            'savedVocabularyIds' => $savedVocabularyIds,
        ]);
    }
}
