<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Filament\Widgets\PinnedItems;
use App\Models\Category;
use App\Models\Pin;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PinnedItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_see_their_pinned_items(): void
    {
        $user = User::factory()->create();

        $category = Category::factory()->create([
            'name' => 'Hosting',
            'subtitle' => $categorySubtitle = 'Servers, domains and mail with a very long subtitle that should be shortened in the dashboard card header because it otherwise takes too much space',
            'content' => 'Use this hosting category preview on the dashboard.',
            'views' => 12,
            'visibility' => Visibility::Public,
        ]);

        $post = Post::factory()->for($category)->create([
            'title' => 'Mail setup',
            'subtitle' => 'Configure mailboxes',
            'content' => 'Use this mail setup preview on the dashboard.',
            'views' => 5,
            'visibility' => Visibility::Public,
        ]);

        $post->attachTag(Tag::factory()->create(['name' => 'Mail']));
        $category->attachTag(Tag::factory()->create(['name' => 'Infrastructure']));

        Pin::create(['user_id' => $user->id, 'pinnable_type' => $category->getMorphClass(), 'pinnable_id' => $category->id]);
        Pin::create(['user_id' => $user->id, 'pinnable_type' => $post->getMorphClass(), 'pinnable_id' => $post->id]);

        $this->actingAs($user);

        $pins = app(PinnedItems::class)->getViewData()['pins'];

        $this->assertTrue($pins->first()->pinnable->is($category));

        Livewire::test(PinnedItems::class)
            ->assertSeeText('Pinned')
            ->assertSeeText('Hosting')
            ->assertSeeText(Str::limit($categorySubtitle, 20))
            ->assertDontSeeText($categorySubtitle)
            ->assertSeeText('Infrastructure')
            ->assertSeeText('Use this hosting category preview on the dashboard.')
            ->assertSeeText('Mail setup')
            ->assertSeeText('Configure mailboxes')
            ->assertSeeText('Mail')
            ->assertSeeText('Use this mail setup preview on the dashboard.');
    }

    public function test_users_only_see_their_own_pins(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownCategory = Category::factory()->create([
            'name' => 'Own shortcut',
            'visibility' => Visibility::Public,
        ]);

        $otherCategory = Category::factory()->create([
            'name' => 'Other shortcut',
            'visibility' => Visibility::Public,
        ]);

        Pin::create(['user_id' => $user->id, 'pinnable_type' => $ownCategory->getMorphClass(), 'pinnable_id' => $ownCategory->id]);
        Pin::create(['user_id' => $otherUser->id, 'pinnable_type' => $otherCategory->getMorphClass(), 'pinnable_id' => $otherCategory->id]);

        $this->actingAs($user);

        $pins = app(PinnedItems::class)->getViewData()['pins'];

        $this->assertTrue(PinnedItems::canView());
        $this->assertCount(1, $pins);
        $this->assertTrue($pins->first()->pinnable->is($ownCategory));
    }
}
