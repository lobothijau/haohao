<?php

namespace App\Services;

use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Illuminate\Support\Facades\Storage;

class GoogleTtsService
{
    private TextToSpeechClient $client;

    public function __construct(?TextToSpeechClient $client = null)
    {
        $this->client = $client ?? $this->createClient();
    }

    /**
     * Synthesize text to speech and store the MP3 file.
     *
     * @return string Public URL to the stored audio file
     */
    public function synthesizeAndStore(string $text, string $subdir, ?string $filename = null): string
    {
        $filename = $filename ?? md5($text).'.mp3';
        $path = "audio/{$subdir}/{$filename}";

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $input = (new SynthesisInput)->setText($text);

        $voice = (new VoiceSelectionParams)
            ->setLanguageCode(config('services.google_tts.language_code'))
            ->setName(config('services.google_tts.voice_name'));

        $audioConfig = (new AudioConfig)
            ->setAudioEncoding(AudioEncoding::MP3)
            ->setSpeakingRate(config('services.google_tts.speaking_rate'));

        $request = (new SynthesizeSpeechRequest)
            ->setInput($input)
            ->setVoice($voice)
            ->setAudioConfig($audioConfig);

        $response = $this->client->synthesizeSpeech($request);

        Storage::disk('public')->put($path, $response->getAudioContent());

        return Storage::disk('public')->url($path);
    }

    /**
     * Generate audio for a dictionary word.
     */
    public function generateWordAudio(string $simplified): string
    {
        return $this->synthesizeAndStore($simplified, 'words', md5($simplified).'.mp3');
    }

    /**
     * Generate audio for a story sentence.
     */
    public function generateSentenceAudio(string $textZh, int $sentenceId): string
    {
        return $this->synthesizeAndStore($textZh, 'sentences', "sentence_{$sentenceId}.mp3");
    }

    private function createClient(): TextToSpeechClient
    {
        $credentialsPath = config('services.google_tts.credentials_path');

        $options = [];
        if ($credentialsPath) {
            $options['credentials'] = $credentialsPath;
        }

        return new TextToSpeechClient($options);
    }
}
