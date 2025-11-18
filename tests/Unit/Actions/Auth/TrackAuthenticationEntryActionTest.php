<?php

use App\Actions\Auth\TrackAuthenticationEntryAction;
use App\Models\Access\Client;
use App\Models\Auth\Entry;
use App\Models\Auth\User;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

describe('TrackAuthenticationEntryAction', function () {
    describe('Basic Entry Creation', function () {
        it('creates entry with all required fields', function () {
            $user = User::factory()->create();
            $client = Client::factory()->create();
            $token = $user->createToken('test-token');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute(
                $user,
                $token,
                $client->id,
                '192.168.1.1',
                'Mozilla/5.0 Test Browser'
            );

            expect($entry)->toBeInstanceOf(Entry::class)
                ->and($entry->user_id)->toBe($user->id)
                ->and($entry->client_id)->toBe($client->id)
                ->and($entry->token_id)->toBe($token->accessToken->id)
                ->and($entry->ip_address)->toBe('192.168.1.1')
                ->and($entry->device_info)->toBe('Mozilla/5.0 Test Browser')
                ->and($entry->logged_in_at)->not->toBeNull()
                ->and($entry->logged_out_at)->toBeNull();
        });

        it('persists entry to database', function () {
            $user = User::factory()->create();
            $client = Client::factory()->create();
            $token = $user->createToken('test-token');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute(
                $user,
                $token,
                $client->id,
                '192.168.1.1',
                'Mozilla/5.0'
            );

            expect(Entry::find($entry->id))->not->toBeNull()
                ->and(Entry::where('user_id', $user->id)->count())->toBe(1);
        });
    });

    describe('IP Address Handling', function () {
        it('accepts valid IPv4 address', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, '192.168.1.1', null);

            expect($entry->ip_address)->toBe('192.168.1.1');
        });

        it('accepts valid IPv6 address', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, '2001:0db8:85a3:0000:0000:8a2e:0370:7334', null);

            expect($entry->ip_address)->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        });

        it('accepts null IP address', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, null, null);

            expect($entry->ip_address)->toBeNull();
        });
    });

    describe('Device Info Handling', function () {
        it('stores browser user agent', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, null, $userAgent);

            expect($entry->device_info)->toBe($userAgent);
        });

        it('stores mobile user agent', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)';
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, null, $userAgent);

            expect($entry->device_info)->toBe($userAgent);
        });

        it('accepts null device info', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, null, null);

            expect($entry->device_info)->toBeNull();
        });
    });

    describe('Client ID Handling', function () {
        it('accepts client ID as integer', function () {
            $user = User::factory()->create();
            $client = Client::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, $client->id, null, null);

            expect($entry->client_id)->toBe($client->id)
                ->and($entry->client_id)->toBeInt();
        });

        it('accepts client ID as string', function () {
            $user = User::factory()->create();
            $client = Client::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, (string) $client->id, null, null);

            expect($entry->client_id)->toBe($client->id);
        });

        it('defaults to client ID 1 when null provided', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, null, null, null);

            expect($entry->client_id)->toBe(1);
        });
    });

    describe('Token Handling', function () {
        it('stores token ID from NewAccessToken', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test-token');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, null, null);

            expect($entry->token_id)->toBe($token->accessToken->id)
                ->and($entry->token_id)->not->toBeNull();
        });

        it('creates separate entries for different tokens', function () {
            $user = User::factory()->create();
            $token1 = $user->createToken('token-1');
            $token2 = $user->createToken('token-2');
            $action = new TrackAuthenticationEntryAction();

            $entry1 = $action->execute($user, $token1, 1, null, null);
            $entry2 = $action->execute($user, $token2, 1, null, null);

            expect($entry1->token_id)->not->toBe($entry2->token_id)
                ->and(Entry::where('user_id', $user->id)->count())->toBe(2);
        });
    });

    describe('Timestamp Handling', function () {
        it('sets logged_in_at to current time', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $before = now()->subSecond();
            $entry = $action->execute($user, $token, 1, null, null);
            $after = now()->addSecond();

            expect($entry->logged_in_at->timestamp)->toBeGreaterThanOrEqual($before->timestamp)
                ->and($entry->logged_in_at->timestamp)->toBeLessThanOrEqual($after->timestamp);
        });

        it('leaves logged_out_at as null on creation', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();

            $entry = $action->execute($user, $token, 1, null, null);

            expect($entry->logged_out_at)->toBeNull();
        });
    });

    describe('Multiple Entries', function () {
        it('creates multiple entries for same user from different clients', function () {
            $user = User::factory()->create();
            $client1 = Client::factory()->create();
            $client2 = Client::factory()->create();
            $token1 = $user->createToken('web');
            $token2 = $user->createToken('mobile');
            $action = new TrackAuthenticationEntryAction();

            $entry1 = $action->execute($user, $token1, $client1->id, '192.168.1.1', 'Web Browser');
            $entry2 = $action->execute($user, $token2, $client2->id, '192.168.1.2', 'Mobile App');

            expect(Entry::where('user_id', $user->id)->count())->toBe(2)
                ->and($entry1->client_id)->not->toBe($entry2->client_id);
        });

        it('tracks login history chronologically', function () {
            $user = User::factory()->create();
            $action = new TrackAuthenticationEntryAction();

            $entries = collect();
            for ($i = 0; $i < 3; $i++) {
                $token = $user->createToken("token-{$i}");
                $entries->push($action->execute($user, $token, 1, null, null));
            }

            $dbEntries = Entry::where('user_id', $user->id)
                ->orderBy('logged_in_at', 'asc')
                ->get();

            expect($dbEntries)->toHaveCount(3)
                ->and($dbEntries->first()->logged_in_at)->toBeLessThanOrEqual($dbEntries->last()->logged_in_at);
        });
    });
});
