# Implementation Plan: Lobby and Matchmaking System

**Branch**: `003-lobby-and-matchmaking` | **Date**: 2025-11-16 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-lobby-and-matchmaking/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This plan details the implementation of a comprehensive matchmaking and lobby system. It includes a high-speed public "Quickplay" queue using Redis for automated matchmaking with an AI fallback, and a persistent, database-driven lobby system for private (invite-only) and public (discoverable) games. The lobby system supports scheduling, minimum player counts, and host management features.

## Technical Context

**Language/Version**: PHP 8.3
**Primary Dependencies**: Laravel 12, Livewire, Redis, Pest
**Storage**: MySQL/PostgreSQL (for persistent lobbies), Redis (for queues, confirmations, cooldowns)
**Testing**: Pest (Unit, Feature), PHPUnit
**Target Platform**: Linux Web Server
**Project Type**: Web Application
**Performance Goals**: Find public match for 95% of players within 60s; Support 10,000 concurrent users.
**Constraints**: Must use Laravel Reverb for real-time notifications.
**Scale/Scope**: 10,000 concurrent users across both Quickplay and lobby systems.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

This project does not have a `.specify/memory/constitution.md` file with defined principles. All gates pass by default.

## Project Structure

### Documentation (this feature)

```text
specs/003-lobby-and-matchmaking/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)
```text
# Web application structure
app/
├── Enums/
│   ├── LobbyStatus.php
│   └── LobbyPlayerStatus.php
├── Events/
│   ├── GameFound.php
│   ├── LobbyInvitation.php
│   └── LobbyReadyCheck.php
├── Http/
│   └── Controllers/
│       └── Api/
│           └── V1/
│               ├── QuickplayController.php
│               ├── LobbyController.php
│               └── LobbyPlayerController.php
├── Jobs/
│   ├── ProcessQuickplayQueue.php
│   └── ProcessScheduledLobbies.php
├── Models/
│   ├── Lobby.php
│   └── LobbyPlayer.php
└── Services/
    └── SchedulingService.php # (Existing, to be modified)

database/
├── factories/
│   ├── LobbyFactory.php
│   └── LobbyPlayerFactory.php
└── migrations/
    ├── ####_##_##_######_create_lobbies_table.php
    └── ####_##_##_######_create_lobby_players_table.php

routes/
└── api.php # (Existing, to be modified)

tests/
├── Feature/
│   ├── LobbyTest.php
│   └── QuickplayTest.php
└── Unit/
    ├── LobbyTest.php
    └── QuickplayTest.php
```

**Structure Decision**: The feature will be integrated into the existing Laravel web application structure. New models, controllers, jobs, and events will be created in their respective `app/` subdirectories. Database migrations and factories will be added, and tests will be placed in `tests/Feature` and `tests/Unit`.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| N/A       | N/A        | N/A                                 |
