<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit\Utils;

/**
 * Utility class for handling AJAX requests in WordPress.
 *
 * This class allows registering, unregistering, and managing AJAX responses in a standardized way.
 */
class Ajax
{
    /**
     * HTTP code for a successful response (200 OK).
     */
    private const HTTP_OK = 200;

    /**
     * HTTP code for a bad request (400 Bad Request).
     */
    private const HTTP_BAD_REQUEST = 400;

    /**
     * HTTP code for an unauthorized request (401 Unauthorized).
     */
    private const HTTP_UNAUTHORIZED = 401;

    /**
     * HTTP code for a resource not found (404 Not Found).
     */
    private const HTTP_NOT_FOUND = 404;

    /**
     * Registers a new AJAX action.
     *
     * This method registers an AJAX action in WordPress using the `add_action()` function.
     * It supports both private (authenticated users only) and public (accessible to all users) actions.
     *
     * @param string $action The name of the AJAX action to register.
     * @param callable $callback The callback function to execute when the AJAX action is triggered.
     * @param bool $private Whether the action is private (default: true) or public.
     *
     * @return void
     * @deprecated version 1.0.5.6 use addAction instead, the name is more descriptive
     *
     */
    public static function registerAction(string $action, callable $callback, bool $private = true): void
    {
        if ($private) {
            add_action('wp_ajax_' . sanitize_key($action), $callback);
        } else {
            add_action('wp_ajax_nopriv_' . sanitize_key($action), $callback);
        }
    }

    /**
     * Registers a new AJAX action.
     *
     * This method registers an AJAX action in WordPress using the `add_action()` function.
     * It supports both private (authenticated users only) and public (accessible to all users) actions.
     *
     * @param string $action The name of the AJAX action to register.
     * @param callable $callback The callback function to execute when the AJAX action is triggered.
     * @param bool $private Whether the action is private (default: true) or public.
     *
     * @return void
     */
    public static function addAction(string $action, callable $callback, bool $private = true): void
    {
        if ($private) {
            add_action('wp_ajax_' . sanitize_key($action), $callback);
        } else {
            add_action('wp_ajax_nopriv_' . sanitize_key($action), $callback);
        }
    }

    /**
     * Sends a JSON success response.
     *
     * This method sends a JSON response indicating success, optionally including additional data.
     *
     * @param string $message The success message to return.
     * @param array $data Additional data to include in the response.
     * @param int $statusCode The HTTP status code for the response (default: 200).
     *
     * @return void
     */
    public static function sendJsonSuccess(string $message, array $data = [], int $statusCode = self::HTTP_OK): void
    {
        if (!empty($message)) {
            $data['message'] = $message;
        }

        wp_send_json_success($data, $statusCode);
    }

    /**
     * Sends a JSON error response with a custom message.
     *
     * This method sends a JSON response indicating an error, optionally including additional data.
     *
     * @param string $message The error message to return.
     * @param array $data Additional data to include in the response.
     * @param int $statusCode The HTTP status code for the response (default: 400).
     *
     * @return void
     */
    public static function sendJsonError(
        string $message,
        array $data = [],
        int $statusCode = null
    ): void {
        self::logAjaxError($message);
        $data['error'] = !empty($message) ? $message : 'An error occurred';
        wp_send_json_error($data, $statusCode);
    }

    /**
     * Sends a JSON response based on the specified type.
     *
     * This method sends either a success or error JSON response based on the provided type.
     *
     * @param string $type The type of response ('success' or 'error').
     * @param array $data Data to include in the response.
     *
     * @return void
     */
    public static function sendJson(string $type, array $data): void
    {
        if ('success' === $type) {
            $message = $data['message'] ?? '';
            self::sendJsonSuccess($message, $data);
        } else {
            $message = $data['error'] ?? '';
            self::sendJsonError($message, $data);
        }
    }

    /**
     * Verifies if the nonce sent in an AJAX request is valid.
     *
     * This method checks if the nonce sent in an AJAX request is valid.
     * If the nonce is invalid, it sends a JSON error response.
     *
     * @param string|null $field The name of the field containing the nonce.
     * @param string|null $action The action associated with the nonce.
     *
     * @return void
     */
    public static function verifyNonce(?string $field = null, ?string $action = null): void
    {
        $nonce = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
        $nonceAction = $action;

        if (null === $field || null === $action) {
            if (!defined('AJAX_SECURITY_NONCE')) {
                self::sendJsonError('The constant AJAX_SECURITY_NONCE is not defined');
            }

            if (!defined('AJAX_SECURITY_NONCE_ACTION')) {
                self::sendJsonError('The constant AJAX_SECURITY_NONCE_ACTION is not defined');
            }

            /**
             * the security_nonce_action needs to be declared in the application with wp_create_nonce('security_nonce_action');
             */
            $nonce = isset($_POST[AJAX_SECURITY_NONCE]) ? sanitize_text_field($_POST[AJAX_SECURITY_NONCE]) : '';
            $nonceAction = AJAX_SECURITY_NONCE_ACTION;
        }

        if (empty($nonce)) {
            self::sendJsonError(sprintf('The nonce %s is missing or invalid', $field));
        }

        if (!wp_verify_nonce($nonce, $nonceAction)) {
            self::sendJsonError('Unauthorized request', [], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Guards AJAX requests by performing authentication and nonce validation.
     *
     * This method provides a convenient way to secure AJAX endpoints by combining
     * user authentication and nonce verification checks. It can be called at the
     * beginning of AJAX handlers to ensure the request is authorized and legitimate.
     *
     * If authentication fails, it sends a 401 Unauthorized JSON error response.
     * If nonce verification fails, it delegates to verifyNonce() which also sends
     * an appropriate error response.
     *
     * @param string|null $action The AJAX action name for contextual error messages (optional).
     *                            If provided, it will be included in error messages for debugging.
     * @param bool $checkLoggedIn Whether to verify that the user is authenticated (default: true).
     *                            If true and user is not logged in, sends a 401 error response.
     * @param bool $checkNonce Whether to verify the request nonce (default: true).
     *                         If true, calls verifyNonce() to validate the AJAX security nonce.
     *
     * @return void This method terminates execution by sending a JSON error if validation fails.
     *
     * @see verifyNonce() for nonce validation logic
     * @see sendJsonError() for error response format
     */
    public static function guard(?string $action = null, bool $checkLoggedIn = true, bool $checkNonce = true): void
    {
        if ($checkLoggedIn && !is_user_logged_in()) {
            $message = $action
                ? sprintf('Unauthorized request for action: %s', $action)
                : 'Unauthorized request';
            self::sendJsonError($message, [], self::HTTP_UNAUTHORIZED);
        }

        if ($checkNonce) {
            self::verifyNonce();
        }
    }

    /**
     * Logs an error message to the PHP error log.
     *
     * This method logs an error message to the PHP error log if WP_DEBUG is enabled.
     *
     * @param string $message The error message to log.
     *
     * @return void
     */
    public static function logAjaxError(string $message): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AJAX ERROR] ' . $message);
        }
    }

    /**
     * Asserts a condition and sends a JSON error response if the condition is false.
     *
     * @param bool $cond
     * @param string $msg
     *
     * @return void
     */
    public static function assert(bool $cond, string $msg): void
    {
        if (!$cond) {
            self::sendJsonError($msg);
        }
    }
}
