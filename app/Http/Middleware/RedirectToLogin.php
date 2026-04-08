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
        if ($request->routeIs('filament.admin.resources.categories.view')) {
            $category = Category::withoutGlobalScopes()->find($request->route('record'));

            if ($category?->isEffectivelyPrivate()) {
                return $this->redirect();
            }
        }

        // Redirect from private post.
        if ($request->routeIs('filament.admin.resources.posts.view')) {
            $post = Post::withoutGlobalScopes()
                ->with(['category' => fn ($query) => $query->withoutGlobalScopes()])
                ->find($request->route('record'));

            if ($post?->isEffectivelyPrivate()) {
                return $this->redirect();
            }
        }

        return $next($request);
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
