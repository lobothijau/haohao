<?php

namespace App\Http\Controllers;

use App\Services\PinyinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PinyinController extends Controller
{
    public function __invoke(Request $request, PinyinService $pinyinService): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:50'],
        ]);

        return response()->json([
            'pinyin' => $pinyinService->convert($validated['text']),
        ]);
    }
}
