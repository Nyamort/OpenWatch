<?php

use App\Models\User;

beforeEach(function () {
    @unlink(storage_path('app/.installed'));
});

test('setup creates the first user as super admin', function () {
    $response = $this->post(route('setup.store'), [
        'name' => 'Owner',
        'email' => 'owner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));

    $user = User::where('email', 'owner@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->isSuperAdmin())->toBeTrue();

    expect(file_exists(storage_path('app/.installed')))->toBeTrue();
});

test('setup screen redirects when already installed', function () {
    file_put_contents(storage_path('app/.installed'), now()->toIso8601String());

    $response = $this->get(route('setup.show'));

    $response->assertRedirect(route('home'));
});
