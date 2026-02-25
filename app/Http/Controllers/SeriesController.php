<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeriesController extends Controller
{
    public function index(Request $request): Response
    {
        $series = Series::query()
            ->where('is_published', true)
            ->withCount(['stories' => fn ($q) => $q->where('is_published', true)])
            ->when($request->input('hsk_level'), fn ($query, $level) => $query->where('hsk_level', $level))
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title_zh', 'like', "%{$search}%")
                        ->orWhere('title_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Series/Index', [
            'series' => $series,
            'filters' => (object) $request->only(['hsk_level', 'search']),
        ]);
    }

    public function show(Series $series): Response
    {
        abort_unless($series->is_published, 404);

        $stories = $series->stories()
            ->where('is_published', true)
            ->with('categories')
            ->get();

        $chapterProgress = [];
        $user = auth()->user();
        if ($user) {
            $chapterProgress = $user->readingProgress()
                ->whereIn('story_id', $stories->pluck('id'))
                ->get()
                ->keyBy('story_id');
        }

        return Inertia::render('Series/Show', [
            'series' => $series,
            'stories' => $stories,
            'chapterProgress' => $chapterProgress,
        ]);
    }
}
