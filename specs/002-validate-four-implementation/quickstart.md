# Quickstart: Validate Four Implementation

This guide outlines the initial steps to create the necessary files and directories for the "Validate Four" game logic, following the architecture in `docs/logic.md`.

## 1. Update `composer.json`

Ensure the `app/Games/` directory is registered in the autoloader.

```json
// composer.json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "App\\Games\\": "app/Games/",
        "Database\\Factories\\": "database/factories/",
        "Database\\Seeders\\": "database/seeders/"
    }
},
```
Then run: `composer dump-autoload`

## 2. Create Directory Structure

Create the following directories:

```bash
mkdir -p app/Games/ValidateFour/Actions
mkdir -p app/Games/ValidateFour/Modes
```

## 3. Create Core Files

Create the following empty classes and files.

### Global Interface

**File:** `app/Interfaces/GameModeStrategy.php`
```php
<?php

namespace App\Interfaces;

use App\Models\Game\Player;

interface GameModeStrategy
{
    public function validateAction(object $gameState, object $action): bool;
    public function applyAction(object $gameState, object $action): object;
    public function checkEndCondition(object $gameState): ?Player;
}
```

### Game-Specific Files

**File:** `app/Games/ValidateFour/ValidateFourGameState.php`
```php
<?php

namespace App\Games\ValidateFour;

class ValidateFourGameState
{
    // Implementation based on data-model.md
}
```

**File:** `app/Games/ValidateFour/AbstractValidateFourMode.php`
```php
<?php

namespace App\Games\ValidateFour;

use App\Interfaces\GameModeStrategy;

abstract class AbstractValidateFourMode implements GameModeStrategy
{
    // Shared logic for all Validate Four modes will go here.
}
```

**File:** `app/Games/ValidateFour/rules.php`
```php
<?php

return [
    'title' => 'Validate Four',
    'description' => 'Be the first player to connect four of your discs in a row.',
    // Base rules common to all modes
];
```

### Action DTOs

**File:** `app/Games/ValidateFour/Actions/DropDisc.php`
```php
<?php

namespace App\Games\ValidateFour\Actions;

class DropDisc
{
    public readonly int $column;

    public function __construct(array $data)
    {
        // Validation for 'column' will go here.
        $this->column = $data['column'];
    }
}
```

**File:** `app/Games/ValidateFour/Actions/PopOut.php`
```php
<?php

namespace App\Games\ValidateFour\Actions;

class PopOut
{
    public readonly int $column;

    public function __construct(array $data)
    {
        // Validation for 'column' will go here.
        $this->column = $data['column'];
    }
}
```

### Mode Implementations

**File:** `app/Games/ValidateFour/Modes/StandardMode.php`
```php
<?php

namespace App\Games\ValidateFour\Modes;

use App\Games\ValidateFour\AbstractValidateFourMode;

class StandardMode extends AbstractValidateFourMode
{
    // Mode-specific logic will go here.
}
```
*(Repeat for `PopOutMode`, `EightBySevenMode`, `NineBySixMode`, and `FiveMode`)*.
