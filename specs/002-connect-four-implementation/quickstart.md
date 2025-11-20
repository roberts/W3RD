# Quickstart: Connect Four Implementation

This guide outlines the initial steps to create the necessary files and directories for the "Connect Four" game logic, following the architecture in `docs/logic.md`.

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
mkdir -p app/Games/ConnectFour/Actions
mkdir -p app/Games/ConnectFour/Modes
```

## 3. Create Core Files

Create the following empty classes and files.

### Global Interface

**File:** `app/Interfaces/GameTitleContract.php`
```php
<?php

namespace App\Interfaces;

use App\Models\Game\Player;

interface GameTitleContract
{
    public function validateAction(object $gameState, object $action): bool;
    public function applyAction(object $gameState, object $action): object;
    public function checkEndCondition(object $gameState): ?Player;
}
```

### Game-Specific Files

**File:** `app/Games/ConnectFour/ConnectFourGameState.php`
```php
<?php

namespace App\Games\ConnectFour;

class ConnectFourGameState
{
    // Implementation based on data-model.md
}
```

**File:** `app/Games/ConnectFour/AbstractConnectFourMode.php`
```php
<?php

namespace App\Games\ConnectFour;

use App\Interfaces\GameTitleContract;

abstract class AbstractConnectFourMode implements GameTitleContract
{
    // Shared logic for all Connect Four modes will go here.
}
```

**File:** `app/Games/ConnectFour/rules.php`
```php
<?php

return [
    'title' => 'Connect Four',
    'description' => 'Be the first player to connect four of your discs in a row.',
    // Base rules common to all modes
];
```

### Action DTOs

**File:** `app/Games/ConnectFour/Actions/DropDisc.php`
```php
<?php

namespace App\Games\ConnectFour\Actions;

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

**File:** `app/Games/ConnectFour/Actions/PopOut.php`
```php
<?php

namespace App\Games\ConnectFour\Actions;

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

**File:** `app/Games/ConnectFour/Modes/StandardMode.php`
```php
<?php

namespace App\Games\ConnectFour\Modes;

use App\Games\ConnectFour\AbstractConnectFourMode;

class StandardMode extends AbstractConnectFourMode
{
    // Mode-specific logic will go here.
}
```
*(Repeat for `PopOutMode`, `EightBySevenMode`, `NineBySixMode`, and `FiveMode`)*.
