<?php

use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\SrsReviewController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\UserVocabularyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoryController::class, 'index'])->name('home');
Route::get('/stories/{story:slug}', [StoryController::class, 'show'])->name('stories.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/stories/{story}/progress', [ReadingProgressController::class, 'store'])->name('stories.progress');
    Route::get('/vocabulary', [UserVocabularyController::class, 'index'])->name('vocabulary.index');
    Route::post('/vocabulary', [UserVocabularyController::class, 'store'])->name('vocabulary.store');
    Route::delete('/vocabulary/{vocabulary}', [UserVocabularyController::class, 'destroy'])->name('vocabulary.destroy');
    Route::get('/review', [SrsReviewController::class, 'index'])->name('review.index');
    Route::get('/review/cards', [SrsReviewController::class, 'cards'])->name('review.cards');
    Route::post('/review/{srsCard}', [SrsReviewController::class, 'review'])->name('review.review');
    Route::patch('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');
});

require __DIR__.'/settings.php';
