<?php

namespace Tests\Feature;

use App\Filament\Actions\ImportFilesAction;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\FileImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class FileImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_markdown_files_into_the_selected_category(): void
    {
        $category = Category::factory()->create();
        $directory = $this->makeTempDirectory();
        $path = $directory . '/imported-post.md';

        try {
            file_put_contents($path, '# Hello' . "\n\n" . 'Imported content.');

            $importer = new FileImportService;
            $importer->fromFiles([$path], $category->id);

            $post = Post::withoutGlobalScopes()->where('slug', 'imported-post')->first();

            $this->assertSame(1, $importer->created);
            $this->assertNotNull($post);
            $this->assertSame($category->id, $post->category_id);
            $this->assertSame('Imported Post', $post->title);
            $this->assertStringContainsString('Imported content.', $post->content);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_it_imports_markdown_uploads_through_the_filament_action(): void
    {
        $category = Category::factory()->create();
        $directory = $this->makeTempDirectory();
        $path = $directory . '/imported-post.md';
        $action = ImportFilesAction::make();

        try {
            file_put_contents($path, '# Hello' . "\n\n" . 'Imported content.');

            $action->getActionFunction()([
                'category_id' => $category->id,
                'files' => [new UploadedFile($path, 'README.md', 'text/html', null, true)],
            ]);

            $post = Post::withoutGlobalScopes()
                ->where('category_id', $category->id)
                ->where('slug', 'readme')
                ->first();

            $this->assertNotNull($post);
            $this->assertSame($category->id, $post->category_id);
            $this->assertSame('Readme', $post->title);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_guests_cannot_see_the_import_action(): void
    {
        Livewire::test(ListPosts::class)
            ->assertActionHidden('import');
    }

    public function test_logged_in_users_can_see_the_import_action(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListPosts::class)
            ->assertActionVisible('import');
    }

    public function test_it_imports_zip_subfolders_as_subcategories_under_the_selected_category(): void
    {
        $category = Category::factory()->create([
            'name' => 'Docs',
            'slug' => 'docs',
        ]);

        $zipPath = $this->makeZip([
            'Guides/Setup/imported-post.md' => "# Hello\n\nImported content.",
        ]);

        try {
            $importer = new FileImportService;
            $importer->fromZip($zipPath, $category->id);

            $post = Post::withoutGlobalScopes()->where('slug', 'imported-post')->first();
            $subCategory = Category::withoutGlobalScopes()
                ->where('parent_id', $category->id)
                ->where('slug', 'guides')
                ->first();
            $nestedCategory = Category::withoutGlobalScopes()
                ->where('parent_id', $subCategory?->id)
                ->where('slug', 'setup')
                ->first();

            $this->assertSame(1, $importer->created);
            $this->assertNotNull($subCategory);
            $this->assertNotNull($nestedCategory);
            $this->assertNotNull($post);
            $this->assertSame($nestedCategory->id, $post->category_id);
        } finally {
            File::deleteDirectory(dirname($zipPath));
        }
    }

    /**
     * @param  array<string, string>  $files
     */
    private function makeZip(array $files): string
    {
        $path = $this->makeTempDirectory() . '/file-import.zip';
        $zip = new ZipArchive;

        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $path;
    }

    private function makeTempDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/file-import-' . uniqid();
        File::ensureDirectoryExists($directory);

        return $directory;
    }
}
