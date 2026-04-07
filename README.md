# SmStorage Laravel Adapter

Laravel Flysystem adapter for [SmStorage](https://votre-saas.com) — store and serve files via the SmStorage SaaS API.

## Requirements

- PHP ^8.1
- Laravel 10, 11 or 12

## Installation
```bash
composer require youke/sm-storage-laravel
```

The service provider is auto-discovered by Laravel.

## Configuration

**1. Create the configuration file (optional):**
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
        'endpoint'  => env('REALYUUKE_STORAGE_MANAGER_ENDPOINT'),
        'url'       => env('REALYUUKE_STORAGE_MANAGER_URL'),
    ],
],
```

**3. Setup your environment variables in `.env`:**
```env
REALYUUKE_STORAGE_MANAGER_ENDPOINT=https://your-smstorage-instance.com
REALYUUKE_STORAGE_MANAGER_URL=https://your-smstorage-instance.com/uploads
REALYUUKE_STORAGE_MANAGER_API_KEY=your_api_key
REALYUUKE_STORAGE_MANAGER_BUCKET_ID=your_bucket_id
```

## Usage
```php
use Illuminate\Support\Facades\Storage;

// Upload or Store a file
// Note: The adapter currently uses the filename, subdirectories may be flattened depending on your SaaS setup.
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

SSL verification is automatically disabled when `APP_ENV=local` to allow testing with local dev environments using self-signed certificates.

## License

MIT