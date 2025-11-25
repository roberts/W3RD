# Game Engine Lifecycle

This directory contains all game lifecycle management components, organized into three distinct phases:

## Structure

```
Lifecycle/
├── Creation/          # Game initialization and setup
├── Progression/       # Active gameplay mechanics
└── Conclusion/        # Game ending and outcome processing
```

## Creation Phase

Handles game initialization and setup before gameplay begins.

### GameBuilder
- Creates games from lobbies or queue matches
- Sets up initial player positions and colors
- Manages game creation transactions

### InitialStateFactory
- Generates initial game states for different game titles
- Validates player counts against game requirements
- Serializes states for database storage

## Progression Phase

Manages active gameplay, turn progression, and player coordination.

### TurnAdvancer
- Advances turns for sequential games
- Handles phase transitions for phase-based games
- Manages simultaneous and interleaved turn systems

### PhaseTransitioner
- Manages phase transitions (e.g., Hearts passing → playing)
- Checks phase completion conditions
- Delegates to game-specific phase logic

### CoordinatedActionProcessor
- Handles actions requiring multiple players (e.g., Hearts card passing)
- Tracks coordination groups and sequences
- Processes batched coordinated actions when complete
- Supports custom coordination requirements per action type

## Conclusion Phase

Handles game ending, outcome determination, and reward processing.

### ConclusionManager
- Determines when games have ended
- Processes different win conditions (elimination, rule-based)
- Updates game status and winner information
- Broadcasts game completion events

### OutcomeEvaluator
- Evaluates final game outcomes
- Determines winners and rankings
- Calculates game statistics
- Checks end conditions via game arbiters

### OutcomeProcessor
- Calculates and awards XP and rewards
- Manages progression system integration
- Stores final scores and statistics
- Provides player statistics for completed games

### RankingCalculator
- Calculates final rankings for multiplayer games
- Handles tie-breaking logic
- Supports custom comparison functions
- Determines winners from rankings
