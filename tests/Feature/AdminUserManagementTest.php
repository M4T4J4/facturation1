<?php

use App\Models\User;
use App\UserRole;

test('administrators can assign a role to another user', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($administrator)->put(route('admin.users.role.update', $customer), [
        'role' => UserRole::FieldAgent->value,
    ])->assertRedirect();

    expect($customer->fresh()->role)->toBe(UserRole::FieldAgent);
});

test('administrators cannot remove their own administrator role', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($administrator)->put(route('admin.users.role.update', $administrator), [
        'role' => UserRole::Customer->value,
    ])->assertUnprocessable();

    expect($administrator->fresh()->role)->toBe(UserRole::Administrator);
});
