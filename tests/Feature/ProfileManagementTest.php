<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('guests cannot access profile settings', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

test('users can update their own profile information', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Amina Nguema',
        'email' => 'amina.updated@example.test',
        'phone' => '+237 6 70 00 00 02',
    ])->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Amina Nguema',
        'email' => 'amina.updated@example.test',
        'phone' => '+237670000002',
    ]);
});

test('users must provide their current password to change it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'incorrect-password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('users can change their password after confirming the current one', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertRedirect();

    expect(Hash::check('NewPassword123!', $user->fresh()->password))->toBeTrue();
});

test('configured administrators cannot change credentials outside deployment configuration', function () {
    config([
        'auth.admin.email' => 'admin@example.test',
        'auth.admin.password' => 'a-strong-admin-password-2026',
    ]);

    $this->post(route('login'), [
        'identifier' => 'admin@example.test',
        'password' => 'a-strong-admin-password-2026',
    ])->assertRedirect(route('dashboard'));

    $this->put(route('profile.update'), [
        'name' => 'Changed administrator',
        'email' => 'changed-admin@example.test',
        'phone' => null,
    ])->assertForbidden();

    $this->put(route('profile.password.update'), [
        'current_password' => 'a-strong-admin-password-2026',
        'password' => 'another-strong-admin-password-2026',
        'password_confirmation' => 'another-strong-admin-password-2026',
    ])->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
});
