<?php

use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserVocabularyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoryController::class, 'index'])->name('home');
Route::get('/stories/{story:slug}', [StoryController::class, 'show'])->name('stories.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/stories/{story}/progress', [ReadingProgressController::class, 'store'])->name('stories.progress');
    Route::post('/vocabulary', [UserVocabularyController::class, 'store'])->name('vocabulary.store');
    Route::delete('/vocabulary/{vocabulary}', [UserVocabularyController::class, 'destroy'])->name('vocabulary.destroy');
});

require __DIR__.'/settings.php';
