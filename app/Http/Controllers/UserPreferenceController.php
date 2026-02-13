<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'show_pinyin' => ['sometimes', 'boolean'],
            'show_translation' => ['sometimes', 'boolean'],
        ]);

        $request->user()->preference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated,
        );

        return response()->json(['status' => 'ok']);
    }
}
