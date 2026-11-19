<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentService
{
    public function mode(): string
    {
        return (string) config('services.stripe.mode', 'test');
    }

    public function isLiveMode(): bool
    {
        return $this->mode() === 'live';
    }

    public function isConfigured(): bool
    {
        $mode = $this->mode();
        $keyPrefix = $mode === 'live' ? 'pk_live_' : 'pk_test_';
        $secretPrefix = $mode === 'live' ? 'sk_live_' : 'sk_test_';
        $validEnvironment = $mode === 'test'
            || ($mode === 'live'
                && app()->environment('production')
                && filter_var(config('services.stripe.live_enabled'), FILTER_VALIDATE_BOOL)
                && ! config('app.debug')
                && Str::startsWith((string) config('app.url'), 'https://'));

        return filter_var(config('services.stripe.enabled'), FILTER_VALIDATE_BOOL)
            && in_array($mode, ['test', 'live'], true)
            && $validEnvironment
            && Str::startsWith((string) config('services.stripe.key'), $keyPrefix)
            && Str::startsWith((string) config('services.stripe.secret'), $secretPrefix)
            && Str::startsWith((string) config('services.stripe.webhook_secret'), 'whsec_');
    }

    public function createPaymentIntent(int $amountCents, string $email, int $entityId, string $reference, string $entityType = 'order'): array
    {
        $this->assertConfigured();

        $intent = $this->client()->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => 'mxn',
                'allowed_payment_method_types' => ['card'],
                'receipt_email' => $email,
                'description' => "AQUARIUM {$reference} · ".($this->isLiveMode() ? 'pago' : 'prueba'),
                'metadata' => [
                    'aquarium_entity_type' => $entityType,
                    'aquarium_entity_id' => (string) $entityId,
                    'aquarium_reference' => $reference,
                    'stripe_mode' => $this->mode(),
                ],
            ], ['idempotency_key' => "aquarium-{$entityType}-{$entityId}-stripe-{$this->mode()}-v1"]);

        $result = $intent->toArray();
        if (empty($result['id']) || empty($result['client_secret'])) {
            throw new RuntimeException('Stripe no pudo preparar el pago de prueba. Revisa las claves de prueba y los logs locales.');
        }

        return $result;
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $this->assertConfigured();
        return $this->client()->paymentIntents->retrieve($paymentIntentId)->toArray();
    }

    public function refundPaymentIntent(string $paymentIntentId, string $entityType, int $entityId): string
    {
        $this->assertConfigured();
        $refund = $this->client()->refunds->create(
            ['payment_intent' => $paymentIntentId],
            ['idempotency_key' => "aquarium-{$entityType}-{$entityId}-{$this->mode()}-refund-v1"],
        );

        return (string) ($refund->status ?? 'pending');
    }

    public function verifyWebhook(string $payload, ?string $signatureHeader): ?array
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if (! Str::startsWith($secret, 'whsec_') || ! $signatureHeader) {
            return null;
        }

        try {
            return Webhook::constructEvent($payload, $signatureHeader, $secret, 300)->toArray();
        } catch (\UnexpectedValueException|\Stripe\Exception\SignatureVerificationException) {
            return null;
        }
    }

    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Stripe no está habilitado: revisa el modo, las claves y la configuración segura del entorno.');
        }
    }

    private function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }
}
