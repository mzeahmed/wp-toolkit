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
    private const int HTTP_OK = 200;

    /**
     * HTTP code for a bad request (400 Bad Request).
     */
    private const int HTTP_BAD_REQUEST = 400;

    /**
     * HTTP code for an unauthorized request (401 Unauthorized).
     */
    private const int HTTP_UNAUTHORIZED = 401;

    /**
     * HTTP code for a resource not found (404 Not Found).
     */
    private const int HTTP_NOT_FOUND = 404;

    private const string SECURITY_NONCE = 'security_nonce';

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
    public static function registerAction(string $action, callable $callback, bool $private = true): void
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
        int $statusCode = self::HTTP_BAD_REQUEST
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
            /**
             * the security_nonce_action needs to be declared in the application with wp_create_nonce('security_nonce_action');
             */
            $nonce = isset($_POST[self::SECURITY_NONCE]) ? sanitize_text_field($_POST[self::SECURITY_NONCE]) : '';
            $nonceAction = is_user_logged_in() ? 'ys_user_' . get_current_user_id() . '_ajax_security_nonce_action' : 'ys_guest_ajax_security_nonce_action';
        }

        if (empty($nonce)) {
            self::sendJsonError(sprintf('The nonce %s is missing or invalid', $field));
        }

        if (!wp_verify_nonce($nonce, $nonceAction)) {
            self::sendJsonError('Unauthorized request', [], self::HTTP_UNAUTHORIZED);
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
}
