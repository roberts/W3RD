# GamerProtocol.io Constitution

<!--
Sync Impact Report - Version 1.0.0 (Initial)
═══════════════════════════════════════════════════════════════════════════
Version Change: NONE → 1.0.0 (Initial ratification)
Created: 2025-11-20

PRINCIPLES ESTABLISHED:
✓ I. Headless API Architecture
✓ II. Dual Authorization Model
✓ III. Entertainment-Only Economy
✓ IV. Real-Time State Synchronization
✓ V. Multi-Client Isolation

TEMPLATES REQUIRING UPDATES:
✅ spec-template.md - Aligned with API namespace organization
✅ plan-template.md - Updated with constitution compliance checks
✅ tasks-template.md - Added principle-driven task categories
✅ commands/*.md - Generic guidance verified

FOLLOW-UP TODOS: None

SUGGESTED COMMIT MESSAGE:
docs: establish constitution v1.0.0 (headless API governance + multi-client principles)
═══════════════════════════════════════════════════════════════════════════
-->

## Core Principles

### I. Headless API Architecture

GamerProtocol.io operates as a **pure headless API backend** serving multiple frontend clients (web, mobile apps, Telegram mini-apps) through a unified RESTful interface. The API is organized into **9 logical namespaces** (System, Library, Auth, Account, Floor, Games, Economy, Feeds, Competitions) that separate concerns and provide intuitive endpoint discovery.

**Non-Negotiable Rules:**
- All business logic resides in the API backend; frontends are presentation layers only
- API endpoints must be stateless and horizontally scalable
- Every namespace must have clear boundaries with no cross-namespace dependencies
- Response format follows strict envelope pattern: `{"data": ..., "meta": {...}}`
- All game state stored in MySQL JSON columns for schema flexibility

**Rationale**: Headless architecture enables multiple branded frontends (TokenGames.io, Cabin Games, etc.) to share the same game logic, user data, and matchmaking infrastructure without code duplication. Namespace organization ensures teams can work independently on different API areas without conflicts.

### II. Dual Authorization Model (NON-NEGOTIABLE)

Every authenticated API request requires **two independent authorization layers**:
1. **User Authentication**: Laravel Sanctum Bearer token identifying the user
2. **Client Authorization**: X-Client-Key header identifying the application

Both must be present and valid. User tokens are scoped to the client that issued them.

**Non-Negotiable Rules:**
- Registration tracks which client onboarded each user (`registration_client_id`)
- Middleware must validate both tokens before processing requests
- API keys are stored in the `clients` table with platform identification
- Revoking a client's key immediately blocks all its users from API access
- Client-specific balances are isolated (users have separate balances per client)

**Rationale**: Dual authorization prevents unauthorized applications from accessing the platform, enables per-client billing/quotas, supports multi-tenant virtual economies, and provides granular access control for security and compliance. This is critical for protecting user data and preventing API abuse.

### III. Entertainment-Only Economy

The platform tracks **virtual tokens and chips for entertainment purposes exclusively**. No real money or cryptocurrency transactions occur within the virtual balance system. Real payments (subscriptions via Stripe, Google Play, Apple Store, Telegram) are tracked separately in the unified `transactions` table.

**Non-Negotiable Rules:**
- Virtual balances (`tokens`, `chips`) must never be presented as having monetary value
- All economy documentation must include the disclaimer: "For entertainment purposes only"
- Cashier endpoint (`POST /economy/cashier`) is restricted to approved clients only
- Chip buy-ins only allowed when all game players use the same client (client matching validation required)
- Transactions table clearly separates virtual balance operations from real payment records
- Balance-to-currency conversion mechanisms are prohibited

**Rationale**: Entertainment-only positioning ensures legal compliance and avoids gambling/wagering regulations. Client-specific chip restrictions prevent cross-client balance manipulation. Clear separation of virtual and real transactions maintains transparency and simplifies auditing.

### IV. Real-Time State Synchronization

Game state updates are delivered instantly via **Laravel Reverb (WebSockets)** and supplemented by **Server-Sent Events (SSE)** for six public data feeds (games, wins, leaderboards, tournaments, challenges, achievements).

**Non-Negotiable Rules:**
- Every game action must broadcast state changes to all subscribed players immediately
- SSE feeds must support reconnection with `Last-Event-ID` for missed events
- Idempotency-Key header required for all state-mutating game actions
- Board state stored in JSON columns with version tracking for conflict resolution
- Turn timers must be enforced server-side with automatic forfeit on expiration

**Rationale**: Real-time synchronization is essential for multiplayer gameplay UX. Idempotency prevents duplicate moves on network retries. Server-side timer enforcement ensures fair play and prevents client manipulation.

### V. Multi-Client Isolation

Each client application operates with **isolated virtual economies and controlled data visibility**. User balances are per-client, games can have client-specific chip requirements, and approved clients manage their own user balance lifecycles.

**Non-Negotiable Rules:**
- `balances` table has unique constraint on `(user_id, client_id)` - one balance per user per client
- Transactions must record `client_id` for balance operations
- Game buy-in logic validates all players use same client before allowing chip stakes
- Client approval for cashier access controlled via `clients.use_cashier` boolean
- Cross-client balance transfers prohibited unless explicitly implemented per-client agreement

**Rationale**: Multi-client isolation enables independent client economies, prevents cross-contamination of balances, supports client-specific monetization strategies, and ensures fair play within client-bounded game sessions. This architecture scales to unlimited client applications without interference.

## Technology Standards

### Required Stack Components

- **Framework**: Laravel 12.x (PHP 8.4+)
- **Database**: MySQL 8.0+ (JSON column support mandatory)
- **Authentication**: Laravel Sanctum 4.2+
- **Real-Time**: Laravel Reverb 1.6+
- **Subscriptions**: Laravel Cashier 16.0+ (Stripe integration)
- **Data Transfer**: Spatie Laravel Data 4.5+ (typed DTOs)
- **Testing**: Pest 4.1+ with Pest Plugin Laravel 4.0+

### Architecture Patterns

- **Service Layer**: Complex business logic encapsulated in service classes (e.g., `GameBuyInService`, `CashierService`)
- **Single-Responsibility Controllers**: One primary action per controller (e.g., `GameConcedeController`, `CashierController`)
- **Data Transfer Objects**: Spatie Laravel Data for type-safe API responses
- **Enums**: Game types, transaction types, payment providers as PHP 8.1+ enums
- **ULIDs**: Preferred over auto-increment IDs for public identifiers (games, transactions, etc.)
- **Event Broadcasting**: Laravel events for WebSocket state propagation

### Security & Compliance

- Two-factor authentication support (optional per user)
- Rate limiting on all API endpoints
- CORS policies restricted to approved client domains
- Webhook signature verification for Stripe/payment providers
- Audit logging for cashier operations and subscription payments
- Personal data handling compliant with GDPR/CCPA requirements

## Development Workflow

### Specification-Driven Development

All features begin with a complete specification in `/specs/{feature-number}-{feature-name}/`:
- `spec.md` - User stories with acceptance criteria (mandatory testing scenarios)
- `plan.md` - Implementation phases and technical decisions
- `data-model.md` - Database schema and DTOs
- `research.md` - Architectural decisions and alternatives considered
- `contracts/api.openapi.yaml` - OpenAPI 3.1 contract for endpoints
- `quickstart.md` - Developer integration examples

**Constitution Compliance Check**: Every specification must explicitly validate adherence to all five core principles before implementation approval.

### Testing Requirements

- **Test-First Approach**: Acceptance tests written and approved before implementation begins
- **Coverage Targets**: 80%+ for services, 70%+ for controllers, 90%+ for critical paths (economy, game state)
- **Pest Framework**: All tests use Pest syntax with descriptive test names
- **Database Factories**: Every model has a factory for test data generation
- **Integration Tests**: Required for multi-namespace workflows (e.g., floor → game creation)

### Migration Policy

- Migrations are **append-only** (no editing historical migrations)
- Date-prefixed filenames: `YYYY_MM_DD_HHMMSS_description`
- Every foreign key must have explicit `onDelete()` behavior
- Check constraints for business rule enforcement (e.g., `amount > 0`, `tokens >= 0`)
- Comments on complex fields explaining purpose and constraints

### API Versioning

- URL-based versioning: `/v1/`, `/v2/`, etc.
- Breaking changes require new major version with minimum **12 months** deprecation notice
- Deprecation headers returned: `X-API-Deprecated: true`, `X-API-Replacement: /v2/...`
- Legacy endpoints maintained until version N-1 is sunset

## Governance

### Amendment Process

Constitution amendments require:
1. Documented rationale explaining why change is needed
2. Impact analysis on existing features and specifications
3. Template updates (spec-template.md, plan-template.md, tasks-template.md)
4. Version bump following semantic versioning:
   - **MAJOR**: Principle removal or backward-incompatible governance change
   - **MINOR**: New principle added or existing principle materially expanded
   - **PATCH**: Clarifications, wording improvements, non-semantic refinements

### Compliance Review

- All pull requests must include constitution compliance verification
- Feature specifications must map user stories to affected principles
- Code reviews verify adherence to technology standards and architecture patterns
- Complexity introduced must be explicitly justified with documented alternatives considered

### Runtime Guidance

During active development, refer to:
- `.github/copilot-instructions.md` - Auto-generated technology summary
- `/docs/api.md` - Complete API endpoint reference
- `/specs/008-api-structure/research.md` - Architectural decision records

**Version**: 1.0.0 | **Ratified**: 2025-11-20 | **Last Amended**: 2025-11-20
