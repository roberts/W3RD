<?php

use App\Models\Competitions\Tournament;
use App\Models\Games\Game;
use App\Models\Matchmaking\Lobby;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Game channels - users can subscribe to games they're participating in
Broadcast::channel('games.{gameId}', function ($user, $gameId) {
    $game = Game::find($gameId);

    if (! $game) {
        return false;
    }

    // Check if user is a participant
    return $game->gamePlayers()->where('user_id', $user->id)->exists();
});

// Lobby channels - users can subscribe to lobbies they're in
Broadcast::channel('lobbies.{lobbyId}', function ($user, $lobbyId) {
    $lobby = Lobby::find($lobbyId);

    if (! $lobby) {
        return false;
    }

    // Check if user is in the lobby
    return $lobby->lobbyPlayers()->where('user_id', $user->id)->exists();
});

// Tournament channels - users can subscribe to tournaments they're in
Broadcast::channel('tournaments.{tournamentId}', function ($user, $tournamentId) {
    $tournament = Tournament::find($tournamentId);

    if (! $tournament) {
        return false;
    }

    // Check if user is enrolled
    return $tournament->tournamentEntries()->where('user_id', $user->id)->exists();
});

// Leaderboard channels - public channels anyone can subscribe to
Broadcast::channel('leaderboards.{gameTitle}', function ($user, $gameTitle) {
    // Public channel - any authenticated user can subscribe
    return true;
});
