<?php

use App\Models\User;
use App\Models\UserNotificationPreference;

test('user can disable issue_updates category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('notifications.update'), [
            'categories' => [
                'issue_updates' => ['enabled' => false],
            ],
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('notifications.edit'));

    expect(
        UserNotificationPreference::where('user_id', $user->id)
            ->where('category', 'issue_updates')
            ->value('enabled')
    )->toBeFalse();
});

test('user cannot disable security category', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('notifications.update'), [
            'categories' => [
                'security' => ['enabled' => false],
            ],
        ])
        ->assertSessionHasErrors();
});

test('notification preferences persist per user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $this->actingAs($userA)->patch(route('notifications.update'), [
        'categories' => ['threshold_alerts' => ['enabled' => false]],
    ]);

    $this->actingAs($userB)->patch(route('notifications.update'), [
        'categories' => ['threshold_alerts' => ['enabled' => true]],
    ]);

    expect(
        UserNotificationPreference::where('user_id', $userA->id)
            ->where('category', 'threshold_alerts')
            ->value('enabled')
    )->toBeFalse();

    expect(
        UserNotificationPreference::where('user_id', $userB->id)
            ->where('category', 'threshold_alerts')
            ->value('enabled')
    )->toBeTrue();
});
