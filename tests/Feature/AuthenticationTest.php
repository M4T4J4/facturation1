<?php

use App\Models\User;

test('guests can view the login and registration forms', function () {
    $this->get(route('login'))->assertOk()->assertSee('Connexion');
    $this->get(route('register'))->assertOk()->assertSee('Créer un compte');
});

test('registration creates a customer account and ignores submitted roles', function () {
    $response = $this->post(route('register'), [
        'name' => 'Amina Nguema',
        'email' => 'amina@example.test',
        'phone' => '+237 6 70 00 00 01',
        'password' => 'ChangeMe123!',
        'password_confirmation' => 'ChangeMe123!',
        'role' => 'administrator',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'amina@example.test',
        'phone' => '+237670000001',
        'role' => 'customer',
    ]);
});

test('users can sign in with their email address', function () {
    $user = User::factory()->create(['email' => 'amina@example.test']);

    $this->post(route('login'), [
        'identifier' => 'AMINA@example.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('users can sign in with a normalized phone number', function () {
    $user = User::factory()->create(['phone' => '+237670000001']);

    $this->post(route('login'), [
        'identifier' => '+237 (6) 70-00-00-01',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('configured administrator can sign in without a database account', function () {
    config([
        'auth.admin.name' => 'Administrateur principal',
        'auth.admin.email' => 'admin@example.test',
        'auth.admin.password' => 'a-strong-admin-password-2026',
    ]);

    $this->post(route('login'), [
        'identifier' => 'ADMIN@example.test',
        'password' => 'a-strong-admin-password-2026',
        'remember' => '1',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    $this->get(route('admin.users.index'))->assertOk();
});

test('configured administrator cannot sign in with an incorrect password', function () {
    config([
        'auth.admin.email' => 'admin@example.test',
        'auth.admin.password' => 'a-strong-admin-password-2026',
    ]);

    $this->post(route('login'), [
        'identifier' => 'admin@example.test',
        'password' => 'incorrect-password',
    ])->assertSessionHasErrors('identifier');

    $this->assertGuest();
});

test('configured administrator requires a password of at least 16 characters', function () {
    config([
        'auth.admin.email' => 'admin@example.test',
        'auth.admin.password' => 'short',
    ]);

    $this->post(route('login'), [
        'identifier' => 'admin@example.test',
        'password' => 'short',
    ])->assertSessionHasErrors('identifier');

    $this->assertGuest();
});

test('invalid credentials do not authenticate a user', function () {
    $this->post(route('login'), [
        'identifier' => 'missing@example.test',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('identifier');

    $this->assertGuest();
});
