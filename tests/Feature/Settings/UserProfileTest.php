<?php

use App\Models\User;

test('user can update profile name and email', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@example.com');
});

test('email uniqueness is enforced on profile update', function () {
    $existingUser = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'My Name',
            'email' => 'taken@example.com',
        ]);

    $response->assertSessionHasErrors('email');
});

test('user can update timezone preference', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);

    $response = $this
        ->actingAs($user)
        ->patch(route('preferences.update'), [
            'timezone' => 'America/New_York',
            'locale' => 'en',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('preferences.edit'));

    expect($user->refresh()->timezone)->toBe('America/New_York');
});

test('partial preference update does not overwrite other display preference keys', function () {
    $user = User::factory()->create([
        'display_preferences' => ['theme' => 'dark', 'density' => 'compact'],
    ]);

    $response = $this
        ->actingAs($user)
        ->patch(route('preferences.update'), [
            'display_preferences' => ['density' => 'comfortable'],
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('preferences.edit'));

    $user->refresh();

    expect($user->display_preferences['theme'])->toBe('dark');
    expect($user->display_preferences['density'])->toBe('comfortable');
});

test('user cannot access another user settings', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $response = $this
        ->actingAs($userA)
        ->patch(route('preferences.update'), [
            'timezone' => 'America/Chicago',
        ]);

    $response->assertRedirect(route('preferences.edit'));

    expect($userB->refresh()->timezone)->toBe('UTC');
});
