<?php

namespace App\Services;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\ES256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;

class AppleReceiptValidator
{
    /**
     * Validate an Apple App Store receipt.
     */
    public function validate(string $transactionId): array
    {
        $jwt = $this->generateJWT();

        // Call Apple's App Store Server API
        $response = $this->callAppleAPI($jwt, $transactionId);

        return $response;
    }

    /**
     * Generate JWT for App Store Server API authentication.
     */
    private function generateJWT(): string
    {
        $keyId = config('services.apple.key_id');
        $issuerId = config('services.apple.issuer_id');
        $bundleId = config('services.apple.bundle_id');
        $privateKey = config('services.apple.private_key');

        // Create JWK from private key
        $jwk = JWKFactory::createFromKey($privateKey, null, [
            'kid' => $keyId,
            'alg' => 'ES256',
            'use' => 'sig',
        ]);

        // Create algorithm manager
        $algorithmManager = new AlgorithmManager([new ES256]);

        // Create JWS builder
        $jwsBuilder = new JWSBuilder($algorithmManager);

        $payload = json_encode([
            'iss' => $issuerId,
            'iat' => time(),
            'exp' => time() + 3600, // 1 hour expiration
            'aud' => 'appstoreconnect-v1',
            'bid' => $bundleId,
        ]);

        // Build JWS
        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => 'ES256', 'kid' => $keyId, 'typ' => 'JWT'])
            ->build();

        // Serialize
        $serializer = new CompactSerializer;

        return $serializer->serialize($jws, 0);
    }

    /**
     * Call Apple's App Store Server API.
     */
    private function callAppleAPI(string $jwt, string $transactionId): array
    {
        $url = config('app.env') === 'production'
            ? "https://api.storekit.itunes.apple.com/inApps/v1/transactions/{$transactionId}"
            : "https://api.storekit-sandbox.itunes.apple.com/inApps/v1/transactions/{$transactionId}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$jwt,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("Apple API returned status {$httpCode}: {$response}");
        }

        return json_decode($response, true);
    }
}
