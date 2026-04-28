<?php

use App\Models\User;

test('non super admin cannot access admin dashboard', function () {
    $user = User::factory()->create(['is_super_admin' => false]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertForbidden();
});

test('super admin can access admin dashboard', function () {
    $user = User::factory()->create(['is_super_admin' => true]);

    $response = $this->actingAs($user)->get('/admin');

    $response->assertOk();
});

test('guests are redirected from admin dashboard', function () {
    $response = $this->get('/admin');

    $response->assertRedirect(route('login'));
});

test('super admin gate authorizes super admins only', function () {
    $admin = User::factory()->create(['is_super_admin' => true]);
    $regular = User::factory()->create(['is_super_admin' => false]);

    expect($admin->isSuperAdmin())->toBeTrue();
    expect($regular->isSuperAdmin())->toBeFalse();

    $this->actingAs($admin);
    expect(\Illuminate\Support\Facades\Gate::allows('super-admin'))->toBeTrue();

    $this->actingAs($regular);
    expect(\Illuminate\Support\Facades\Gate::allows('super-admin'))->toBeFalse();
});
