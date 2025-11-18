<?php

use App\Actions\Client\ResolveClientIdAction;
use App\Models\Access\Client;
use Illuminate\Http\Request;

describe('ResolveClientIdAction', function () {
    describe('Header Parsing', function () {
        it('resolves client id from X-Client-Key header as integer', function () {
            $client = Client::factory()->create();
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => (string) $client->id,
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe($client->id)
                ->and($clientId)->toBeInt();
        });

        it('resolves client id from X-Client-Key header as string', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '5',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(5)
                ->and($clientId)->toBeInt();
        });

        it('converts string client id to integer', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '123',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBeInt()
                ->and($clientId)->toBe(123);
        });
    });

    describe('Default Behavior', function () {
        it('returns default client id 1 when header is missing', function () {
            $request = Request::create('/', 'GET');
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(1);
        });

        it('returns default client id 1 when header is empty string', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(1);
        });

        it('returns default client id 1 when header is null', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => null,
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(1);
        });

        it('returns default client id 1 when header is zero', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '0',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(1);
        });
    });

    describe('Edge Cases', function () {
        it('handles numeric string with leading zeros', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '007',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(7);
        });

        it('handles large client id numbers', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '999999',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(999999);
        });

        it('handles non-numeric string by returning default', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => 'invalid',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(1);
        });

        it('handles negative numbers by casting to int', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '-5',
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            // Negative string casts to negative int via (int) cast
            expect($clientId)->toBe(-5);
        });
    });

    describe('HTTP Methods', function () {
        it('works with GET request', function () {
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '5',
            ]);
            $action = new ResolveClientIdAction();

            expect($action->execute($request))->toBe(5);
        });

        it('works with POST request', function () {
            $request = Request::create('/', 'POST', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '5',
            ]);
            $action = new ResolveClientIdAction();

            expect($action->execute($request))->toBe(5);
        });

        it('works with PUT request', function () {
            $request = Request::create('/', 'PUT', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '5',
            ]);
            $action = new ResolveClientIdAction();

            expect($action->execute($request))->toBe(5);
        });

        it('works with DELETE request', function () {
            $request = Request::create('/', 'DELETE', [], [], [], [
                'HTTP_X_CLIENT_KEY' => '5',
            ]);
            $action = new ResolveClientIdAction();

            expect($action->execute($request))->toBe(5);
        });
    });

    describe('Real-world Scenarios', function () {
        it('handles web client', function () {
            $webClient = Client::factory()->create(['name' => 'Gamer Protocol Web']);
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => (string) $webClient->id,
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe($webClient->id);
        });

        it('handles mobile client', function () {
            $mobileClient = Client::factory()->create(['name' => 'Gamer Protocol iOS']);
            $request = Request::create('/', 'GET', [], [], [], [
                'HTTP_X_CLIENT_KEY' => (string) $mobileClient->id,
            ]);
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe($mobileClient->id);
        });

        it('defaults to 1 for AI agents without header', function () {
            $request = Request::create('/', 'GET');
            $action = new ResolveClientIdAction();

            $clientId = $action->execute($request);

            expect($clientId)->toBe(1); // Gamer Protocol Web default
        });
    });
});
