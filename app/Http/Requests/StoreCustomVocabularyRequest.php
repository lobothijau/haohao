<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomVocabularyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'simplified' => ['required', 'string', 'max:50'],
            'pinyin' => ['required', 'string', 'max:100'],
            'meaning_id' => ['required', 'string', 'max:500'],
            'meaning_en' => ['nullable', 'string', 'max:500'],
        ];
    }
}
