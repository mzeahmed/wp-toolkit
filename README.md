# WP ToolKit

**WP ToolKit** is a PHP library that simplifies WordPress development by providing reusable classes to handle common operations :

- AJAX requests
- Database operations
- and more...

## Installation

```bash
composer require mzeahmed/wp-helpers
```
Then include Composer's autoloader in your project:

```php
require_once 'vendor/autoload.php';
```

## Features
1. [AJAX Requests](#ajax-requests)
2. [Database Operations](#database-operations)
3. [User Activity Monitoring](#user-activity-monitoring)

## Ajax Requests

The `Ajax` utility class simplifies working with AJAX in WordPress by providing methods for registering AJAX actions, validating requests, and sending JSON responses.

### Features

- Register AJAX Actions: Supports both authenticated (wp_ajax_) and public (wp_ajax_nopriv_) actions.
- Send JSON Responses: Easily send success or error responses to the client.
- Nonce Validation: Verifies nonces to ensure request authenticity.
- Error Logging: Logs AJAX errors when WP_DEBUG is enabled.

### Usage

#### 1. Register an AJAX Action

```php
use MzeAhmed\WpToolKit\Utils\Ajax;

Ajax::register('my_action', 'my_callback');

function my_callback()
{
    $data = [
        'message' => 'Action executed successfully!',
        'additional_info' => 'Extra data here'
    ];
    
    Ajax::sendJsonSuccess('Success!', $data);
}
```

#### 2. Send JSON Responses

Use the `sendJsonSuccess` and `sendJsonError` methods to send standardized JSON responses:

```php
Ajax::sendJsonSuccess('Success message', ['data' => 'value']);
Ajax::sendJsonError('Error message', ['error' => 'reason']);
```

#### 3. Nonce Validation

Verify nonces to ensure that AJAX requests are secure:

```php
Ajax::verifyNonce('nonce_field_name', 'action_name');
```

Need to define theses constants in the application: `AJAX_SECURITY_NONCE_ACTION` and `AJAX_SECURITY_NONCE`.

```php
define('AJAX_SECURITY_NONCE_ACTION', 'my_action');
define('AJAX_SECURITY_NONCE', 'nonce_field_name');
```
And use wp_localize_script to pass the nonce to the client side.

```php
wp_localize_script('your-script-handle', 'ajax_object', [
    'ajax_url' => admin_url('admin-ajax.php'),
    AJAX_SECURITY_NONCE => wp_create_nonce(AJAX_SECURITY_NONCE_ACTION),
]);
```

In the client side, you can use the nonce like this:

```javascript
fetch(ajax_object.ajax_url, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-WP-Nonce': ajax_object.nonce_field_name
    },
    body: new URLSearchParams({
        action: 'my_action',
        data: 'value'
    })
})
```

## Database Operations

- **Abstract Repository:** Base class for implementing the repository pattern with `$wpdb`.
- **Pagination Support:** Easily paginate database results.
- **Bulk Operations:** Perform bulk inserts, updates, and deletes.
- **Transaction Management:** Start, commit, and rollback database transactions.
- **Error Logging:** Automatically log and debug database errors.
- **Dynamic Query Building:** Flexible support for building dynamic `WHERE` and `JOIN` clauses.

On the first usage, a new database table `wp_change_logs` will be created, which will be used to log insert, update, and delete operations used in the `AbstractRepository` class.

### Usage

#### 1. Implement a Repository

Create a repository class that extends the `AbstractRepository` class. For example:

```php
namespace YourNamespace\Repositories;

use Mzeahmed\WpToolKit\Database\AbstractRepository;

class YourCustomRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct();
        $this->tableName = $this->wpdbPrefix . 'your_table_name';
    }

    public function findActiveItems(): array
    {
        return $this->findByCriteria(['status' => 'active']);
    }
}
```

#### 2. Use the Repository

In your WordPress plugin or theme:

```php
use YourNamespace\Repositories\YourCustomRepository;

$repository = new YourCustomRepository();
$activeItems = $repository->findActiveItems();
```
#### 3. Bulk Operations

Perform bulk inserts, updates, or deletes:

```php
// Bulk insert example
$repository->bulkInsert([
    ['column1' => 'value1', 'column2' => 'value2'],
    ['column1' => 'value3', 'column2' => 'value4'],
]);

// Bulk delete example
$repository->bulkDelete('id', [1, 2, 3]);
```

#### 4. Transactions

Manage transactions to ensure data consistency:

```php
$repository->beginTransaction();

try {
    $repository->insert(['column1' => 'value1']);
    $repository->update(['column2' => 'value2'], ['id' => 1]);
    $repository->commit();
} catch (\Exception $e) {
    $repository->rollback();
    error_log($e->getMessage());
}
```

## User Activity Monitoring

The `UserActivityMonitor` class helps track user activity and determine online/offline status.

### Features

- Track User Activity: Monitor user activity and update the last seen timestamp.
- Check Online Status: Determine if a user is online within a specified margin.
- Retrieve Online Users: Get a list of all users currently online.
- Identify Recently Offline Users: Retrieve users who recently went offline.

### Usage

#### 1. Update User Activity

```php
use MzeAhmed\WpToolKit\UserActivityMonitor;

$monitor = new UserActivityMonitor();
$monitor->updateStatus($userId);
```

#### 2. Check Online Status

```php
$isOnline = $monitor->isUserOnline($userId);

if ($isOnline) {
    echo "User $userId is online.";
} else {
    echo "User $userId is offline.";
}
```

#### 3. Retrieve Online Users

```php
$onlineUsers = $monitor->getOnlineUsers();

foreach ($onlineUsers as $user) {
    echo "User $user->ID is online.";
}
```

#### 4. Identify Recently Offline Users

```php
$recentlyOfflineUsers = $monitor->getRecentlyOfflineUsers();

foreach ($recentlyOfflineUsers as $user) {
    echo "User $user->ID recently went offline.";
}
```

## 🔒 Data Sanitization

The `Sanitizer` class provides static methods to clean user input and ensure data safety in WordPress environments.

### Features

- ✅ Sanitize strings, emails, URLs, and textarea contents
- 🔁 Sanitize arrays and nested arrays
- 🎯 Apply custom sanitization rules per field

### Supported Rules

| Rule      | Description                        |
|-----------|------------------------------------|
| `text`    | Uses `sanitize_text_field()`       |
| `email`   | Uses `sanitize_email()`            |
| `url`     | Uses `esc_url_raw()`               |
| `textarea`| Uses `sanitize_textarea_field()`   |

### Usage

#### 1. Sanitize a single value or array

```php
use MzeAhmed\WpToolKit\Utils\Sanitizer;

$cleanText = Sanitizer::text(' John ');
// returns 'John'

$cleanEmailArray = Sanitizer::email(['  a@a.com ', 'b@b.com ']);
// returns ['a@a.com', 'b@b.com']
```

### 2. Recursively sanitize text values in nested arrays

```php
$dirtyArray = [
    'name' => ' John ',
     'meta' => [
        'city' => ' Paris ',
        'desc' => " Hello\nWorld "
    ]
];

$clean = Sanitizer::recursiveText($raw);
// returns [
//   'name' => 'John',
//   'meta' => [
//     'city' => 'Paris',
//     'desc' => "Hello\nWorld"
//   ]
// ]
```

### 3. Apply custom sanitization rules

```php
$data = [
    'name' => ' John Doe ',
    'email' => ' john@example.com ',
    'website' => ' https://example.com ',
    'bio' => " Hello\nI'm John "
];

$rules = [
    'name' => 'text',
    'email' => 'email',
    'website' => 'url',
    'bio' => 'textarea'
];

$cleaned = Sanitizer::byRules($data, $rules);
// returns [
//   'name' => 'John Doe',
//   'email' => 'john@example.com',
//   'website' => 'https://example.com',
//   'bio' => "Hello\nI'm John"
// ]
```