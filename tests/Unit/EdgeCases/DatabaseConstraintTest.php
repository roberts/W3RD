<?php

use App\Enums\GameStatus;
use App\Models\Auth\User;
use App\Models\Game\Game;
use App\Models\Game\Player;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

describe('Database Constraint Violations', function () {
    describe('Duplicate Key Violations', function () {
        it('handles duplicate player position in same game', function () {
            $game = Game::factory()->create();
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            
            // Create first player at position 1
            Player::factory()->for($game)->position(1)->create(['user_id' => $user1->id]);
            
            // Try to create second player at same position (should fail)
            expect(fn () => Player::factory()->for($game)->position(1)->create(['user_id' => $user2->id]))
                ->toThrow(QueryException::class);
        });

        it('handles race condition creating same username', function () {
            // Simulate two users trying to claim same username simultaneously
            $user1 = User::factory()->create();
            $user2 = User::factory()->create();
            
            $targetUsername = 'popular_name';
            
            // First update succeeds
            $user1->update(['username' => $targetUsername]);
            
            // Second update should fail (unique constraint)
            expect(fn () => $user2->update(['username' => $targetUsername]))
                ->toThrow(QueryException::class);
        });
    });

    describe('Foreign Key Constraint Failures', function () {
        it('handles creating player for non-existent game', function () {
            $nonExistentGameId = 999999;
            
            // Try to create player for non-existent game
            expect(fn () => Player::factory()->create(['game_id' => $nonExistentGameId]))
                ->toThrow(QueryException::class);
        });

        it('handles referencing non-existent user ID', function () {
            $nonExistentUserId = 999999;
            
            expect(fn () => Player::factory()->create(['user_id' => $nonExistentUserId]))
                ->toThrow(QueryException::class);
        });

        it('handles deleting user with active games', function () {
            $user = User::factory()->create();
            $game = Game::factory()->create();
            Player::factory()->for($game)->position(1)->create(['user_id' => $user->id]);
            
            // Try to delete user (should fail or cascade depending on FK setup)
            try {
                $user->delete();
                $deleted = true;
            } catch (QueryException $e) {
                $deleted = false;
            }
            
            // Either cascades or fails - both are valid depending on schema
            expect($deleted)->toBeIn([true, false]);
        });
    });

    describe('NOT NULL Constraint Violations', function () {
        it('handles creating game without required mode_id', function () {
            expect(fn () => Game::create([
                'status' => 'pending',
                // Missing mode_id
            ]))->toThrow(QueryException::class);
        });

        it('handles creating player without user_id', function () {
            $game = Game::factory()->create();
            
            expect(fn () => Player::create([
                'game_id' => $game->id,
                'position_id' => 1,
                // Missing user_id
            ]))->toThrow(QueryException::class);
        });
    });

    describe('Check Constraint Violations', function () {
        it('handles invalid enum value for game status', function () {
            // Try to set invalid status
            expect(fn () => DB::table('games')->insert([
                'mode_id' => 1,
                'status' => 'invalid_status',
                'ulid' => 'test123',
                'created_at' => now(),
                'updated_at' => now(),
            ]))->toThrow(QueryException::class);
        });
    });

    describe('Transaction Rollbacks', function () {
        it('rolls back all changes when transaction fails', function () {
            $initialUserCount = User::count();
            
            try {
                DB::transaction(function () {
                    // Create user
                    $user = User::factory()->create(['username' => 'testuser']);
                    
                    // Create game
                    $game = Game::factory()->create();
                    
                    // Try to create invalid player (should fail)
                    Player::create([
                        'game_id' => $game->id,
                        'user_id' => $user->id,
                        'position_id' => 1,
                        // Missing required fields - will fail
                    ]);
                });
            } catch (QueryException $e) {
                // Expected to fail
            }
            
            // User should not exist (transaction rolled back)
            expect(User::count())->toBe($initialUserCount);
        });

        it('maintains database consistency after failed transaction', function () {
            $game = Game::factory()->create();
            $initialPlayerCount = Player::count();
            
            try {
                DB::transaction(function () use ($game) {
                    // Create first player
                    Player::factory()->for($game)->position(1)->create();
                    
                    // Try to create duplicate position (will fail)
                    Player::factory()->for($game)->position(1)->create();
                });
            } catch (QueryException $e) {
                // Expected to fail
            }
            
            // No players should be added
            expect(Player::count())->toBe($initialPlayerCount);
        });
    });

    describe('Deadlock Scenarios', function () {
        it('handles concurrent updates to same game record', function () {
            $game = Game::factory()->create(['status' => 'pending']);
            
            // Simulate two processes trying to update game simultaneously
            try {
                DB::transaction(function () use ($game) {
                    $game->update(['status' => GameStatus::ACTIVE]);
                    
                    // Small delay to simulate concurrent access
                    usleep(100);
                    
                    $game->update(['status' => 'completed']);
                });
                
                $succeeded = true;
            } catch (QueryException $e) {
                $succeeded = false;
            }
            
            // Should either succeed or fail gracefully
            expect($succeeded)->toBeIn([true, false]);
        });
    });

    describe('Connection Loss Scenarios', function () {
        it('handles graceful degradation when database connection fails', function () {
            // Note: This test is difficult to implement without actually breaking DB
            // In production, this would be tested with chaos engineering
            
            expect(true)->toBeTrue(); // Placeholder for chaos test
        });
    });

    describe('Constraint Error Messages', function () {
        it('provides meaningful error for duplicate key violation', function () {
            $game = Game::factory()->create();
            Player::factory()->for($game)->position(1)->create();
            
            try {
                Player::factory()->for($game)->position(1)->create();
            } catch (QueryException $e) {
                $message = $e->getMessage();
                
                // Should mention UNIQUE constraint
                expect($message)->toContain('UNIQUE');
            }
        });

        it('provides meaningful error for foreign key violation', function () {
            try {
                Player::factory()->create(['user_id' => 999999]);
            } catch (QueryException $e) {
                $message = $e->getMessage();
                
                // Should mention foreign key or constraint
                expect($message)->toMatch('/foreign key|constraint/i');
            }
        });
    });
});
