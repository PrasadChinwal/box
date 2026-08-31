# UIS ITS Box

A Laravel wrapper for the [Box Platform API](https://developer.box.com).

## Installation

```bash
composer require prasadchinwal/box
php artisan vendor:publish --tag=box-config
```

Configure credentials in `config/box.php` or via environment variables. See the [Box authentication guides](https://developer.box.com/guides/authentication/select/) for setup details.

### Configuration

| Variable | Description |
| --- | --- |
| `BOX_AUTH_METHOD` | `app_token` (JWT) or `client_credentials` |
| `BOX_CLIENT_ID` | Box application client ID |
| `BOX_CLIENT_SECRET` | Box application client secret |
| `BOX_ENTERPRISE_ID` | Enterprise ID for JWT / client credentials auth |
| `BOX_KEY_ID` | Public key ID for JWT auth |
| `BOX_KEY_PASSWORD` | Passphrase for `private_key.pem` |
| `BOX_FOLDER_ID` | Default root folder ID for API calls (defaults to `0`) |
| `BOX_TOKEN_CACHE_KEY` | Cache key for access tokens |
| `BOX_TOKEN_EXPIRY_BUFFER` | Seconds before expiry to refresh a cached token |
| `BOX_REQUEST_TIMEOUT` | HTTP request timeout in seconds |
| `BOX_REQUEST_RETRY_TIMES` | Number of HTTP retries |
| `BOX_REQUEST_RETRY_SLEEP` | Delay between retries in milliseconds |

Access tokens are cached automatically and reused until they are close to expiring.

---

## Quick start

All Box API calls use the `Box` facade:

```php
use PrasadChinwal\Box\Facades\Box;

$file = Box::file()->whereId('1234')->info();
$folder = Box::folder()->whereId('4321')->info();
$user = Box::user()->get();
```

For Laravel filesystem integration, use the `Storage` facade (see [Laravel Filesystem](#laravel-filesystem) below).

### Path conventions

When using the `box` storage disk, paths are relative to the configured `folder_id`:

- `report.pdf` — file in the root folder
- `AppsFiles/report.pdf` — file inside the `AppsFiles` subfolder

The **All Files** label in the Box web UI is the root folder (`0`), not a folder named `AllFiles`. Use the actual folder name from Box (for example `AppsFiles`).

---

## Laravel Filesystem

This package registers a `box` storage driver via `Storage::extend()`.

### Disk configuration

Add a disk to `config/filesystems.php`:

```php
'disks' => [
    'box' => [
        'driver' => 'box',
        'folder_id' => env('BOX_FOLDER_ID', '0'),
    ],
],
```

The disk `folder_id` overrides `config('box.folder_id')` for that disk.

### Root path

The Box driver does not use a Laravel-style `root` path. Instead, set where Storage paths begin with `folder_id` and/or `prefix`:

| Option | Purpose |
| --- | --- |
| `folder_id` | Box folder ID that acts as the disk root. All paths are relative to this folder. |
| `prefix` | Optional subfolder path prepended to every Storage path within `folder_id`. |

**Option 1 — root at a Box folder ID**

Point `folder_id` at the folder you want as root (find the ID in the Box web UI or API). Paths no longer need that folder name in them:

```php
'disks' => [
    'box' => [
        'driver' => 'box',
        'folder_id' => env('BOX_APPS_FOLDER_ID'), // e.g. the AppsFiles folder ID
    ],
],

// Resolves to report.pdf inside that folder
Storage::disk('box')->get('report.pdf');
```

**Option 2 — account root with a path prefix**

Keep `folder_id` at `0` (or your default root) and set `prefix` to a subfolder name:

```php
'disks' => [
    'box' => [
        'driver' => 'box',
        'folder_id' => env('BOX_FOLDER_ID', '0'),
        'prefix' => 'AppsFiles',
    ],
],

// Resolves to AppsFiles/report.pdf under folder 0
Storage::disk('box')->get('report.pdf');
```

Use one approach or the other. If you already set `folder_id` to the target folder, you usually do not need `prefix`.

### Storage facade usage

```php
use Illuminate\Support\Facades\Storage;

$disk = Storage::disk('box');

// Read and write
$disk->put('AppsFiles/report.pdf', $contents);
$contents = $disk->get('AppsFiles/report.pdf');

// Stream a browser download (return from a route)
return $disk->download('AppsFiles/report.pdf');
return $disk->download('AppsFiles/report.pdf', 'custom-name.pdf');

// Existence checks
$disk->exists('AppsFiles/report.pdf');
$disk->missing('AppsFiles/report.pdf');
$disk->directoryExists('AppsFiles');

// List contents
$disk->files('AppsFiles');           // files in a directory
$disk->directories('AppsFiles');     // subdirectories
$disk->allFiles();                   // all files recursively
$disk->allDirectories();             // all directories recursively

// Copy, move, delete
$disk->copy('old.txt', 'AppsFiles/new.txt');
$disk->move('old.txt', 'AppsFiles/new.txt');
$disk->delete('AppsFiles/report.pdf');
$disk->deleteDirectory('AppsFiles/archive');

// Create a directory
$disk->makeDirectory('AppsFiles/new-folder');

// Metadata
$disk->size('AppsFiles/report.pdf');
$disk->mimeType('AppsFiles/report.pdf');
$disk->lastModified('AppsFiles/report.pdf');
```

File downloads through Storage follow the Box API flow internally: resolve the file by path, obtain a redirect URL from `/files/{id}/content`, then stream the bytes to the client.

---

## File API

```php
use PrasadChinwal\Box\Facades\Box;

$file = Box::file();
```

Chain `whereId()` before most operations, or `inFolder()` to scope searches and uploads.

### Get file info

[Documentation](https://developer.box.com/reference/get-files-id/)

```php
Box::file()->whereId('1234')->info();
```

### Search for a file by name

[Documentation](https://developer.box.com/reference/get-search/)

```php
Box::file()->search('report.pdf');

// Limit search to a folder subtree
Box::file()->inFolder('4321')->search('report.pdf');
```

### Read file contents

[Documentation](https://developer.box.com/reference/get-files-id-content/)

Returns the file body as a string. Uses the Box redirect download URL internally.

```php
$contents = Box::file()->whereId('1234')->contents();
```

### Get a temporary download URL

[Documentation](https://developer.box.com/reference/get-files-id-content/)

```php
$url = Box::file()->whereId('1234')->getDownloadUrl();
```

### Download a file to the browser

[Documentation](https://developer.box.com/reference/get-files-id-content/)

Downloads the file to `storage/app/{filename}` and returns a `BinaryFileResponse`. Use when you know the Box file ID.

```php
return Box::file()->whereId('1234')->downloadFile();
```

### Upload a file from disk

[Documentation](https://developer.box.com/reference/post-files-content/)

For files over 50 MB, use the [Chunk Upload APIs](https://developer.box.com/guides/uploads/chunked/).

```php
$attributes = [
    'attributes' => json_encode([
        'name' => 'New_Test.pdf',
        'parent' => ['id' => '4321'],
    ]),
];

Box::file()->create(
    filepath: storage_path('app/file.pdf'),
    filename: 'My_New_File.pdf',
    attributes: $attributes,
);
```

### Write file contents

[Documentation](https://developer.box.com/reference/post-files-content/)

```php
Box::file()
    ->inFolder('4321')
    ->write(filepath: 'report.pdf', contents: 'Hello, Box!');
```

### Copy a file

[Documentation](https://developer.box.com/reference/post-files-id-copy/)

```php
Box::file()->whereId('1234')->copy([
    'name' => 'TestFile.pdf',
    'parent' => ['id' => '4321'],
]);
```

### Update a file

[Documentation](https://developer.box.com/reference/put-files-id/)

```php
Box::file()->whereId('1234')->update([
    'name' => 'Renamed.pdf',
    'description' => 'Updated description.',
    'parent' => ['id' => '5678'],
]);
```

### Delete a file

[Documentation](https://developer.box.com/reference/delete-files-id/)

```php
Box::file()->whereId('1234')->delete();
```

### Shared links

[Create shared link](https://developer.box.com/reference/put-files-id--add-shared-link/)

```php
Box::file()->whereId('1234')->createSharedLink([
    'shared_link' => [
        'access' => 'company',
        'permissions' => [
            'can_download' => true,
            'can_edit' => true,
        ],
    ],
]);
```

[Get shared link](https://developer.box.com/reference/get-files-id--get-shared-link/)

```php
Box::file()->whereId('1234')->getSharedLink();
```

[Remove shared link](https://developer.box.com/reference/delete-files-id--remove-shared-link/)

```php
Box::file()->whereId('1234')->removeSharedLink();
```

[Find file from shared link](https://developer.box.com/reference/get-shared-items/)

```php
Box::file()->whereLink('https://company.box.com/s/abc123')->find();
```

### Thumbnail

[Documentation](https://developer.box.com/reference/get-files-id-thumbnail-id/)

```php
Box::file()->whereId('1234')->thumbnail(extension: '.jpg');
```

### File versions

[Documentation](https://developer.box.com/reference/get-files-id-versions/)

```php
Box::file()->whereId('1234')->versions();
```

### Watermark

[Get watermark](https://developer.box.com/reference/get-files-id-watermark/)

```php
Box::file()->whereId('1234')->getWatermark();
```

[Create watermark](https://developer.box.com/reference/put-files-id-watermark/)

```php
Box::file()->whereId('1234')->createWatermark();
```

[Remove watermark](https://developer.box.com/reference/delete-files-id-watermark/)

```php
Box::file()->whereId('1234')->removeWatermark();
```

### Collaborations on a file

[Documentation](https://developer.box.com/reference/get-files-id-collaborations/)

```php
Box::file()->whereId('1234')->getCollaboration();
```

---

## Folder API

```php
use PrasadChinwal\Box\Facades\Box;

$folder = Box::folder();
```

Chain `whereId()` with the folder ID before most operations. Use `'0'` for the root folder.

### Get folder info

[Documentation](https://developer.box.com/reference/get-folders-id/)

```php
Box::folder()->whereId('4321')->info();
```

### List folder items

[Documentation](https://developer.box.com/reference/get-folders-id-items/)

```php
// Single page (default limit 1000)
Box::folder()->whereId('4321')->items();

// Paginated
Box::folder()->whereId('4321')->items(limit: 100, offset: 0);

// All entries across pages
Box::folder()->whereId('4321')->allEntries();
```

### Create a folder

[Documentation](https://developer.box.com/reference/post-folders/)

```php
Box::folder()->create([
    'name' => 'New Folder',
    'parent' => ['id' => '4321'],
]);
```

### Copy a folder

[Documentation](https://developer.box.com/reference/post-folders-id-copy/)

```php
Box::folder()->whereId('4321')->copy([
    'name' => 'Copied Folder',
    'parent' => ['id' => '5678'],
]);
```

### Update a folder

[Documentation](https://developer.box.com/reference/put-folders-id/)

```php
Box::folder()->whereId('4321')->update([
    'name' => 'Renamed Folder',
    'description' => 'Updated description.',
]);
```

### Delete a folder

[Documentation](https://developer.box.com/reference/delete-folders-id/)

```php
Box::folder()->whereId('4321')->delete(recursive: true);
```

### Shared links

[Create shared link](https://developer.box.com/reference/put-folders-id--add-shared-link/)

```php
Box::folder()->whereId('4321')->createSharedLink([
    'shared_link' => [
        'access' => 'company',
        'permissions' => ['can_download' => true],
    ],
]);
```

[Get shared link](https://developer.box.com/reference/get-folders-id--get-shared-link/)

```php
Box::folder()->whereId('4321')->getSharedLink();
```

[Find folder from shared link](https://developer.box.com/reference/get-shared-items/)

```php
Box::folder()->whereLink('https://company.box.com/s/abc123')->find();
```

### Folder locks

[Get locks](https://developer.box.com/reference/get-folder-locks/)

```php
Box::folder()->whereId('4321')->getLocks();
```

[Create lock](https://developer.box.com/reference/post-folder-locks/)

```php
Box::folder()->lock([
    'folder' => ['type' => 'folder', 'id' => '4321'],
    'locked_operations' => ['move' => true, 'delete' => true],
]);
```

[Remove lock](https://developer.box.com/reference/delete-folder-locks-id/)

```php
Box::folder()->unlock(lockid: '0983');
```

### Collaborations on a folder

[Documentation](https://developer.box.com/reference/get-folders-id-collaborations/)

```php
Box::folder()->whereId('4321')->getCollaboration();
```

---

## Collaboration API

```php
use PrasadChinwal\Box\Facades\Box;

$collaboration = Box::collaboration();
```

### Get a collaboration

[Documentation](https://developer.box.com/reference/get-collaborations-id/)

```php
Box::collaboration()->whereId('45678')->get();
```

### Create a collaboration

[Documentation](https://developer.box.com/reference/post-collaborations/)

```php
Box::collaboration()->create([
    'item' => ['type' => 'folder', 'id' => '4321'],
    'accessible_by' => ['type' => 'user', 'login' => 'user@example.com'],
    'role' => 'editor',
]);
```

### Update a collaboration

[Documentation](https://developer.box.com/reference/put-collaborations-id/)

```php
Box::collaboration()->whereId('1111')->update([
    'status' => 'accepted',
    'role' => 'editor',
]);
```

### Delete a collaboration

[Documentation](https://developer.box.com/reference/delete-collaborations-id/)

```php
Box::collaboration()->whereId('1111')->delete();
```

---

## User API

```php
use PrasadChinwal\Box\Facades\Box;

$user = Box::user();
```

### Get the current user

[Documentation](https://developer.box.com/reference/get-users-me/)

```php
Box::user()->get();
```

### List all users

[Documentation](https://developer.box.com/reference/get-users/)

```php
Box::user()->all();
```

### Get a user by ID

[Documentation](https://developer.box.com/reference/get-users-id/)

```php
Box::user()->whereId('3477983675')->first();
```

### Find users by email

[Documentation](https://developer.box.com/reference/get-users/)

```php
Box::user()->findByEmail('user@example.com');
```

### Get group memberships

[Documentation](https://developer.box.com/reference/get-users-id-memberships/)

```php
Box::user()->whereId('3477983675')->memberships();
```

### Delete a user

[Documentation](https://developer.box.com/reference/delete-users-id/)

```php
Box::user()->whereId('3477983675')->delete(force: false, notify: false);
```

### Transfer a user's root folder

Transfers the contents of a user's root folder to another user.

```php
Box::user()->transfer(from: '1111', to: '2222');
```

### Deprovision a user

Transfers a user's content to another account and wraps errors in an `OperationException`.

```php
Box::user()->deprovision(transferFrom: '1111', transferTo: '2222');
```

---

## Choosing a download approach

| Goal | Approach |
| --- | --- |
| Download by path in a Laravel route | `return Storage::disk('box')->download('folder/file.pdf');` |
| Read file contents as a string | `Storage::disk('box')->get('folder/file.pdf')` or `Box::file()->whereId($id)->contents()` |
| Download when you have the Box file ID | `return Box::file()->whereId($id)->downloadFile();` |
| Get a direct CDN URL | `Box::file()->whereId($id)->getDownloadUrl()` |

Always **return** download responses from routes — do not wrap them in `dd()`.
