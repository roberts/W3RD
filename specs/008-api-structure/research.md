# Phase 0 Research: Production-Ready V1 API Structure

**Feature**: 008-api-structure | **Date**: November 20, 2025

## Overview

This research document consolidates architectural decisions, design patterns, and best practices for restructuring the GamerProtocol.io v1 API into a production-ready headless infrastructure with 9 logical namespaces.

## Research Areas

### 1. API Namespace Organization & RESTful Design

**Decision**: Organize API into 9 logical namespaces based on functional domains

**Rationale**:
- **Separation of Concerns**: Platform services (System, Library, Auth, Account) are logically separated from gameplay (Floor, Games) and financial operations (Economy)
- **Discoverability**: Clear namespace hierarchy makes API easier to understand and navigate
- **Scalability**: Each namespace can be independently evolved, versioned, and potentially extracted into microservices
- **Developer Experience**: Intuitive naming reduces onboarding time and API misuse

**Alternatives Considered**:
- **Flat structure** (all endpoints at root): Rejected due to poor scalability and namespace collisions
- **Feature-based grouping** (matchmaking, progression, monetization): Rejected because it mixes technical layers and creates ambiguous boundaries
- **Domain-driven design modules** (user-context, game-context, billing-context): Rejected as too abstract for API consumers

**Implementation Pattern**:
```php
// routes/api.php namespace organization
Route::prefix('v1')->group(function () {
    // System & Platform
    Route::prefix('system')->group(function () {
        Route::get('health', [HealthController::class, 'check']);
        Route::get('time', [TimeController::class, 'show']);
        Route::get('config', [ConfigController::class, 'show']);
    });
    
    // Game Library (public)
    Route::prefix('library')->group(function () {
        Route::get('/', [GameLibraryController::class, 'index']);
        Route::get('{key}', [GameLibraryController::class, 'show']);
        Route::get('{key}/rules', [GameRulesController::class, 'show']);
        Route::get('{key}/entities', [GameLibraryController::class, 'entities']);
    });
    
    // Authentication (public)
    Route::prefix('auth')->group(function () {
        Route::post('register', [RegisterController::class, 'store']);
        Route::post('login', [LoginController::class, 'store']);
        Route::post('social', [SocialAuthController::class, 'store']);
        Route::post('logout', [LogoutController::class, 'destroy'])->middleware('auth:sanctum');
    });
    
    // Protected routes require auth:sanctum
    Route::middleware(['auth:sanctum'])->group(function () {
        // Account Management
        Route::prefix('account')->group(function () {
            Route::get('profile', [ProfileController::class, 'show']);
            Route::patch('profile', [ProfileController::class, 'update']);
            Route::get('progression', [ProgressionController::class, 'show']);
            Route::get('records', [RecordsController::class, 'show']);
            Route::get('alerts', [AlertsController::class, 'index']);
            Route::post('alerts/read', [AlertsController::class, 'markAsRead']);
        });
        
        // Floor Coordination
        Route::prefix('floor')->group(function () {
            Route::get('lobbies', [LobbyController::class, 'index']);
            Route::post('lobbies', [LobbyController::class, 'store']);
            Route::post('lobbies/{id}/seat', [LobbyController::class, 'seat']);
            Route::post('signals', [SignalController::class, 'store']);
            Route::delete('signals/{id}', [SignalController::class, 'destroy']);
            Route::post('proposals', [ProposalController::class, 'store']);
            Route::post('proposals/{id}/accept', [ProposalController::class, 'accept']);
        });
        
        // Active Games
        Route::prefix('games')->group(function () {
            Route::get('/', [GameController::class, 'index']);
            Route::get('{ulid}', [GameController::class, 'show']);
            Route::post('{ulid}/actions', [GameActionController::class, 'store']);
            Route::get('{ulid}/turn', [GameTurnController::class, 'show']);
            Route::get('{ulid}/timeline', [GameTimelineController::class, 'index']);
            Route::post('{ulid}/concede', [GameConcedeController::class, 'store']);
            Route::post('{ulid}/abandon', [GameAbandonController::class, 'store']);
            Route::get('{ulid}/outcome', [GameOutcomeController::class, 'show']);
        });
        
        // Economy
        Route::prefix('economy')->group(function () {
            Route::get('balance', [BalanceController::class, 'show']);
            Route::get('transactions', [TransactionController::class, 'index']);
            Route::post('cashier', [CashierController::class, 'store']);
            Route::get('plans', [PlanController::class, 'index']);
            Route::post('receipts/{provider}', [ReceiptController::class, 'verify']);
        });
        
        // Data Feeds (SSE streams)
        Route::prefix('feeds')->group(function () {
            Route::get('live-scores', [LiveScoresController::class, 'stream']);
            Route::get('casino-floor', [CasinoFloorController::class, 'stream']);
        });
        
        // Competitions
        Route::prefix('competitions')->group(function () {
            Route::get('/', [CompetitionController::class, 'index']);
            Route::post('{id}/enter', [EntryController::class, 'store']);
            Route::get('{id}/structure', [StructureController::class, 'show']);
            Route::get('{id}/bracket', [BracketController::class, 'show']);
            Route::get('{id}/standings', [StandingsController::class, 'show']);
        });
    });
    
    // Webhooks (vendor authenticated)
    Route::post('webhooks/{provider}', [WebhookController::class, 'handle']);
});
```

### 2. Migration Strategy from Legacy Endpoints

**Decision**: Implement dual routing with deprecation warnings during transition period

**Rationale**:
- **Zero Downtime**: Existing clients continue working during migration
- **Gradual Migration**: Clients can migrate namespace by namespace
- **Clear Communication**: Deprecation headers inform clients of upcoming changes
- **Backward Compatibility**: Maintains promise of one major version support

**Alternatives Considered**:
- **Hard cutover**: Rejected due to breaking all existing clients simultaneously
- **API gateway aliasing**: Rejected as it hides the new structure and delays client migration
- **v2 API**: Rejected because structure change doesn't warrant full version bump

**Implementation Pattern**:
```php
// Maintain legacy routes with deprecation warnings
Route::prefix('v1')->group(function () {
    // Legacy endpoint (deprecated)
    Route::get('status', function () {
        return response()->json(app(HealthController::class)->check())
            ->header('X-API-Deprecated', 'true')
            ->header('X-API-Deprecation-Date', '2026-06-01')
            ->header('X-API-Replacement', '/v1/system/health');
    });
    
    // New endpoint
    Route::get('system/health', [HealthController::class, 'check']);
});

// Middleware to track deprecation usage
class TrackDeprecatedEndpoints
{
    public function handle($request, Closure $next)
    {
        if ($request->headers->has('X-API-Deprecated')) {
            Log::channel('deprecation')->info('Deprecated endpoint accessed', [
                'path' => $request->path(),
                'client' => $request->header('X-Client-Key'),
                'replacement' => $request->headers->get('X-API-Replacement'),
            ]);
        }
        
        return $next($request);
    }
}
```

### 3. Controller Organization & Responsibility

**Decision**: Single-responsibility controllers with one primary action per controller

**Rationale**:
- **Clarity**: Each controller has a clear, singular purpose
- **Testability**: Smaller controllers are easier to unit test
- **Maintainability**: Changes are localized to specific controllers
- **RESTful Design**: Aligns with RESTful resource/action pairing

**Alternatives Considered**:
- **Resource controllers**: Rejected because many operations don't map to CRUD
- **Monolithic controllers**: Rejected due to poor maintainability and testability
- **Action classes**: Rejected as over-engineering for current scale

**Implementation Pattern**:
```php
// Good: Single responsibility
class GameConcedeController extends Controller
{
    public function store(string $ulid, ConcedeGameRequest $request)
    {
        $game = Game::findByUlid($ulid);
        $this->authorize('concede', $game);
        
        $outcome = app(GameService::class)->concede(
            game: $game,
            player: $request->user()
        );
        
        return new GameOutcomeResource($outcome);
    }
}

// Bad: Multiple responsibilities
class GameController extends Controller
{
    public function concede() { /* ... */ }
    public function abandon() { /* ... */ }
    public function forfeit() { /* ... */ }
    // Too many actions, unclear responsibility
}
```

### 4. Data Transfer Objects (DTOs) with Spatie Laravel Data

**Decision**: Use Spatie Laravel Data for type-safe API responses and validation

**Rationale**:
- **Type Safety**: Compile-time type checking prevents runtime errors
- **Validation**: Declarative validation rules co-located with data structure
- **Transformation**: Consistent serialization across all endpoints
- **Documentation**: DTOs serve as self-documenting API contracts

**Alternatives Considered**:
- **Plain arrays**: Rejected due to lack of type safety and validation
- **Laravel Resources**: Rejected because they don't provide input validation
- **Custom DTO implementation**: Rejected to avoid reinventing the wheel

**Implementation Pattern**:
```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Max;

class GameTitleData extends Data
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        #[Required, Min(2), Max(10)]
        public int $playerCount,
        public PacingType $pacing,
        public ComplexityLevel $complexity,
        public array $categoryTags,
        public ?string $thumbnail = null,
        public ?int $averageSessionMinutes = null,
    ) {}
    
    public static function fromModel(Title $title): self
    {
        return new self(
            key: $title->key,
            name: $title->name,
            description: $title->description,
            playerCount: $title->player_count,
            pacing: $title->pacing,
            complexity: $title->complexity,
            categoryTags: $title->category_tags,
            thumbnail: $title->thumbnail_url,
            averageSessionMinutes: $title->average_session_minutes,
        );
    }
}

// Usage in controller
class GameLibraryController extends Controller
{
    public function show(string $key): GameTitleData
    {
        $title = Title::where('key', $key)->firstOrFail();
        return GameTitleData::from($title);
    }
}
```

### 5. Service Layer Architecture

**Decision**: Introduce service classes for complex business logic spanning multiple models

**Rationale**:
- **Separation of Concerns**: Controllers handle HTTP, services handle business logic
- **Reusability**: Services can be called from controllers, jobs, commands, and tests
- **Testability**: Business logic can be tested independently of HTTP layer
- **Maintainability**: Complex operations are encapsulated in dedicated classes

**Alternatives Considered**:
- **Fat models**: Rejected due to violating single responsibility principle
- **Fat controllers**: Rejected due to poor testability and reusability
- **Repository pattern**: Rejected as over-engineering for Eloquent ORM usage

**Implementation Pattern**:
```php
class FloorCoordinationService
{
    public function createLobby(
        User $host,
        Title $title,
        LobbySettings $settings
    ): Lobby {
        return DB::transaction(function () use ($host, $title, $settings) {
            $lobby = Lobby::create([
                'host_id' => $host->id,
                'title_id' => $title->id,
                'visibility' => $settings->visibility,
                'max_players' => $settings->maxPlayers,
                'join_code' => $this->generateJoinCode(),
            ]);
            
            // Auto-seat the host
            $lobby->players()->attach($host->id, [
                'seat_number' => 1,
                'status' => PlayerStatus::Ready,
            ]);
            
            // Broadcast lobby created event
            broadcast(new LobbyCreatedEvent($lobby));
            
            return $lobby;
        });
    }
    
    public function matchPlayers(MatchmakingSignal $signal): ?Game
    {
        // Find compatible signals
        $compatibleSignals = MatchmakingSignal::query()
            ->where('title_id', $signal->title_id)
            ->where('id', '!=', $signal->id)
            ->whereRaw('ABS(elo_rating - ?) < ?', [$signal->elo_rating, 200])
            ->orderByRaw('ABS(elo_rating - ?) ASC', [$signal->elo_rating])
            ->limit(1)
            ->lockForUpdate()
            ->get();
            
        if ($compatibleSignals->isEmpty()) {
            return null;
        }
        
        $opponent = $compatibleSignals->first();
        
        return DB::transaction(function () use ($signal, $opponent) {
            // Create game
            $game = app(GameService::class)->create(
                title: $signal->title,
                players: [$signal->user, $opponent->user]
            );
            
            // Delete matched signals
            MatchmakingSignal::destroy([$signal->id, $opponent->id]);
            
            // Notify both players
            broadcast(new MatchFoundEvent($game));
            
            return $game;
        });
    }
}
```

### 6. API Versioning Strategy

**Decision**: URL-based versioning with semantic versioning principles

**Rationale**:
- **Explicit**: Version is clearly visible in URL
- **Cache-friendly**: Different versions have different URLs
- **Client-friendly**: No special headers required
- **Industry Standard**: Most widely adopted approach

**Alternatives Considered**:
- **Header-based versioning**: Rejected due to poor cache support and debugging difficulty
- **Accept header content negotiation**: Rejected as non-standard for REST APIs
- **Query parameter versioning**: Rejected due to cache key pollution

**Implementation Pattern**:
```php
// Current: v1 (stable)
Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// Future: v2 (when breaking changes needed)
Route::prefix('v2')->group(base_path('routes/api/v2.php'));

// Version deprecation policy documented in API
// - v1 supported until v3 release
// - Minimum 12 months notice before deprecation
// - Sunset header returned when deprecated: Sunset: Sat, 1 Jun 2026 00:00:00 GMT
```

### 7. Idempotency for Game Actions

**Decision**: Require `Idempotency-Key` header for all state-mutating game operations

**Rationale**:
- **Duplicate Prevention**: Network retries don't cause duplicate moves
- **Consistency**: Ensures game state integrity
- **User Experience**: Prevents accidental double-moves on slow connections
- **Industry Standard**: Follows Stripe and other payment API patterns

**Alternatives Considered**:
- **Automatic deduplication**: Rejected because it's ambiguous (how long to remember?)
- **Client-side prevention only**: Rejected as unreliable with network issues
- **No idempotency**: Rejected due to poor user experience and data integrity risks

**Implementation Pattern**:
```php
class EnsureIdempotency
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->hasHeader('Idempotency-Key')) {
            return response()->json([
                'error' => 'Idempotency-Key header required',
                'message' => 'Include a unique Idempotency-Key header to prevent duplicate actions',
            ], 400);
        }
        
        $key = $request->header('Idempotency-Key');
        $cacheKey = "idempotency:{$key}";
        
        // Check if we've seen this key before
        if ($cached = Cache::get($cacheKey)) {
            return response()->json($cached['response'], $cached['status']);
        }
        
        $response = $next($request);
        
        // Store successful responses for 24 hours
        if ($response->isSuccessful()) {
            Cache::put($cacheKey, [
                'response' => $response->getData(true),
                'status' => $response->status(),
            ], now()->addHours(24));
        }
        
        return $response;
    }
}

// Apply to game action routes
Route::post('games/{ulid}/actions', [GameActionController::class, 'store'])
    ->middleware(['auth:sanctum', EnsureIdempotency::class]);
```

### 8. Server-Sent Events (SSE) for Real-Time Feeds

**Decision**: Use native PHP SSE implementation for six engagement-focused data feeds

**Feed Endpoints**:
1. **`/feeds/games`**: Live public game activity (starts, moves, completions)
2. **`/feeds/wins`**: Win announcements with stakes and outcomes
3. **`/feeds/leaderboards`**: Rank changes and high score updates
4. **`/feeds/tournaments`**: Tournament progress and bracket updates
5. **`/feeds/challenges`**: Challenge activity (issued, accepted, completed)
6. **`/feeds/achievements`**: Platform-wide achievement unlocks

**Rationale**:
- **Simplicity**: No additional server infrastructure required beyond PHP
- **Scalability**: One-way communication is more efficient than bidirectional WebSockets
- **Compatibility**: Works through corporate proxies that block WebSockets
- **Use Case Fit**: Feeds are read-only streams, don't need client-to-server messages
- **Engagement**: Creates social proof, FOMO, and community atmosphere
- **Performance**: Each feed can be independently scaled and filtered

**Alternatives Considered**:
- **Laravel Reverb (WebSockets)**: Rejected for feeds as overkill for one-way communication
- **Polling**: Rejected due to high latency and server load
- **Long polling**: Rejected as more complex than SSE with no benefits

**Implementation Pattern**:
```php
class GamesController extends Controller
{
    public function stream(Request $request): StreamedResponse
    {
        return response()->stream(function () use ($request) {
            // Set SSE headers
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no'); // Nginx unbuffering
            
            $lastEventId = $request->header('Last-Event-ID', 0);
            
            while (true) {
                // Check if client disconnected
                if (connection_aborted()) {
                    break;
                }
                
                // Fetch new public game events since last ID
                $events = GameEvent::where('id', '>', $lastEventId)
                    ->whereIn('event_type', ['game_started', 'game_completed'])
                    ->whereHas('game', fn($q) => $q->where('visibility', 'public'))
                    ->orderBy('id')
                    ->limit(50)
                    ->get();
                
                foreach ($events as $event) {
                    echo "id: {$event->id}\n";
                    echo "event: game-update\n";
                    echo 'data: ' . json_encode([
                        'game_ulid' => $event->game_ulid,
                        'event_type' => $event->event_type,
                        'players' => $event->data['players'],
                        'game_type' => $event->data['game_type'],
                        'timestamp' => $event->created_at->toIso8601String(),
                    ]) . "\n\n";
                    
                    $lastEventId = $event->id;
                    ob_flush();
                    flush();
                }
                
                // Wait before checking for new events
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

### 9. OpenAPI/Swagger Documentation

**Decision**: Generate OpenAPI 3.1 specifications for each namespace

**Rationale**:
- **Contract-First**: Specifications serve as source of truth
- **Auto-Generated Clients**: SDKs can be generated for multiple languages
- **Interactive Documentation**: Swagger UI provides testing interface
- **Validation**: Automated contract testing ensures implementation matches spec

**Alternatives Considered**:
- **Manual documentation**: Rejected due to drift from implementation
- **Code-first generation**: Rejected because annotations pollute code
- **Single large spec**: Rejected due to poor maintainability

**Implementation Pattern**:
```yaml
# contracts/games.openapi.yaml
openapi: 3.1.0
info:
  title: GamerProtocol.io Games API
  version: 1.0.0
  description: Active game instance management and gameplay actions

servers:
  - url: https://api.gamerprotocol.io/v1
    description: Production
  - url: https://staging-api.gamerprotocol.io/v1
    description: Staging

paths:
  /games:
    get:
      summary: List user's active games
      operationId: listGames
      tags: [Games]
      security:
        - bearerAuth: []
        - clientKey: []
      parameters:
        - name: status
          in: query
          schema:
            type: string
            enum: [active, completed, abandoned]
        - name: limit
          in: query
          schema:
            type: integer
            minimum: 1
            maximum: 100
            default: 20
      responses:
        '200':
          description: List of games
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Game'
                  meta:
                    $ref: '#/components/schemas/PaginationMeta'

  /games/{ulid}/actions:
    post:
      summary: Execute game action
      operationId: executeAction
      tags: [Games]
      security:
        - bearerAuth: []
        - clientKey: []
      parameters:
        - name: ulid
          in: path
          required: true
          schema:
            type: string
            pattern: '^[0-9A-Z]{26}$'
        - name: Idempotency-Key
          in: header
          required: true
          schema:
            type: string
            format: uuid
      requestBody:
        required: true
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/GameAction'
      responses:
        '200':
          description: Action executed successfully
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Game'
        '422':
          description: Invalid action
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/ValidationError'

components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
      bearerFormat: JWT
    clientKey:
      type: apiKey
      in: header
      name: X-Client-Key

  schemas:
    Game:
      type: object
      required: [ulid, title, status, players, created_at]
      properties:
        ulid:
          type: string
          pattern: '^[0-9A-Z]{26}$'
          example: '01HQZDJ3K8M9N1P0Q2R4S6T8V0'
        title:
          type: string
          example: 'connect-four'
        status:
          type: string
          enum: [waiting, active, completed, abandoned]
        players:
          type: array
          items:
            $ref: '#/components/schemas/GamePlayer'
        current_turn:
          type: string
          nullable: true
        state:
          type: object
          description: Game-specific state (board, hands, etc.)
        created_at:
          type: string
          format: date-time
        updated_at:
          type: string
          format: date-time
```

### 10. Economy Namespace & Cashier Service

**Decision**: Implement entertainment-only virtual balance tracking with cashier service for approved clients

**Critical Context**: The economy namespace tracks virtual tokens and chips **for entertainment purposes only**. No real money or cryptocurrency transactions occur. This is not a financial system, wagering platform, or gambling service.

**Rationale**:
- **Legal Compliance**: Clear separation from financial/gambling regulations
- **Approved Client Model**: Only authorized applications can modify user balances
- **Unified Endpoint**: Single `/economy/cashier` endpoint replaces separate buy-in/cash-out operations
- **Reference Tracking**: Clients provide reference IDs for internal reconciliation
- **Entertainment Focus**: Emphasizes gameplay experience over monetary transactions

**Alternatives Considered**:
- **Financial transaction endpoints** (deposit/withdraw/buy-in/cash-out): Rejected due to regulatory implications and confusion about platform purpose
- **Direct balance API**: Rejected due to security concerns and lack of client accountability
- **Payment gateway integration**: Rejected as this is entertainment-only, not a payment system

**Implementation Pattern**:
```php
class CashierController extends Controller
{
    public function store(CashierRequest $request)
    {
        // Verify approved client authorization
        if (!$request->user()->client?->use_cashier) {
            throw new CashierUnauthorizedException(
                'Only approved client applications can access the cashier service'
            );
        }
        
        $validated = $request->validated();
        
        $transaction = DB::transaction(function () use ($request, $validated) {
            $user = $request->user();
            $client = $request->user()->client;
            
            // Get or create balance for this user-client combination
            $balance = Balance::firstOrCreate(
                ['user_id' => $user->id, 'client_id' => $client->id],
                ['tokens' => 0, 'chips' => 0, 'locked_in_games' => 0]
            );
            
            $amount = $validated['amount'];
            $currency = $validated['currency']; // 'tokens' or 'chips'
            $action = $validated['action'];     // 'add' or 'remove'
            
            // Update balance based on action
            if ($action === 'add') {
                $balance->$currency += $amount;
            } else {
                if ($balance->$currency < $amount) {
                    throw new InsufficientBalanceException(
                        "Insufficient {$currency} balance for client {$client->name}"
                    );
                }
                $balance->$currency -= $amount;
            }
            
            $balance->save();
            
            // Record transaction with client context
            return Transaction::create([
                'ulid' => Str::ulid(),
                'user_id' => $user->id,
                'client_id' => $client->id,
                'action' => $action,
                'amount' => $amount,
                'currency' => $currency,
                'reference' => $validated['reference'],
                'source' => 'cashier',
                'metadata' => [
                    'client_name' => $client->name,
                ],
            ]);
        });
        
        return new TransactionResource($transaction);
    }
}

// Request validation
class CashierRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:add,remove'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'in:tokens,chips'],
            'reference' => ['required', 'string', 'max:255'],
        ];
    }
}

// Exception handling
class CashierUnauthorizedException extends Exception
{
    public function render()
    {
        return response()->json([
            'error' => 'cashier_unauthorized',
            'message' => 'Only approved client applications can access the cashier service',
        ], 403);
    }
}
```

**Authorization Model**:
```php
// Migration for client approvals
Schema::table('clients', function (Blueprint $table) {
    $table->boolean('use_cashier')->default(false);
    $table->timestamp('cashier_approved_at')->nullable();
    $table->foreignId('cashier_approved_by')->nullable()->constrained('users');
});

// Middleware to verify cashier access
class EnsureCashierAccess
{
    public function handle(Request $request, Closure $next)
    {
        $client = $request->user()->client;
        
        if (!$client || !$client->use_cashier) {
            throw new CashierUnauthorizedException();
        }
        
        return $next($request);
    }
}

// Apply to cashier endpoint
Route::post('economy/cashier', [CashierController::class, 'store'])
    ->middleware(['auth:sanctum', EnsureCashierAccess::class]);
```

**Client Integration Pattern**:
```php
// Example: Client application adding tokens to user balance
$response = Http::withToken($userToken)
    ->withHeaders(['X-Client-Key' => config('services.protocol.client_key')])
    ->post('https://api.gamerprotocol.io/v1/economy/cashier', [
        'action' => 'add',
        'amount' => 100.00,
        'currency' => 'tokens',
        'reference' => 'client-txn-' . Str::uuid(),
    ]);

// Example: Client application removing chips from user balance
$response = Http::withToken($userToken)
    ->withHeaders(['X-Client-Key' => config('services.protocol.client_key')])
    ->post('https://api.gamerprotocol.io/v1/economy/cashier', [
        'action' => 'remove',
        'amount' => 50.00,
        'currency' => 'chips',
        'reference' => 'client-redemption-' . Str::uuid(),
    ]);
```

**Important Notes**:
- **Multi-Client Balances**: Each user has separate balances per client application
- **Client-Specific Chips**: Chips can only be used in games where all players are using the same client
- **Client Matching Logic**: Before allowing chip buy-ins, system verifies all game participants are authenticated via the same client
- **Token Flexibility**: Tokens may be transferable across clients (implementation specific)
- Virtual balances persist across sessions for entertainment continuity
- No withdrawal or payout mechanisms exist
- Balances cannot be converted to real money or cryptocurrency
- Client reference IDs enable reconciliation with client-side systems
- All transactions are logged with client_id for auditing and client reporting
- Game buy-ins/cash-outs track client_id to ensure proper balance isolation

**Game Buy-in Client Validation**:
```php
class GameBuyInService
{
    public function validateChipBuyIn(Game $game, User $user, float $amount): void
    {
        // Get all players' client IDs for this game
        $playerClients = $game->players->pluck('user.registration_client_id')->unique();
        
        // Chips can only be used if all players are from the same client
        if ($playerClients->count() > 1) {
            throw new MixedClientException(
                'Chip buy-ins require all players to be using the same client application'
            );
        }
        
        $clientId = $playerClients->first();
        $balance = Balance::where('user_id', $user->id)
            ->where('client_id', $clientId)
            ->first();
        
        if (!$balance || $balance->chips < $amount) {
            throw new InsufficientBalanceException(
                "Insufficient chips for {$user->client->name}"
            );
        }
        
        // Lock chips in game
        DB::transaction(function () use ($balance, $amount, $user, $clientId, $game) {
            $balance->chips -= $amount;
            $balance->locked_in_games += $amount;
            $balance->save();
            
            Transaction::create([
                'ulid' => Str::ulid(),
                'user_id' => $user->id,
                'client_id' => $clientId,
                'action' => 'game_buy_in',
                'amount' => $amount,
                'currency' => 'chips',
                'reference' => null,
                'source' => 'game',
                'metadata' => ['game_ulid' => $game->ulid],
            ]);
        });
    }
}
```

## Summary of Decisions

| Decision Area | Chosen Approach | Key Benefit |
|--------------|-----------------|-------------|
| Namespace Organization | 9 functional domains | Clear separation of concerns |
| Migration Strategy | Dual routing with deprecation | Zero downtime, gradual migration |
| Controller Design | Single-responsibility | Improved testability |
| Data Transfer | Spatie Laravel Data DTOs | Type safety and validation |
| Business Logic | Service layer | Reusability and testability |
| API Versioning | URL-based semantic versioning | Explicit and cache-friendly |
| Idempotency | Required header for mutations | Prevents duplicate actions |
| Real-Time Feeds | Server-Sent Events (SSE) | Simple and scalable |
| Documentation | OpenAPI 3.1 per namespace | Contract-first development |
| Virtual Economy | Entertainment-only cashier service | Legal compliance and clear purpose |
| Multi-Client Balances | Separate balances per user-client pair | Client-specific economies and isolation |

## Next Steps (Phase 1)

1. Create data model documentation for new entities (MatchmakingSignal, Proposal, Balance, Tournament)
2. Generate complete OpenAPI contracts for all 9 namespaces
3. Document developer quickstart guide with example API flows
4. Update agent context with new patterns and decisions
