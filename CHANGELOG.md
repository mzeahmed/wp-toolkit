### [1.0.10] - 2025-04-18
### ✨ Added
- Modify `AbstractRepository` class to add a new column in `change_logs` table

### [1.0.9] - 2025-04-10
### ✨ Improve
- Perform the `HttpClient` json_decode

### [1.0.8] - 2025-03-26
#### ✨ Added
- `HttpClient` class in `MzeAhmed\WpToolKit\Http` for making safe HTTP requests using the WordPress HTTP API.
    - Supports GET, POST, PUT, DELETE, PATCH
    - Automatically decodes JSON response bodies
    - Optional `safe` mode with `reject_unsafe_urls`

### [1.0.7] - 2025-03-25
#### ✨ Added
- `Utils::isDevEnvironment()` — helper method to check if the current WordPress environment is set to `development`.

### [1.0.6] - 2025-03-25
#### ✨ Added
New Sanitizer utility class to handle data cleaning operations

text(), email(), url(), textarea()

recursiveText() for nested arrays

byRules() to apply field-based sanitization dynamically

📚 Documentation
Updated README.md to include full usage examples for the Sanitizer class

🔒 Security
Improved input sanitization patterns throughout the toolkit
