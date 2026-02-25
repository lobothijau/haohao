<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ElevenLabsTtsService
{
    /**
     * Generate audio for a dictionary word and store it on DO Spaces.
     *
     * @return string Public URL to the stored audio file
     */
    public function generateWordAudio(string $simplified): string
    {
        $filename = md5($simplified).'.mp3';
        $path = "audio/words/{$filename}";

        if (Storage::disk('do')->exists($path)) {
            return Storage::disk('do')->url($path);
        }

        $voiceId = config('services.elevenlabs.voice_id');

        $response = Http::withHeaders([
            'xi-api-key' => config('services.elevenlabs.api_key'),
            'Accept' => 'audio/mpeg',
        ])->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}", [
            'text' => $simplified,
            'model_id' => config('services.elevenlabs.model_id'),
            'output_format' => 'mp3_44100_128',
        ]);

        $response->throw();

        Storage::disk('do')->put($path, $response->body(), 'public');

        return Storage::disk('do')->url($path);
    }
}
