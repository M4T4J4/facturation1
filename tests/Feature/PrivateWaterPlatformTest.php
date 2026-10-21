<?php

use App\Models\SupportTicket;
use App\Models\User;
use App\Models\WaterZone;
use App\UserRole;

test('the public guide explains the path from registration to invoice download', function () {
    $this->get(route('guide'))
        ->assertOk()
        ->assertSee('De l’inscription à votre facture')
        ->assertSee('Demandez le raccordement')
        ->assertSee('Envoyez l’index du compteur')
        ->assertSee('Télécharger PDF')
        ->assertSee(route('register'));
});

test('administrators can follow the guide from a connection request to invoice generation', function () {
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($administrator)
        ->get(route('admin.guide'))
        ->assertOk()
        ->assertSee('Du dossier à la facture de l’abonné')
        ->assertSee('Créer l’abonnement et le compteur')
        ->assertSee('Valider le relevé')
        ->assertSee('02:00')
        ->assertSee(route('admin.water.dashboard'));
});

test('non-administrators cannot access the administration guide', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->get(route('admin.guide'))->assertForbidden();
})->with([
    UserRole::Customer,
    UserRole::FieldAgent,
]);

test('customers can submit a connection request', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);

    $this->actingAs($customer)->post(route('client.requests.store'), [
        'service_address' => 'Quartier Bonamoussadi, Douala',
        'usage_type' => 'residential',
    ])->assertRedirect(route('client.dashboard'));

    $this->assertDatabaseHas('connection_requests', [
        'user_id' => $customer->id,
        'service_address' => 'Quartier Bonamoussadi, Douala',
        'status' => 'received',
    ]);
});

test('only customers can submit connection requests', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->post('/client/requests', [
        'service_address' => 'Douala',
        'usage_type' => 'residential',
    ])->assertForbidden();
})->with([
    UserRole::Administrator,
    UserRole::FieldAgent,
]);

test('the private water service can process a request through a confirmed invoice payment', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $agent = User::factory()->create(['role' => UserRole::FieldAgent]);
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($customer)->post(route('client.requests.store'), [
        'service_address' => 'Bonamoussadi, Douala',
        'usage_type' => 'residential',
    ])->assertRedirect(route('client.dashboard'));

    $connectionRequest = $customer->connectionRequests()->firstOrFail();
    $this->actingAs($administrator)->patch(route('admin.water.requests.update', $connectionRequest), [
        'status' => 'quoted',
        'quote_amount' => 25000,
    ])->assertSessionHasNoErrors();

    $this->actingAs($customer)->post(route('client.requests.accept', $connectionRequest))->assertSessionHasNoErrors();
    $this->assertDatabaseHas('connection_requests', ['id' => $connectionRequest->id, 'status' => 'approved']);
    $activationResponse = $this->actingAs($administrator)->post(route('admin.water.requests.activate', $connectionRequest));
    $activationResponse->assertSessionHas('status', 'subscription-activated');
    $this->assertDatabaseHas('connection_requests', ['id' => $connectionRequest->id, 'status' => 'connected']);

    $subscription = $customer->subscriptions()->firstOrFail();
    expect($subscription->status)->toBe('active');
    expect($subscription->waterMeter->serial_number)->toBe($subscription->meter_number);
    $zone = WaterZone::query()->create(['name' => 'Bonamoussadi', 'code' => 'BON']);
    $subscription->update(['water_zone_id' => $zone->id]);
    $zone->agents()->attach($agent);

    $this->actingAs($agent)->post(route('agent.readings.store'), [
        'reference' => $subscription->reference,
        'current_index' => 15,
        'read_at' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    $reading = $subscription->meterReadings()->firstOrFail();
    expect($reading->water_meter_id)->toBe($subscription->waterMeter->id);
    $this->actingAs($administrator)
        ->patch(route('admin.water.readings.verify', $reading))
        ->assertSessionHasNoErrors();
    $this->actingAs($administrator)->post(route('admin.water.readings.invoice', $reading))->assertSessionHasNoErrors();

    $invoice = $subscription->invoices()->firstOrFail();
    expect($invoice->amount)->toBe(7000);
    $this->actingAs($customer)
        ->get(route('client.invoices.print', $invoice))
        ->assertOk()
        ->assertSee('FACTURE D’EAU')
        ->assertSee('Exemplaire abonné')
        ->assertSee('Exemplaire comité')
        ->assertSee('Point de livraison');

    $this->actingAs($agent)->post(route('agent.readings.store'), [
        'reference' => $subscription->reference,
        'current_index' => 20,
        'read_at' => now()->toDateString(),
    ])->assertSessionHasNoErrors();
    $duplicateReading = $subscription->meterReadings()->latest('id')->firstOrFail();
    $this->actingAs($administrator)
        ->patch(route('admin.water.readings.verify', $duplicateReading))
        ->assertSessionHasNoErrors();
    $this->actingAs($administrator)->post(route('admin.water.readings.invoice', $duplicateReading))
        ->assertSessionHasErrors('invoice');
    expect($subscription->invoices()->count())->toBe(1);

    $anotherCustomer = User::factory()->create(['role' => UserRole::Customer]);
    $this->actingAs($anotherCustomer)
        ->get(route('client.invoices.download', $invoice))
        ->assertForbidden();
    $this->actingAs($anotherCustomer)->post(route('client.invoices.payments.store', $invoice), [
        'channel' => 'wave',
    ])->assertForbidden();

    $this->actingAs($customer)->post(route('client.invoices.payments.store', $invoice), [
        'channel' => 'wave',
        'reference' => 'TX-12345',
    ])->assertSessionHasNoErrors();
    expect($invoice->fresh()->status)->toBe('unpaid');

    $payment = $invoice->payments()->firstOrFail();
    $this->actingAs($administrator)->patch(route('admin.water.payments.update', $payment), [
        'status' => 'confirmed',
    ])->assertSessionHasNoErrors();

    expect($invoice->fresh()->status)->toBe('paid')
        ->and($payment->fresh()->status)->toBe('confirmed');
});

test('each role can render only its own water workspace', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $agent = User::factory()->create(['role' => UserRole::FieldAgent]);
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($customer)->get(route('dashboard'))->assertOk()->assertSee('Mes compteurs');
    $this->get(route('client.requests.index'))->assertOk()->assertSee('Nouvelle demande');
    $this->get(route('client.invoices.index'))->assertOk()->assertSee('Mes factures');

    $this->actingAs($agent)->get(route('agent.readings.index'))->assertOk()->assertSee('Nouveau relevé');
    $this->get(route('client.invoices.index'))->assertForbidden();

    $this->actingAs($administrator)->get(route('admin.water.dashboard'))->assertOk()->assertSee('Demandes récentes');
    $this->get(route('client.requests.index'))->assertForbidden();
});

test('customers can submit support tickets and administrators can resolve them', function () {
    $customer = User::factory()->create(['role' => UserRole::Customer]);
    $administrator = User::factory()->create(['role' => UserRole::Administrator]);

    $this->actingAs($customer)->post(route('client.support.store'), [
        'subject' => 'Fuite d’eau',
        'description' => 'Fuite observée près du compteur.',
    ])->assertSessionHasNoErrors();

    $ticket = SupportTicket::query()->firstOrFail();
    $this->actingAs($administrator)->patch(route('admin.water.tickets.update', $ticket), [
        'status' => 'resolved',
    ])->assertSessionHasNoErrors();

    expect($ticket->fresh()->status)->toBe('resolved');
});

test('an operator can provision the first administrator from the console', function () {
    $user = User::factory()->create(['role' => UserRole::Customer]);

    $this->artisan('app:assign-user-role', [
        'identifier' => $user->email,
        'role' => 'administrator',
    ])->assertSuccessful();

    expect($user->fresh()->role)->toBe(UserRole::Administrator);
});
