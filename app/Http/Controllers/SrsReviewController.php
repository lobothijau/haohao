<?php

namespace App\Http\Controllers;

use App\Enums\SrsRating;
use App\Models\SrsCard;
use App\Services\SrsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SrsReviewController extends Controller
{
    public function __construct(private SrsService $srsService) {}

    public function index(Request $request): Response
    {
        $dueCount = $request->user()->srsCards()
            ->where('due_at', '<=', now())
            ->count();

        return Inertia::render('Review/Index', [
            'dueCount' => $dueCount,
        ]);
    }

    public function cards(Request $request): JsonResponse
    {
        $cards = $request->user()->srsCards()
            ->with('dictionaryEntry')
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->limit(10)
            ->get();

        return response()->json(['cards' => $cards]);
    }

    public function review(Request $request, SrsCard $srsCard): JsonResponse
    {
        abort_unless($srsCard->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'in:1,2,3,4'],
            'time_taken_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $rating = SrsRating::from((int) $validated['rating']);

        $result = $this->srsService->review(
            $srsCard,
            $rating,
            $validated['time_taken_ms'] ?? null,
        );

        return response()->json([
            'card' => $result['card'],
            'log' => $result['log'],
        ]);
    }
}
