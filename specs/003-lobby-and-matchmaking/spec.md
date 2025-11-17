# Feature Specification: Lobby and Matchmaking System

**Version**: 1.0
**Date**: 2025-11-16
**Status**: Draft

## 1. Overview

This document outlines the requirements for a comprehensive game matchmaking and lobby system. The system will consist of two distinct but complementary components:

1.  **Public Matchmaking Queue**: A high-speed, automated system for quickly finding games against suitable public opponents (human or AI).
2.  **Lobby System**: A flexible, persistent system that allows players to create, schedule, and manage private (invite-only) and public (discoverable) game lobbies.

The goal is to provide players with both a fast, seamless way to jump into a game and a powerful, socially-driven way to organize games with friends or the wider community.

## 2. User Scenarios & Testing

### Scenario 1: Player Joins Public Matchmaking

*   **Actor**: A player (Player A).
*   **Preconditions**: Player A is logged in and at the main menu.
*   **Flow**:
    1.  Player A selects a game title and chooses "Find Public Match."
    2.  The system adds them to the public matchmaking queue.
    3.  After a short wait, the system finds a suitable opponent (Player B).
    4.  Both players receive an "Accept Game" prompt with a 10-second timer.
    5.  Both players click "Accept."
    6.  The game begins, and both players are navigated to the game screen.

### Scenario 2: Player Declines a Public Match

*   **Actor**: A player (Player A).
*   **Preconditions**: Player A has been found a match and is presented with the "Accept Game" prompt.
*   **Flow**:
    1.  Player A clicks "Decline" (or the timer expires).
    2.  Player A is removed from the queue and receives a temporary matchmaking cooldown penalty.
    3.  The other player (Player B) is immediately placed back at the front of the queue to find a new opponent.

### Scenario 3: Host Creates a Private Lobby

*   **Actor**: A player (The Host).
*   **Preconditions**: The Host is logged in.
*   **Flow**:
    1.  The Host navigates to the "Create Lobby" screen.
    2.  They select a game title and choose "Private Lobby."
    3.  They invite one or more friends by username.
    4.  They send the invitations.
    5.  The lobby is created, and the Host waits for invitees to respond.

### Scenario 4: Invitee Joins a Private Lobby

*   **Actor**: A player (The Invitee).
*   **Preconditions**: The Invitee has received a lobby invitation.
*   **Flow**:
    1.  The Invitee receives a notification about the game invitation.
    2.  They view the invitation details (who hosted, what game).
    3.  They click "Accept."
    4.  The system marks them as "Accepted" in the lobby.
    5.  Once all players accept, the game automatically begins.

### Scenario 5: Host Creates and Schedules a Public Lobby

*   **Actor**: A player (The Host).
*   **Preconditions**: The Host is logged in.
*   **Flow**:
    1.  The Host navigates to the "Create Lobby" screen.
    2.  They select a game title, choose "Public Lobby," and set a minimum player count of 4.
    3.  They set the `scheduled_at` time for 8:00 PM the next day.
    4.  The lobby is created and appears in the public Lobby Browser.
    5.  Other players join the lobby throughout the day.
    6.  At 8:00 PM, the scheduled job checks the lobby, sees that at least 4 players have accepted, and automatically starts the game.

## 3. Functional Requirements

### FR1: Public Matchmaking Queue
*   **FR1.1**: Players must be able to join a game-specific queue for public matchmaking.
*   **FR1.2**: The system must match players based on a skill level metric.
*   **FR1.3**: The system must prevent a player from being matched against the same opponent repeatedly.
*   **FR1.4**: If a human opponent is not found within 30 seconds, the system must attempt to match the player with a suitable AI agent.

### FR2: Match Confirmation
*   **FR2.1**: When a match is found, both players must be prompted to accept or decline.
*   **FR2.2**: The confirmation prompt must have a 10-second countdown timer.
*   **FR2.3**: If both players accept, the game must start immediately.
*   **FR2.4**: If one player declines or times out, they shall be removed from the queue. The other player shall be prioritized for the next available match.

### FR3: Queue Dodge Penalty
*   **FR3.1**: A player who declines a match or fails to accept in time must receive a temporary cooldown, preventing them from joining the queue again for a short period.
*   **FR3.2**: The cooldown duration will escalate with repeat offenses. The penalty structure is: 1st offense: 30 seconds; 2nd offense: 2 minutes; 3rd and subsequent offenses: 5 minutes. The offense level will reset a few hours after the last offense.

### FR4: Lobby Creation & Management
*   **FR4.1**: Players (Hosts) must be able to create lobbies.
*   **FR4.2**: The Host must be able to set the lobby as either `private` (invite-only) or `public` (discoverable).
*   **FR4.3**: The Host must be able to set a minimum number of players required to start the game.
*   **FR4.4**: The Host must be able to schedule a game for a future date and time.
*   **FR4.5**: The Host must be able to kick a player from the lobby before the game starts.

### FR5: Lobby Participation
*   **FR5.1**: For private lobbies, the Host must be able to invite players by their username.
*   **FR5.2**: Invited players must receive a notification and be able to accept or decline.
*   **FR5.3**: For public lobbies, any player must be able to view a list of available lobbies and join them.
*   **FR5.4**: A game starts when all invited players (for private) or the minimum number of players (for public) have accepted. For scheduled games, this check occurs at the scheduled time.

### FR6: Pre-Game Readiness
*   **FR6.1**: The Host must be able to initiate a "Ready Check" that requires all accepted players to confirm they are present before the game can be launched.

## 4. Non-Functional Requirements

*   **NFR1 (Performance)**: The public matchmaking system should find a match for 95% of players within 60 seconds (including the 30-second AI fallback window).
*   **NFR2 (Scalability)**: The system must support up to 10,000 concurrent users in lobbies and matchmaking queues.
*   **NFR3 (Usability)**: The process of creating or joining a game (both public and private) should be intuitive and require minimal steps.

## 5. Key Entities

*   **Lobby**: Represents a pre-game gathering of players.
    *   Attributes: Game Title, Host, Public/Private status, Minimum Players, Scheduled Time, Status.
*   **Lobby Player**: Represents a player's status within a lobby.
    *   Attributes: Player, Lobby, Acceptance Status.
*   **Matchmaking Queue**: An in-memory (non-database) representation of players waiting for a public match.
*   **Matchmaking Confirmation**: A temporary, in-memory object representing a proposed match pending confirmation from both players.

## 6. Assumptions

*   A skill-ranking system already exists and provides a "level" for each player per game title.
*   A system for finding and allocating AI agents (`SchedulingService`) is available.
*   A real-time notification service (e.g., WebSockets via Reverb) is in place for dispatching events to clients.

## 7. Out of Scope

*   Tournament bracket generation and management.
*   In-game chat functionality (Lobby chat is separate).
*   Player-to-player direct messaging or friend systems (though the lobby relies on a way to identify users).
*   Game-specific rule customization beyond the game modes already defined.
