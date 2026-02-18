<?php

namespace App\Http\Requests;

use App\Models\Comment;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Closure>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1000'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:comments,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value && Comment::where('id', $value)->whereNotNull('parent_id')->exists()) {
                        $fail('Tidak dapat membalas balasan.');
                    }
                },
            ],
        ];
    }
}
