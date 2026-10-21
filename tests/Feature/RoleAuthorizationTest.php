<?php

use App\Models\User;
use App\UserRole;

test('customers are redirected to their own dashboard', function () {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboards.customer');
});

test('customers can access their dashboard directly with its required data', function () {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($user)->get(route('client.dashboard'))
        ->assertOk()
        ->assertViewIs('dashboards.customer')
        ->assertViewHas('subscriptions')
        ->assertViewHas('unpaidInvoices', 0)
        ->assertViewHas('settings');
});

test('field agents are redirected to their own dashboard', function () {
    $user = User::factory()->create(['role' => UserRole::FieldAgent]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboards.field-agent');
});

test('administrators can access the administration dashboard', function () {
    $user = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboards.administrator');
});

test('non-administrators cannot access user management', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
})->with([
    UserRole::FieldAgent,
    UserRole::Customer,
]);
