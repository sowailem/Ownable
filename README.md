# Laravel Ownable Package

A Laravel package to handle ownership relationships between Eloquent models. This package provides a simple and flexible way to manage ownership of any model by any other model in your Laravel application.

## Features

- **Flexible Ownership**: Any model can own any other model
- **Ownership Transfer**: Transfer ownership between different owners
- **Ownership History**: Keep track of ownership changes over time
- **Current Owner**: Easily retrieve the current owner of any ownable item
- **Bulk Operations**: Check ownership status and manage multiple ownables
- **Automatic Cleanup**: Automatically clean up ownership records when models are deleted
- **Facade Support**: Use the convenient Owner facade for ownership operations

## Requirements

- PHP ^8.0
- Laravel ^9.0, ^10.0, ^11.0 or ^12.0

## Installation

Require the package via Composer:

```bash
composer require sowailem/ownable
```

Publish the migration:

```bash
php artisan vendor:publish --provider="Sowailem\Ownable\OwnableServiceProvider" --tag="ownable-migrations"
```

Run the migration:

```bash
php artisan migrate
```

## Usage Examples

### 1. Setting up models

```php
use Sowailem\Ownable\Traits\HasOwnables;
use Sowailem\Ownable\Traits\IsOwnable;
use Sowailem\Ownable\Contracts\Ownable as OwnableContract;
use Sowailem\Ownable\Contracts\Owner as OwnerContract;

// Owner model (e.g., User)
class User extends Authenticatable implements OwnerContract
{
    use HasOwnables;
}

// Ownable model
class Post extends Model implements OwnableContract
{
    use IsOwnable;
}
```

### 2. Using the package

#### Giving Ownership

```php
$user = User::first();
$post = Post::first();

// Give ownership
$user->giveOwnershipTo($post);
// Or
$post->ownedBy($user);
// Or using facade
Owner::give($user, $post);
```

#### Checking Ownership

```php
// Check ownership
if ($user->owns($post)) {
    // User owns this post
}
// Or
if ($post->isOwnedBy($user)) {
    // Post is owned by this user
}
// Or using facade
if (Owner::check($user, $post)) {
    // Check ownership using facade
}
```

#### Transferring Ownership

```php
// Transfer ownership
$newOwner = User::find(2);
$user->transferOwnership($post, $newOwner);
// Or
$post->transferOwnershipTo($newOwner);
// Or using facade
Owner::transfer($user, $newOwner, $post);
```

#### Retrieving Owned Items and Owners

```php
// Get all owned items
$user->ownables()->get();

// Get current owner of an item
$currentOwner = $post->currentOwner();

// Get all owners (including historical)
$allOwners = $post->owners()->get();

// Get only current owners
$currentOwners = $post->owners()->wherePivot('is_current', true)->get();
```

#### Taking Ownership Away

```php
// Remove ownership
$user->takeOwnershipFrom($post);
```

## API Reference

### HasOwnables Trait

Methods available on owner models:

- `possessions()` - Relationship to ownership records
- `ownables()` - Relationship to owned items
- `owns($ownable)` - Check if owns a specific item
- `giveOwnershipTo($ownable)` - Give ownership of an item
- `takeOwnershipFrom($ownable)` - Remove ownership of an item
- `transferOwnership($ownable, $newOwner)` - Transfer ownership to another owner

### IsOwnable Trait

Methods available on ownable models:

- `ownerships()` - Relationship to ownership records
- `owners()` - Relationship to owners
- `currentOwner()` - Get the current owner
- `ownedBy($owner)` - Set ownership to a specific owner
- `isOwnedBy($owner)` - Check if owned by a specific owner
- `transferOwnershipTo($newOwner)` - Transfer ownership to a new owner

### Owner Facade

Static methods available via the Owner facade:

- `Owner::give($owner, $ownable)` - Give ownership
- `Owner::check($owner, $ownable)` - Check ownership
- `Owner::transfer($fromOwner, $toOwner, $ownable)` - Transfer ownership

## Configuration

You can publish the configuration file to customize the package behavior:

```bash
php artisan vendor:publish --provider="Sowailem\Ownable\OwnableServiceProvider" --tag="ownable-config"
```

The configuration allows you to customize:
- Default owner model class
- Default ownable model class
- Database table name

## Database Structure

The package creates an `ownerships` table with the following structure:

- `id` - Primary key
- `owner_id` - ID of the owner model
- `owner_type` - Class name of the owner model
- `ownable_id` - ID of the ownable model
- `ownable_type` - Class name of the ownable model
- `is_current` - Boolean flag indicating if this is the current ownership
- `created_at` - Timestamp when ownership was created
- `updated_at` - Timestamp when ownership was last updated

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email abdullah.sowailem@email.com instead of using the issue tracker.


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
