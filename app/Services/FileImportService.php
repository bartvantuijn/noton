<?php

namespace App\Services;

use App\Enums\Visibility;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;

class FileImportService
{
    public int $created = 0;

    public function fromFiles(array $paths, int $categoryId, ?string $name = null): void
    {
        foreach ($paths as $path) {
            $this->importFile($path, $categoryId, name: $name);
        }
    }

    public function fromZip(string $path, int $categoryId): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Could not open ZIP file.');
        }

        $tmpDir = sys_get_temp_dir() . '/file-import-' . uniqid();
        $zip->extractTo($tmpDir);
        $zip->close();

        try {
            foreach ($this->markdownFiles($tmpDir) as $file) {
                $relative = ltrim(str_replace($tmpDir, '', $file), '/\\');
                $folder = dirname(str_replace('\\', '/', $relative));
                $segments = $folder === '.' ? [] : explode('/', $folder);

                // A zip can mirror a category tree, so folder segments become subcategories.
                $this->importFile($file, $categoryId, $segments);
            }
        } finally {
            File::deleteDirectory($tmpDir);
        }
    }

    private function importFile(string $path, int $categoryId, array $subfolders = [], ?string $name = null): void
    {
        $content = trim(file_get_contents($path));

        if ($content === '') {
            return;
        }

        // Plain files stay in the selected category; zip folders drill deeper into the tree.
        $categoryId = $subfolders === [] ? $categoryId : $this->resolveSubcategory($categoryId, $subfolders)->id;

        $filename = pathinfo($name ?? $path, PATHINFO_FILENAME);

        $post = new Post;
        $post->title = Str::title(str_replace(['-', '_'], ' ', $filename));
        $post->slug = $this->uniqueSlug(Str::slug($filename), $categoryId);
        $post->category_id = $categoryId;
        $post->content = $content;
        $post->visibility = Visibility::Public;
        $post->sort = 0;
        $post->save();

        $this->created++;
    }

    private function markdownFiles(string $dir): array
    {
        $files = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }

            // Work with the relative path so we can skip macOS metadata and hidden files reliably.
            $relative = ltrim(str_replace($dir, '', $file->getPathname()), '/\\');

            if (str_starts_with($relative, '__MACOSX/') || str_contains($relative, '/.') || str_starts_with($relative, '.')) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }

    private function resolveSubcategory(int $parentId, array $segments): Category
    {
        $parent = Category::withoutGlobalScopes()->findOrFail($parentId);

        // Walk through each folder segment and create missing subcategories.
        foreach (array_filter($segments) as $segment) {
            $name = Str::title(str_replace(['-', '_'], ' ', trim($segment)));
            $slug = Str::slug($name);

            $category = Category::withoutGlobalScopes()
                ->where('parent_id', $parent->id)
                ->where(fn ($query) => $query->where('name', $name)->orWhere('slug', $slug))
                ->first();

            if (! $category) {
                // Missing folders in the archive become missing categories in Noton.
                $category = new Category;
                $category->name = $name;
                $category->slug = $slug;
                $category->parent_id = $parent->id;
                $category->visibility = Visibility::Public;
                $category->sort = 0;
                $category->save();
            }

            $parent = $category;
        }

        return $parent;
    }

    private function uniqueSlug(string $base, int $categoryId): string
    {
        $slug = $base;
        $suffix = 2;

        // Add a number when the slug already exists in this category.
        while (Post::withoutGlobalScopes()->where('category_id', $categoryId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
