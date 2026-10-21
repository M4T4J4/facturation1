<?php

use App\Models\MeterReading;
use App\Models\User;
use App\Models\WaterMeter;
use App\Models\WaterZone;
use App\UserRole;

test('administrators can add a meter to inventory and assign it to a customer subscription', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-TEST-001',
        'service_address' => 'Bonamoussadi, Douala',
        'status' => 'active',
    ]);

    $this->actingAs($administrator)->post(route('admin.meters.store'), [
        'serial_number' => 'SERIAL-001',
        'status' => 'active',
        'installed_at' => now()->toDateString(),
        'subscription_id' => $subscription->id,
    ])->assertRedirect();

    $meter = WaterMeter::query()->where('serial_number', 'SERIAL-001')->firstOrFail();
    $this->assertDatabaseHas('water_meters', [
        'id' => $meter->id,
        'subscription_id' => $subscription->id,
        'status' => 'active',
    ]);
    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'meter_number' => 'SERIAL-001',
    ]);
    $this->assertDatabaseHas('meter_events', [
        'water_meter_id' => $meter->id,
        'subscription_id' => $subscription->id,
        'event_type' => 'installation',
    ]);
});

test('the configured code-based administrator can manage meters without a user row', function () {
    config([
        'auth.admin.email' => 'admin@example.test',
        'auth.admin.password' => 'a-strong-admin-password-2026',
    ]);

    $this->post(route('login'), [
        'identifier' => 'admin@example.test',
        'password' => 'a-strong-admin-password-2026',
    ])->assertRedirect(route('dashboard'));

    $this->post(route('admin.meters.store'), [
        'serial_number' => 'SERIAL-VIRTUAL-ADMIN',
        'status' => 'active',
    ])->assertRedirect();

    $meter = WaterMeter::query()->where('serial_number', 'SERIAL-VIRTUAL-ADMIN')->firstOrFail();
    $this->assertDatabaseHas('meter_events', [
        'water_meter_id' => $meter->id,
        'performed_by_user_id' => null,
        'event_type' => 'registration',
    ]);
});

test('assigning a replacement meter preserves the previous meter history', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-TEST-002',
        'service_address' => 'Akwa, Douala',
        'meter_number' => 'SERIAL-OLD',
        'status' => 'active',
    ]);
    $oldMeter = $subscription->waterMeter()->create([
        'serial_number' => 'SERIAL-OLD',
        'status' => 'active',
    ]);
    $newMeter = WaterMeter::query()->create([
        'serial_number' => 'SERIAL-NEW',
        'status' => 'active',
    ]);

    $this->actingAs($administrator)->post(route('admin.meters.assignment', $newMeter), [
        'subscription_id' => $subscription->id,
        'description' => 'Remplacement pour usure.',
    ])->assertRedirect();

    expect($oldMeter->fresh()->subscription_id)->toBeNull()
        ->and($newMeter->fresh()->subscription_id)->toBe($subscription->id);
    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'meter_number' => 'SERIAL-NEW',
    ]);
    $this->assertDatabaseHas('meter_events', [
        'water_meter_id' => $oldMeter->id,
        'event_type' => 'replacement',
    ]);
    $this->assertDatabaseHas('meter_events', [
        'water_meter_id' => $newMeter->id,
        'event_type' => 'installation',
        'description' => 'Remplacement pour usure.',
    ]);
});

test('changing a meter serial number updates its subscription and records the change', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-TEST-007',
        'service_address' => 'Akwa, Douala',
        'meter_number' => 'SERIAL-BEFORE',
        'status' => 'active',
    ]);
    $meter = $subscription->waterMeter()->create([
        'serial_number' => 'SERIAL-BEFORE',
        'status' => 'active',
    ]);

    $this->actingAs($administrator)->put(route('admin.meters.update', $meter), [
        'serial_number' => 'SERIAL-AFTER',
        'status' => 'active',
    ])->assertRedirect();

    $this->assertDatabaseHas('subscriptions', [
        'id' => $subscription->id,
        'meter_number' => 'SERIAL-AFTER',
    ]);
    $this->assertDatabaseHas('meter_events', [
        'water_meter_id' => $meter->id,
        'event_type' => 'serial_update',
        'description' => 'Numéro de série modifié de SERIAL-BEFORE à SERIAL-AFTER.',
    ]);
});

test('recording a repair returns a defective meter to service and keeps its intervention history', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $meter = WaterMeter::query()->create([
        'serial_number' => 'SERIAL-REPAIR',
        'status' => 'defective',
    ]);

    $this->actingAs($administrator)->post(route('admin.meters.events.store', $meter), [
        'event_type' => 'repair',
        'description' => 'Joint remplacé et compteur vérifié.',
        'event_at' => now()->toDateString(),
    ])->assertRedirect();

    expect($meter->fresh()->status)->toBe('active');
    $this->assertDatabaseHas('meter_events', [
        'water_meter_id' => $meter->id,
        'event_type' => 'repair',
        'description' => 'Joint remplacé et compteur vérifié.',
    ]);
});

test('field agents and customers record readings against the currently assigned meter', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $agent = User::factory()->create(['role' => UserRole::FieldAgent]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-TEST-005',
        'service_address' => 'New Bell, Douala',
        'meter_number' => 'SERIAL-READING-OLD',
        'status' => 'active',
    ]);
    $zone = WaterZone::query()->create(['name' => 'New Bell', 'code' => 'NB']);
    $subscription->update(['water_zone_id' => $zone->id]);
    $zone->agents()->attach($agent);
    $oldMeter = $subscription->waterMeter()->create([
        'serial_number' => 'SERIAL-READING-OLD',
        'status' => 'active',
    ]);
    $subscription->meterReadings()->create([
        'water_meter_id' => $oldMeter->id,
        'previous_index' => 0,
        'current_index' => 120,
        'read_at' => now()->subMonth()->toDateString(),
    ]);
    $newMeter = WaterMeter::query()->create([
        'serial_number' => 'SERIAL-READING-NEW',
        'status' => 'active',
    ]);

    $this->actingAs($administrator)->post(route('admin.meters.assignment', $newMeter), [
        'subscription_id' => $subscription->id,
    ])->assertRedirect();

    $this->actingAs($agent)->post(route('agent.readings.store'), [
        'reference' => $subscription->reference,
        'current_index' => 5,
        'read_at' => now()->toDateString(),
    ])->assertRedirect();

    $agentReading = MeterReading::query()->where('water_meter_id', $newMeter->id)->firstOrFail();
    expect($agentReading->previous_index)->toBe(0);

    $this->actingAs($customer)->post(route('client.readings.store'), [
        'current_index' => 10,
        'read_at' => now()->toDateString(),
    ])->assertRedirect();

    $customerReading = MeterReading::query()->where('water_meter_id', $newMeter->id)->latest('id')->firstOrFail();
    expect($customerReading->previous_index)->toBe(5);
});

test('field agents cannot submit readings for a blocked meter', function () {
    $agent = User::factory()->create(['role' => UserRole::FieldAgent]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $subscription = $customer->subscriptions()->create([
        'reference' => 'EV-TEST-006',
        'service_address' => 'Deido, Douala',
        'status' => 'active',
    ]);
    $zone = WaterZone::query()->create(['name' => 'Deido', 'code' => 'DEI']);
    $subscription->update(['water_zone_id' => $zone->id]);
    $zone->agents()->attach($agent);
    $subscription->waterMeter()->create([
        'serial_number' => 'SERIAL-BLOCKED',
        'status' => 'blocked',
    ]);

    $this->actingAs($agent)->post(route('agent.readings.store'), [
        'reference' => $subscription->reference,
        'current_index' => 25,
        'read_at' => now()->toDateString(),
    ])->assertNotFound();

    expect($subscription->meterReadings()->count())->toBe(0);
});

test('administrators see alerts for blocked meters, stalled readings, and unusual consumption', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $stalledSubscription = $customer->subscriptions()->create([
        'reference' => 'EV-TEST-003',
        'service_address' => 'Deido, Douala',
        'status' => 'active',
    ]);
    $stalledMeter = $stalledSubscription->waterMeter()->create([
        'serial_number' => 'SERIAL-STALLED',
        'status' => 'blocked',
    ]);

    foreach ([now()->subMonths(2), now()->subMonth(), now()] as $date) {
        $stalledSubscription->meterReadings()->create([
            'water_meter_id' => $stalledMeter->id,
            'previous_index' => 50,
            'current_index' => 50,
            'read_at' => $date->toDateString(),
        ]);
    }

    $unusualSubscription = $customer->subscriptions()->create([
        'reference' => 'EV-TEST-004',
        'service_address' => 'Bonapriso, Douala',
        'status' => 'active',
    ]);
    $unusualMeter = $unusualSubscription->waterMeter()->create([
        'serial_number' => 'SERIAL-UNUSUAL',
        'status' => 'active',
    ]);

    $unusualSubscription->meterReadings()->create([
        'water_meter_id' => $unusualMeter->id,
        'previous_index' => 10,
        'current_index' => 20,
        'read_at' => now()->subMonth()->toDateString(),
    ]);
    $unusualSubscription->meterReadings()->create([
        'water_meter_id' => $unusualMeter->id,
        'previous_index' => 20,
        'current_index' => 45,
        'read_at' => now()->toDateString(),
    ]);

    $this->actingAs($administrator)->get(route('admin.meters.index'))
        ->assertOk()
        ->assertViewIs('admin.meters.index')
        ->assertSee('Compteur déclaré bloqué.')
        ->assertSee('Index identique sur trois relevés consécutifs')
        ->assertSee('Consommation récente (25 m³)')
        ->assertSee('SERIAL-STALLED')
        ->assertSee('SERIAL-UNUSUAL');
});

test('non-administrators cannot access or modify the meter inventory', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('admin.meters.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.meters.store'), [
        'serial_number' => 'SERIAL-FORBIDDEN',
        'status' => 'active',
    ])->assertForbidden();

    $this->assertDatabaseMissing('water_meters', ['serial_number' => 'SERIAL-FORBIDDEN']);
})->with([
    UserRole::Customer,
    UserRole::FieldAgent,
]);

test('meter serial numbers must be unique', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);
    WaterMeter::query()->create(['serial_number' => 'SERIAL-DUPLICATE', 'status' => 'active']);

    $this->actingAs($administrator)->post(route('admin.meters.store'), [
        'serial_number' => 'SERIAL-DUPLICATE',
        'status' => 'active',
    ])->assertSessionHasErrors('serial_number');

    expect(WaterMeter::query()->where('serial_number', 'SERIAL-DUPLICATE')->count())->toBe(1);
});
