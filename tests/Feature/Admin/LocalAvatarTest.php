<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin panel must not load anything from third-party hosts: Filament's default avatar comes from
 * ui-avatars.com, which leaks the administrator's initials and breaks offline or on a LAN.
 */
class LocalAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_avatar_is_drawn_locally(): void
    {
        $this->actingAs($this->admin(['name' => 'Demo Admin']));

        $html = $this->get('/admin')->assertOk()->getContent();

        $this->assertStringNotContainsString('ui-avatars.com', $html);
        $this->assertStringContainsString('data:image/svg+xml;base64,', $html);
        $this->assertStringContainsString('>DA<', base64_decode(str($html)->after('data:image/svg+xml;base64,')->before('"')->toString()));
    }
}
