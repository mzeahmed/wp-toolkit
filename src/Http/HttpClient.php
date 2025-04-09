<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit\Http;

/**
 * HTTP client for making GET, POST, PUT, and DELETE requests using WordPress HTTP API.
 *
 * This class wraps WordPress functions like wp_remote_get, wp_remote_post, etc.,
 * and provides simple, consistent methods for interacting with external APIs,
 * including optional URL safety checks and automatic JSON decoding.
 *
 * @since 2.0.1
 */
class HttpClient
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';

    public const RESPONSE_CODE_OK = 200;
    public const RESPONSE_CODE_CREATED = 201;
    public const RESPONSE_CODE_ACCEPTED = 202;
    public const RESPONSE_CODE_UPDATED = 204;

    /**
     * Perform an HTTP GET request.
     *
     * @param string $url The request URL.
     * @param array $args Optional request arguments (headers, auth, etc.).
     * @param bool $safe Whether to reject unsafe URLs.
     *
     * @return array|\WP_Error The decoded response or a WP_Error on failure.
     */
    public function get(string $url, array $args = [], bool $safe = false): \WP_Error|array
    {
        if ($safe) {
            $args['reject_unsafe_urls'] = true;
        }

        $request = wp_remote_get($url, $args);

        if (is_wp_error($request)) {
            return $request;
        }

        $response = wp_remote_retrieve_body($request);

        return $this->decode($response);
    }

    /**
     * Perform an HTTP POST request.
     *
     * @param string $url The request URL.
     * @param array $data The data to send in the request body.
     * @param array $args Optional request arguments.
     * @param bool $safe Whether to reject unsafe URLs.
     *
     * @return array|\WP_Error|null The decoded response or a WP_Error on failure.
     */
    public function post(string $url, array $data = [], array $args = [], bool $safe = false): \WP_Error|array|null
    {
        if ($safe) {
            $args['reject_unsafe_urls'] = true;
        }

        $args['body'] = $data;
        $request = wp_remote_post($url, $args);

        if (is_wp_error($request)) {
            return $request;
        }

        $response = wp_remote_retrieve_body($request);

        return $this->decode($response); 
    }

    /**
     * Perform an HTTP PUT request.
     *
     * @param string $url The request URL.
     * @param array $body The data to send in the request body.
     * @param array $args Optional request arguments.
     * @param bool $safe Whether to reject unsafe URLs.
     *
     * @return array|\WP_Error The decoded response or a WP_Error on failure.
     */
    public function put(string $url, array $body = [], array $args = [], bool $safe = false): \WP_Error|array
    {
        if ($safe) {
            $args['reject_unsafe_urls'] = true;
        }

        $args['method'] = self::METHOD_PUT;
        $args['body'] = $body;
        $request = wp_remote_request($url, $args);

        if (is_wp_error($request)) {
            return $request;
        }

        $response = wp_remote_retrieve_body($request);

        return $this->decode($response); 
    }

    /**
     * Perform an HTTP DELETE request.
     *
     * @param string $url The request URL.
     * @param array $args Optional request arguments.
     * @param bool $safe Whether to reject unsafe URLs.
     *
     * @return array|\WP_Error The decoded response or a WP_Error on failure.
     * @throws \JsonException
     */
    public function delete(string $url, array $args = [], bool $safe = false): \WP_Error|array
    {
        if ($safe) {
            $args['reject_unsafe_urls'] = true;
        }

        $args['method'] = self::METHOD_DELETE;
        $request = wp_remote_request($url, $args);

        if (is_wp_error($request)) {
            return $request;
        }

        $response = wp_remote_retrieve_body($request);

        return $this->decode($response);
    }

    /**
     * Perform decoding of JSON response.
     *
     * @param  string  $json The json string to decode.
     * @param  boolean $assoc Whether to return an associative array.
     * 
     * @return array 
     */
    private function decode(string $json, bool $assoc = true): array
    {
        if (!json_validate($json)) {
            return [];
        }

        $res = json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $res;
    }
}
