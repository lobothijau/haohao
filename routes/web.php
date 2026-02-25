<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MidtransWebhookController;
use App\Http\Controllers\PinyinController;
use App\Http\Controllers\ReadingProgressController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\SrsReviewController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\StoryCommentController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\UserVocabularyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoryController::class, 'index'])->name('home');
Route::get('/stories/{story:slug}', [StoryController::class, 'show'])->name('stories.show');

Route::get('/series', [SeriesController::class, 'index'])->name('series.index');
Route::get('/series/{series:slug}', [SeriesController::class, 'show'])->name('series.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{blogPost:slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('/membership', [MembershipController::class, 'index'])->name('membership.index');
Route::post('/webhooks/midtrans', MidtransWebhookController::class)->name('webhooks.midtrans');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/membership/subscribe', [MembershipController::class, 'subscribe'])->name('membership.subscribe');
    Route::get('/membership/checkout/{subscription}', [MembershipController::class, 'checkout'])->name('membership.checkout');
    Route::post('/stories/{story}/progress', [ReadingProgressController::class, 'store'])->name('stories.progress');
    Route::post('/stories/{story}/comments', [StoryCommentController::class, 'store'])->name('stories.comments.store');
    Route::put('/stories/{story}/comments/{comment}', [StoryCommentController::class, 'update'])->name('stories.comments.update');
    Route::delete('/stories/{story}/comments/{comment}', [StoryCommentController::class, 'destroy'])->name('stories.comments.destroy');
    Route::get('/vocabulary', [UserVocabularyController::class, 'index'])->name('vocabulary.index');
    Route::post('/vocabulary', [UserVocabularyController::class, 'store'])->name('vocabulary.store');
    Route::post('/vocabulary/custom', [UserVocabularyController::class, 'storeCustom'])->name('vocabulary.store-custom');
    Route::delete('/vocabulary/{vocabulary}', [UserVocabularyController::class, 'destroy'])->name('vocabulary.destroy');
    Route::get('/review', [SrsReviewController::class, 'index'])->name('review.index');
    Route::get('/review/cards', [SrsReviewController::class, 'cards'])->name('review.cards');
    Route::post('/review/{srsCard}', [SrsReviewController::class, 'review'])->name('review.review');
    Route::patch('/preferences', [UserPreferenceController::class, 'update'])->name('preferences.update');
    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/pinyin/convert', PinyinController::class)->name('pinyin.convert');
});

require __DIR__.'/settings.php';
