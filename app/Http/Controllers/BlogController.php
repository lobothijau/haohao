<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = BlogPost::query()
            ->where('is_published', true)
            ->with('category')
            ->when($request->input('category'), function ($query, $slug) {
                $query->whereHas('category', fn ($q) => $q->where('slug', $slug));
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
            'categories' => BlogCategory::query()->orderBy('sort_order')->get(),
            'filters' => (object) $request->only(['category', 'search']),
        ]);
    }

    public function show(BlogPost $blogPost): Response
    {
        abort_unless($blogPost->is_published, 404);

        $blogPost->load(['category', 'creator']);

        $relatedPosts = BlogPost::query()
            ->where('is_published', true)
            ->where('id', '!=', $blogPost->id)
            ->when($blogPost->blog_category_id, fn ($query) => $query->where('blog_category_id', $blogPost->blog_category_id))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $seo = [
            'title' => $blogPost->meta_title ?? $blogPost->title,
            'description' => $blogPost->meta_description ?? $blogPost->excerpt,
            'image' => $blogPost->featured_image_url,
            'url' => route('blog.show', $blogPost->slug),
            'type' => 'article',
            'published_time' => $blogPost->published_at?->toIso8601String(),
            'author' => $blogPost->creator?->name,
        ];

        return Inertia::render('Blog/Show', [
            'post' => $blogPost,
            'relatedPosts' => $relatedPosts,
            'seo' => $seo,
        ]);
    }
}
