<?php

namespace Tests\Unit;

use App\Enums\Visibility;
use PHPUnit\Framework\TestCase;

class VisibilityTest extends TestCase
{
    public function test_it_returns_all_visibility_values(): void
    {
        $this->assertSame(['public', 'private'], Visibility::values());
    }
}
