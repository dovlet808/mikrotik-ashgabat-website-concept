<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_an_administrator_with_a_hashed_password(): void
    {
        $this->artisan('app:create-admin', ['--email' => 'owner@example.com', '--name' => 'Owner', '--generate' => true])
            ->expectsOutputToContain('Created administrator owner@example.com')
            ->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->sole();
        $this->assertTrue($user->is_admin);
        $this->assertSame('ru', $user->locale);
        $this->assertTrue(Hash::isHashed($user->password));
    }

    public function test_interactive_password_must_be_strong(): void
    {
        $this->artisan('app:create-admin', ['--email' => 'owner@example.com', '--name' => 'Owner'])
            ->expectsQuestion('Password (min. 12 characters)', 'short')
            ->expectsQuestion('Repeat password', 'short')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }

    public function test_is_admin_cannot_be_mass_assigned(): void
    {
        try {
            User::create(['name' => 'X', 'email' => 'x@example.com', 'password' => 'secret-password-1', 'is_admin' => true]);
            $this->fail('is_admin must not be mass assignable');
        } catch (MassAssignmentException) {
            $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
        }

        $user = (new User)->fill(['name' => 'Y', 'email' => 'y@example.com', 'password' => 'secret-password-1']);
        $user->save();
        $this->assertFalse($user->fresh()->is_admin);
    }
}
