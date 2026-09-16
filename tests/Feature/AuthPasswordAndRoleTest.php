<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends a reset link for a valid email', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'user@example.com',
    ]);

    $response = $this->postJson('/api/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'We have emailed your password reset link.']);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('allows an admin to update a user role', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'developer']);

    $response = $this->actingAs($admin, 'sanctum')->putJson('/api/users/' . $user->id . '/role', [
        'role' => 'editor',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.role', 'editor')
        ->assertJsonPath('message', 'User role updated successfully');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'role' => 'editor',
    ]);
});

it('returns a list of users and a single user for admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $target = User::factory()->create(['role' => 'developer']);

    $listResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/users');
    $listResponse->assertOk()->assertJsonStructure(['data' => [['id', 'name', 'email', 'role']]]);

    $singleResponse = $this->actingAs($admin, 'sanctum')->getJson('/api/users/' . $target->id);
    $singleResponse->assertOk()
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.email', $target->email);
});
