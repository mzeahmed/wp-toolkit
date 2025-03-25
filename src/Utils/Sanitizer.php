<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit\Utils;

class Sanitizer
{
    /**
     * Sanitize a single text field or array of text fields.
     *
     * @param string|array $data The text or array of texts to sanitize.
     * @return string|array The sanitized text or array.
     *
     * @example
     * Sanitizer::text(' Hello '); // returns 'Hello'
     * Sanitizer::text([' Foo ', ' Bar ']); // returns ['Foo', 'Bar']
     */
    public static function text(string|array $data): string|array
    {
        if (!is_array($data)) {
            return sanitize_text_field($data);
        }

        return array_map('sanitize_text_field', $data);
    }

    /**
     * Sanitize an email address or array of email addresses.
     *
     * @param string|array $data The email or array of emails to sanitize.
     * @return string|array The sanitized email or array.
     *
     * @example
     * Sanitizer::email(' test@example.com '); // returns 'test@example.com'
     * Sanitizer::email([' a@b.com ', ' c@d.com ']); // returns ['a@b.com', 'c@d.com']
     */
    public static function email(string|array $data): string|array
    {
        if (!is_array($data)) {
            return sanitize_email($data);
        }

        return array_map('sanitize_email', $data);
    }

    /**
     * Sanitize a URL or array of URLs.
     *
     * @param string|array $data The URL or array of URLs to sanitize.
     * @return string|array The sanitized URL or array.
     *
     * @example
     * Sanitizer::url(' https://example.com '); // returns 'https://example.com'
     * Sanitizer::url([' https://a.com ', ' https://b.com ']); // returns ['https://a.com', 'https://b.com']
     */
    public static function url(string|array $data): string|array
    {
        if (!is_array($data)) {
            return esc_url_raw($data);
        }

        return array_map('esc_url_raw', $data);
    }

    /**
     * Sanitize a textarea content or array of them.
     *
     * @param string|array $data The textarea string or array to sanitize.
     * @return string|array The sanitized textarea string or array.
     *
     * @example
     * Sanitizer::textarea(" Hello\nWorld "); // returns "Hello\nWorld"
     */
    public static function textarea(string|array $data): string|array
    {
        if (!is_array($data)) {
            return sanitize_textarea_field($data);
        }

        return array_map('sanitize_textarea_field', $data);
    }

    /**
     * Generic recursive sanitization for associative arrays (text fields only).
     *
     * @param array $data The associative array of data to sanitize.
     * @return array The recursively sanitized array.
     *
     * @example
     * Sanitizer::recursiveText(['name' => ' John ', 'meta' => ['city' => ' Paris ']]);
     * // returns ['name' => 'John', 'meta' => ['city' => 'Paris']]
     */
    public static function recursiveText(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::recursiveText($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        return $sanitized;
    }

    /**
     * Sanitize data using a ruleset array.
     *
     * @param array $data Raw input data
     * @param array $rules Associative array of rules: field => type (text|email|url|textarea)
     * @return array Cleaned data
     *
     * @example
     * $data = [
     *     'name' => ' John Doe ',
     *     'email' => 'john@example.com ',
     *     'website' => ' https://example.com ',
     *     'bio' => " Hello\nI am John.  "
     * ];
     *
     * $rules = [
     *     'name' => 'text',
     *     'email' => 'email',
     *     'website' => 'url',
     *     'bio' => 'textarea'
     * ];
     *
     * $clean = Sanitizer::byRules($data, $rules);
     * // Result:
     * // [
     * //     'name' => 'John Doe',
     * //     'email' => 'john@example.com',
     * //     'website' => 'https://example.com',
     * //     'bio' => "Hello\nI am John."
     * // ]
     */
    public static function byRules(array $data, array $rules): array
    {
        $sanitized = [];

        foreach ($rules as $field => $rule) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            switch ($rule) {
                case 'text':
                    $sanitized[$field] = self::text($value);
                    break;
                case 'email':
                    $sanitized[$field] = self::email($value);
                    break;
                case 'url':
                    $sanitized[$field] = self::url($value);
                    break;
                case 'textarea':
                    $sanitized[$field] = self::textarea($value);
                    break;
                default:
                    // Unknown rule, fallback to sanitize_text_field
                    $sanitized[$field] = sanitize_text_field($value);
                    break;
            }
        }

        return $sanitized;
    }
}
