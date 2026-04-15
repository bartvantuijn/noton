<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Post;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RedirectToLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()) {
            return $next($request);
        }

        // Redirect from private pages.
        if ($request->routeIs([
            'filament.admin.auth.profile',
            'filament.admin.pages.settings',
            'filament.admin.resources.*.create',
            'filament.admin.resources.*.edit',
            'filament.admin.resources.users.*',
        ])) {
            return $this->redirect();
        }

        // Redirect from private category.
        if ($request->routeIs('filament.admin.resources.categories.view') && $this->findCategory($request->route('record'))?->isEffectivelyPrivate()) {
            return $this->redirect();
        }

        // Redirect from private post.
        if ($request->routeIs('filament.admin.resources.posts.view') && $this->findPost($request->route('category'), $request->route('record'))?->isEffectivelyPrivate()) {
            return $this->redirect();
        }

        return $next($request);
    }

    protected function findCategory(string $slug): ?Category
    {
        return Category::findBySlugPath($slug, Category::withoutGlobalScopes());
    }

    protected function findPost(string $categorySlug, string $postSlug): ?Post
    {
        $category = $this->findCategory($categorySlug);

        return $category
            ? Post::withoutGlobalScopes()
                ->with(['category' => fn ($query) => $query->withoutGlobalScopes()])
                ->whereBelongsTo($category, 'category')
                ->where(fn ($query) => $query->where('slug', $postSlug)->orWhereNull('slug'))
                ->get()
                ->first(fn (Post $post) => $post->getSlug() === $postSlug)
            : null;
    }

    protected function redirect(): RedirectResponse
    {
        Notification::make()
            ->warning()
            ->title(__('Please log in to continue.'))
            ->send();

        return redirect()->route('filament.admin.auth.login');
    }
}
