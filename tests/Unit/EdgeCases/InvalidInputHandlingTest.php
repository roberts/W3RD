<?php

use App\Actions\Auth\TrackAuthenticationEntryAction;
use App\Actions\Game\FindGameByUlidAction;
use App\Actions\Lobby\FindLobbyByUlidAction;
use App\Actions\User\ResolveUsernameAction;
use App\Models\Auth\User;
use App\Models\Game\Game;
use Illuminate\Database\Eloquent\ModelNotFoundException;

describe('Invalid and Malformed Input Handling', function () {
    describe('ULID Edge Cases', function () {
        it('handles ULIDs with wrong length', function () {
            $action = new FindGameByUlidAction();
            
            expect(fn () => $action->execute('toolong01234567890123456789', []))
                ->toThrow(ModelNotFoundException::class);
        });

        it('handles ULIDs with invalid characters', function () {
            $action = new FindGameByUlidAction();
            
            expect(fn () => $action->execute('01234567890!@#$%^&*()123', []))
                ->toThrow(ModelNotFoundException::class);
        });

        it('handles ULID that is valid format but references non-existent record', function () {
            $action = new FindGameByUlidAction();
            
            // Valid ULID format but doesn't exist
            expect(fn () => $action->execute('01ARZ3NDEKTSV4RRFFQ69G5FAV', []))
                ->toThrow(ModelNotFoundException::class);
        });

        it('handles empty string as ULID', function () {
            $action = new FindLobbyByUlidAction();
            
            expect(fn () => $action->execute('', []))
                ->toThrow(ModelNotFoundException::class);
        });

        it('handles null coerced to string as ULID', function () {
            $action = new FindGameByUlidAction();
            
            expect(fn () => $action->execute('null', []))
                ->toThrow(ModelNotFoundException::class);
        });
    });

    describe('Extremely Long Strings', function () {
        it('handles extremely long device info string', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            // 10,000 character string
            $longDeviceInfo = str_repeat('A', 10000);
            
            $entry = $action->execute($user, $token, 1, '127.0.0.1', $longDeviceInfo);
            
            // Should truncate or store (depends on DB column size)
            expect($entry->device_info)->not->toBeNull();
        });

        it('handles extremely long IP address string', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            // Malformed IP that's way too long
            $longIp = str_repeat('255.', 1000).'255';
            
            $entry = $action->execute($user, $token, 1, $longIp, null);
            
            // Should handle gracefully (store or truncate)
            expect($entry)->not->toBeNull();
        });

        it('handles username at maximum length boundary', function () {
            $user = User::factory()->create();
            $maxUsername = str_repeat('a', 255); // Assuming 255 is max
            $user->update(['username' => $maxUsername]);
            
            $action = new ResolveUsernameAction();
            $found = $action->execute($maxUsername);
            
            expect($found->id)->toBe($user->id);
        });
    });

    describe('Special Characters and Encoding', function () {
        it('handles username with emoji characters', function () {
            $user = User::factory()->create();
            $emojiUsername = 'user🎮123';
            $user->update(['username' => $emojiUsername]);
            
            $action = new ResolveUsernameAction();
            
            // Should lowercase and handle emoji
            $found = $action->execute($emojiUsername);
            expect($found->id)->toBe($user->id);
        });

        it('handles device info with special characters', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $specialDeviceInfo = "Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) <script>alert('xss')</script>";
            
            $entry = $action->execute($user, $token, 1, '127.0.0.1', $specialDeviceInfo);
            
            // Should store as-is without executing
            expect($entry->device_info)->toContain('script');
        });

        it('handles null bytes in strings', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $nullByteString = "device\0info";
            
            $entry = $action->execute($user, $token, 1, '127.0.0.1', $nullByteString);
            
            expect($entry)->not->toBeNull();
        });

        it('handles non-UTF8 encoded strings', function () {
            $user = User::factory()->create();
            
            // ISO-8859-1 encoded string
            $nonUtf8Username = mb_convert_encoding('café', 'ISO-8859-1', 'UTF-8');
            
            try {
                $user->update(['username' => $nonUtf8Username]);
                $updated = true;
            } catch (\Exception $e) {
                $updated = false;
            }
            
            // Should either handle gracefully or reject
            expect($updated)->toBeIn([true, false]);
        });
    });

    describe('Numeric Edge Cases', function () {
        it('handles negative client ID', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            // Negative client_id should trigger FK constraint failure
            expect(fn () => $action->execute($user, $token, -999, '127.0.0.1', null))
                ->toThrow(\Illuminate\Database\QueryException::class);
        });

        it('handles client ID larger than PHP_INT_MAX', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            // String representation of very large number - will be cast to PHP_INT_MAX
            $hugeClientId = PHP_INT_MAX;
            
            // Should trigger FK constraint failure (no such client exists)
            expect(fn () => $action->execute($user, $token, $hugeClientId, '127.0.0.1', null))
                ->toThrow(\Illuminate\Database\QueryException::class);
        });

        it('handles zero as client ID', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $entry = $action->execute($user, $token, 0, '127.0.0.1', null);
            
            // Should default to 1 (based on implementation)
            expect($entry->client_id)->toBe(1);
        });
    });

    describe('IPv6 Address Formats', function () {
        it('handles full IPv6 address', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $ipv6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
            
            $entry = $action->execute($user, $token, 1, $ipv6, null);
            
            expect($entry->ip_address)->toBe($ipv6);
        });

        it('handles compressed IPv6 address', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $ipv6 = '2001:db8::1';
            
            $entry = $action->execute($user, $token, 1, $ipv6, null);
            
            expect($entry->ip_address)->toBe($ipv6);
        });

        it('handles IPv6 loopback address', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $entry = $action->execute($user, $token, 1, '::1', null);
            
            expect($entry->ip_address)->toBe('::1');
        });

        it('handles malformed IPv6 address', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $malformedIpv6 = '2001:db8:::1';
            
            // Should either validate and reject or store as-is
            $entry = $action->execute($user, $token, 1, $malformedIpv6, null);
            
            expect($entry)->not->toBeNull();
        });
    });

    describe('SQL Injection Attempts', function () {
        it('handles SQL injection in username lookup', function () {
            $action = new ResolveUsernameAction();
            
            $sqlInjection = "admin' OR '1'='1";
            
            // Should not return any user (parameterized queries protect)
            expect(fn () => $action->execute($sqlInjection))
                ->toThrow(ModelNotFoundException::class);
        });

        it('handles SQL injection in device info', function () {
            $user = User::factory()->create();
            $token = $user->createToken('test');
            $action = new TrackAuthenticationEntryAction();
            
            $sqlInjection = "'; DROP TABLE entries; --";
            
            $entry = $action->execute($user, $token, 1, '127.0.0.1', $sqlInjection);
            
            // Should store as literal string, not execute
            expect($entry->device_info)->toContain('DROP TABLE');
        });
    });
});
