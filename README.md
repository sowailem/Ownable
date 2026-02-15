# Laravel Ownable Package

A powerful, automated, and API-driven Laravel package to handle ownership relationships between Eloquent models. This package provides a seamless way to manage ownership, track history, and automatically attach ownership data to your API responses without needing to modify your models with traits or interfaces.

## Key Features

- **Hands-off Integration**: No traits or interfaces required on your models.
- **Automatic API Attachment**: Automatically inject ownership information into your JSON responses via middleware.
- **Dynamic Registration**: Register "ownable" models through configuration or a dedicated REST API.
- **Centralized Management**: Manage all ownership operations via the `Owner` facade or service.
- **Ownership History**: Keep track of ownership changes over time.
- **Built-in API Endpoints**: Ready-to-use routes for managing ownership and ownable model registrations.
- **Blade Directive**: Simple `@owns` directive for UI-based authorization checks.

## Requirements

- PHP ^8.3
- Laravel ^12.0

## Installation

1. Require the package via Composer:

```bash
composer require sowailem/ownable
```

2. Run the migrations:

```bash
php artisan migrate
```

## Configuration

The `config/ownable.php` file allows you to customize:

- `owner_model`: The default model class that acts as an owner (e.g., `App\Models\User`).
- `ownable_models`: An array of model classes that can be owned (static registration).
- `routes`: Custom prefix and middleware for the built-in API.
- `automatic_attachment`: Enable/disable automatic ownership injection and customize the response key.

## Usage Guide

### 1. Basic Operations

The `Owner` facade is your primary tool for managing ownership relationships.

#### Giving Ownership
Assign ownership of any model to another. This automatically handles marking previous ownerships for that entity as inactive.

```php
use Sowailem\Ownable\Facades\Owner;

// A User owning a Post
Owner::give($user, $post);

// A Team owning a Project
Owner::give($team, $project);
```

#### Checking Ownership
Quickly verify if a specific owner currently owns an entity.

```php
if (Owner::check($user, $post)) {
    // Authorized
}
```

#### Transferring Ownership
Transfer ownership from one entity to another.

```php
Owner::transfer($oldOwner, $newOwner, $post);
```

#### Retrieving Current Owner
Get the actual model instance of the current owner.

```php
$owner = Owner::currentOwner($post); // Returns User instance, Team instance, etc.
```

#### Removing Ownership
Clear the current ownership without necessarily assigning a new one.

```php
Owner::remove($post);
```

### 2. Automatic API Attachment

This is the most powerful feature of the package. The `AttachOwnershipMiddleware` is automatically registered globally. It recursively scans your JSON responses (from Controllers or API Resources) and injects ownership data for any model registered as "ownable".

#### How it works:
1. It looks for Eloquent models in your `JsonResponse`.
2. It checks if the model class is registered in `config/ownable.php` or via the dynamic API.
3. If matched, it fetches the current owner and appends it to the JSON object.

#### Registration:
Add models to `config/ownable.php`:
```php
'ownable_models' => [
    \App\Models\Post::class,
    \App\Models\Comment::class,
],
```

#### JSON Response Example:
Before:
```json
{
    "id": 1,
    "title": "Hello World"
}
```

After (Automatic):
```json
{
    "id": 1,
    "title": "Hello World",
    "ownership": {
        "id": 45,
        "owner_id": 1,
        "owner_type": "User",
        "ownable_id": 1,
        "ownable_type": "Post",
        "is_current": true,
        "owner": {
            "id": 1,
            "name": "John Doe"
        }
    }
}
```

#### Customization:
You can change the injection key and toggle the feature in `config/ownable.php`:
```php
'automatic_attachment' => [
    'enabled' => true,
    'key' => 'meta_ownership', // Change "ownership" to something else
],
```

### 3. Practical Examples

#### Scenario A: Multi-Level Ownership
You might have `Users` owning `Projects`, and `Projects` owning `Tasks`.

```php
// User owns Project
Owner::give($user, $project);

// Project owns Task
Owner::give($project, $task);

// Check if project owns task
Owner::check($project, $task); // true
```

#### Scenario B: Middleware-based Authorization
Create a custom middleware to protect routes based on ownership:

```php
public function handle($request, $next)
{
    $post = $request->route('post');
    
    if (!Owner::check($request->user(), $post)) {
        abort(403);
    }

    return $next($request);
}
```

#### Scenario C: Dynamic Registration Workflow
Register models as "ownable" on the fly without changing code:

```bash
curl -X POST http://your-app.test/api/ownable/ownable-models \
     -H "Content-Type: application/json" \
     -d '{"name": "Document", "model_class": "App\\Models\\Document", "description": "Client documents"}'
```
Now, all `Document` models returned in APIs will automatically include ownership data.

### 3. Blade Directive

Check ownership directly in your Blade views:

```blade
@owns($user, $post)
    <button>Edit Post</button>
@else
    <span>Read Only</span>
@endowns
```

## API Reference

The package provides several endpoints for managing ownership (prefixed by `api/ownable` by default):

### Ownership Records
- `GET /api/ownable/ownerships`: List and filter ownership history.
- `POST /api/ownable/ownerships/give`: Give ownership of an ownable entity to an owner.
- `POST /api/ownable/ownerships/transfer`: Transfer ownership from one owner to another.
- `POST /api/ownable/ownerships/check`: Check if an owner owns a specific entity.
- `POST /api/ownable/ownerships/remove`: Remove ownership of an entity.
- `POST /api/ownable/ownerships/current`: Get the current owner of an entity.

### Ownable Models (Dynamic Registration)
- `GET /api/ownable/ownable-models`: List models registered for automatic attachment.
- `POST /api/ownable/ownable-models`: Register a new model class as "ownable".
- `GET|PUT|DELETE /api/ownable/ownable-models/{id}`: Manage specific ownable model registrations.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
