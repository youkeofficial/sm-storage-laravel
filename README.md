# SmStorage Laravel Adapter

Laravel Flysystem adapter for [SmStorage](https://storage.realyuuke.tech) — store and serve files via the SmStorage SaaS API.

## Requirements

- PHP ^8.2
- Laravel 10, 11, 12 or 13

## Installation

Add the repository to your project's `composer.json`:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/youkeofficial/sm-storage-laravel"
    }
],
```

Then install the package:

```bash
composer require youkeofficial/sm-storage-laravel:dev-main
```

## Configuration

**1. Create the configuration file:**
```bash
php artisan vendor:publish --tag=sm-storage-config
```

**2. Add the disk to `config/filesystems.php`:**
```php
'disks' => [
    'sm' => [
        'driver'    => 'sm',
        'key'       => env('REALYUUKE_STORAGE_MANAGER_API_KEY'),
        'bucket_id' => env('REALYUUKE_STORAGE_MANAGER_BUCKET_ID'),
        'endpoint'  => env('REALYUUKE_STORAGE_MANAGER_ENDPOINT', 'https://storage.realyuuke.tech'),
        'url'       => env('REALYUUKE_STORAGE_MANAGER_URL', 'https://storage.realyuuke.tech/uploads'),
    ],
],
```

**3. Setup your environment variables in `.env`:**
```env
REALYUUKE_STORAGE_MANAGER_API_KEY=your_secret_api_key
REALYUUKE_STORAGE_MANAGER_BUCKET_ID=your_bucket_uuid
```

## Usage
```php
use Illuminate\Support\Facades\Storage;

// Upload a file
$path = $request->file('avatar')->store('avatars', 'sm');

// Get the public URL
$url = Storage::disk('sm')->url($path);

// File existence
if (Storage::disk('sm')->exists($path)) {
    // ...
}

// Delete a file
Storage::disk('sm')->delete($path);
```

## Local Development (SSL)

SSL verification is automatically disabled when `APP_ENV=local` to allow testing with local dev environments.

## License

MIT

