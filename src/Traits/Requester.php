<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit\Traits;

/**
 * This trait provides methods to determine the type of the current request in WordPress.
 * It allows checking whether the request is an admin, cron, REST, AJAX, or public request.
 */
trait Requester
{
    /**
     * Determines the type of the current request based on the provided parameter.
     *
     * This method allows checking if the current request matches a specific type such as
     * `installing_wp`, `public`, `admin`, `rest`, `cron`, or `ajax`. If the request type is
     * unknown, a fatal error is triggered.
     *
     * @param string $type The type of request to check. Possible values are:
     *                     'installing_wp', 'public', 'admin', 'rest', 'cron', 'ajax'.
     *
     * @return bool Returns true if the request matches the given type, otherwise false.
     */
    public function request(string $type): bool
    {
        switch ($type) {
            case 'installing_wp':
                return $this->isInstallingWp();
            case 'public':
                return $this->isPublic();
            case 'admin':
                return $this->isAdmin();
            case 'rest':
                return $this->isRest();
            case 'cron':
                return $this->isCron();
            case 'ajax':
                return $this->isAjax();
            default:
                wp_die('Unknown request type: ' . $type);

                return false;
        }
    }

    /**
     * Checks if WordPress is currently installing.
     *
     * @return bool Returns true if WordPress is installing.
     */
    public function isInstallingWp(): bool
    {
        return \defined('WP_INSTALLING');
    }

    /**
     * Checks if the request is a public request (not admin, cron, or REST).
     *
     * @return bool Returns true if the request is public.
     */
    public function isPublic(): bool
    {
        return !$this->isAdmin() && !$this->isCron() && !$this->isRest();
    }

    /**
     * Checks if the request is an admin request.
     *
     * @return bool Returns true if the request is an admin request.
     * @since 2.0.0
     */
    public function isAdmin(): bool
    {
        return is_user_logged_in() && is_admin();
    }

    /**
     * Checks if the request is a REST API request.
     *
     * @return bool Returns true if the request is a REST API request.
     */
    public function isRest(): bool
    {
        return \defined('REST_REQUEST');
    }

    /**
     * Checks if the request is a cron request.
     *
     * @return bool Returns true if the request is a cron request.
     */
    public function isCron(): bool
    {
        return (\function_exists('wp_doing_cron') && wp_doing_cron()) || defined('DOING_CRON');
    }

    /**
     * Checks if the request is an AJAX request.
     *
     * @return bool Returns true if the request is an AJAX request.
     */
    public function isAjax(): bool
    {
        return (\function_exists('wp_doing_ajax') && wp_doing_ajax()) || defined('DOING_AJAX');
    }
}

