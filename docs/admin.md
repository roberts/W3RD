# 🛠️ Admin Panel Architecture (Filament v4)

This document provides a detailed plan for building a comprehensive administration panel for the GamerProtocol.io API using **Filament v4**.

---

## Why Filament v4?

Filament is a modern, full-stack admin panel framework for Laravel that allows for incredibly rapid development.

*   **Speed:** Build complex CRUD interfaces, dashboards, and data-driven widgets in minutes.
*   **Modern UI:** The UI is clean, responsive, and built with Tailwind CSS, offering a premium user experience out of the box.
*   **Integrated:** It deeply integrates with Laravel's core features (Eloquent, Policies, Gates).
*   **Extensible:** It's easy to add custom pages, actions, and components.

---

## Core Features to Implement

### 1. Installation & Setup

1.  **Install Filament:**
    ```bash
    composer require filament/filament:"^4.0-stable" -W
    php artisan filament:install --panels
    ```
2.  **Create an Admin User:** Create a user and ensure they can access the panel. In a `User` model, you can implement the `FilamentUser` contract:
    ```php
    use Filament\Models\Contracts\FilamentUser;

    class User extends Authenticatable implements FilamentUser
    {
        // ...
        public function canAccessPanel(Panel $panel): bool
        {
            return $this->is_admin; // Or check for a specific role
        }
    }
    ```

### 2. Dashboard

The main admin dashboard should provide a high-level overview of the platform's health.

*   **Implementation:** Use Filament Widgets (`php artisan make:filament-widget StatsOverview --stats-overview`).
*   **Widgets to Create:**
    *   **Stats Overview:** Key metrics like Daily Active Users (DAU), Monthly Active Users (MAU), Total Revenue (This Month), and New Signups (Today).
    *   **Recent Matches Chart:** A chart showing the number of matches played per day over the last 30 days.
    *   **Recent Users Table:** A table widget showing the latest 10 users who signed up.

### 3. Resource Management

Resources are the heart of Filament, providing full CRUD functionality for your models.

*   **User Management (`UserResource`)**
    *   **Command:** `php artisan make:filament-resource UserResource`
    *   **Table Columns:** `id`, `name`, `email`, `stripe_id`, `deactivated_at` (as a toggleable icon).
    *   **Filters:** Add filters for "Subscribed," "Not Subscribed," and "Deactivated."
    *   **Actions:**
        *   **Impersonate:** Add an action to securely log in as a user to debug issues.
        *   **Grant Subscription:** A custom action that manually creates a subscription for a user.
        *   **Deactivate/Reactivate:** An action to toggle the `deactivated_at` timestamp.

*   **Game & Match Management**
    *   **`GameResource`:** Simple CRUD for managing the available game blueprints (`slug`, `name`, `max_players`).
    *   **`MatchResource`:**
        *   **Table:** List all matches, showing `ulid`, `title_slug`, players, `status`, and `winner`.
        *   **Custom View:** On the "View" page for a match, create a custom component to render the `game_state` JSON in a readable format (e.g., a visual representation of the board).
        *   **Actions:** Add an action to "Manually Resolve" a stuck match by setting a winner.

*   **Content Management**
    *   **`AvatarResource`:** CRUD for managing avatars. Use the `FileUpload` component to handle image uploads directly to your storage.
    *   **`AgentResource`:** CRUD for managing AI and local player profiles.

*   **Billing Management**
    *   **`SubscriptionResource`:**
        *   **Source:** This could be built on the `Laravel\Cashier\Subscription` model.
        *   **Table:** List all subscriptions, showing the user, plan name, status (active, canceled), and next renewal date.
        *   **Actions:** Add a "Cancel Subscription" action that calls the appropriate Cashier method.
    *   **`ProductResource` (for Store):** CRUD for managing the cosmetic items available for purchase.

*   **Platform Integrity**
    *   **`ClientResource`:** A critical but simple CRUD for managing the `clients` table. This allows admins to issue, view, and revoke the `X-Client-Key` for different frontend applications.

---

## Example Resource (UserResource)

This gives a taste of how simple the code is.

```php
// app/Filament/Resources/UserResource.php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('email')->email()->required(),
                Forms\Components\DateTimePicker::make('deactivated_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\IconColumn::make('deactivated_at')->boolean()->label('Is Active'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                // ... filters
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Action::make('deactivate')
                    ->action(fn (User $record) => $record->update(['deactivated_at' => now()]))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle'),
            ])
            ->bulkActions([
                // ... bulk actions
            ]);
    }
}
```
