<?php

declare(strict_types=1);

namespace MzeAhmed\WpHelpers;

/**
 * This class monitors user activity and determines their online/offline status in WordPress.
 *
 * It provides methods to update user activity, check if users are online, and retrieve lists of online or recently offline users.
 */
class UserActivityMonitor
{
    /**
     * User inactivity margin in seconds before being considered offline.
     * Currently set to 10 minutes.
     *
     * @var int
     */
    private const int ONLINE_STATUS_MARGIN = 10 * MINUTE_IN_SECONDS;

    /**
     * The duration in seconds for which the transient is stored.
     * Currently set to one day.
     *
     * @var int
     */
    private const int TRANSIENT_SELF_CLEAR = DAY_IN_SECONDS;

    /**
     * Sets and updates the transient that monitors user activity (`_user_online_status_transient`).
     * Updates the activity timestamp for the specified user.
     *
     * @param int $userId The user ID.
     */
    public function updateStatus(int $userId): void
    {
        $transient = get_transient('_user_online_status_transient');

        if (empty($transient)) {
            $transient = [];
        }

        $timestamp = current_time('timestamp');

        if (empty($transient[$userId]) || ($transient[$userId] < ($timestamp - self::ONLINE_STATUS_MARGIN))) {
            $transient[$userId] = $timestamp;

            set_transient('_user_online_status_transient', $transient);
        }
    }

    /**
     * Checks if a specific user is online.
     * A user is considered online if they have been active within the last 10 minutes.
     *
     * @param int $userId The user ID.
     *
     * @return bool True if the user is online, otherwise false. Null if no data is available.
     */
    public function isUserOnline(int $userId): bool
    {
        $transient = get_transient('_user_online_status_transient');

        return isset($transient[$userId]) && $transient[$userId] > (current_time('timestamp') - self::ONLINE_STATUS_MARGIN);
    }

    /**
     * Retrieves an array of IDs of users who recently went offline.
     * A user is considered recently offline if they have not been active within the last 10 minutes.
     *
     * @return array|null An array of IDs of recently offline users, or null if no users match this criterion.
     */
    public function getRecentlyOfflineUsers(): ?array
    {
        $transients = array_reverse(get_transient('_user_online_status_transient'), true);

        $recentlyOfflineUsersIds = [];

        foreach ($transients as $userId => $timestamp) {
            if ($timestamp < (current_time('timestamp') - self::ONLINE_STATUS_MARGIN)) {
                $recentlyOfflineUsersIds[] = $userId;
            }
        }

        return $recentlyOfflineUsersIds;
    }

    /**
     * Retrieves a list of users currently online.
     *
     * This method queries the activity tracking system to get the list of online user IDs
     * and then instantiates a `WP_User` object for each online user.
     *
     * @return array|null An array of `WP_User` objects representing online users, or `null` if no users are online.
     */
    public function getOnlineUsers(): ?array
    {
        $ids = $this->getOnlineUsersIds();
        $users = [];

        foreach ($ids as $id) {
            $users[] = new \WP_User($id);
        }

        return $users;
    }

    /**
     * Retrieves an array of IDs of all users currently online.
     * A user is considered online if they have been active within the last 10 minutes.
     *
     * @return array|null An array of IDs of currently online users, or null if no users are online.
     * @since 2.0.0
     */
    private function getOnlineUsersIds(): ?array
    {
        $transients = array_reverse(get_transient('_user_online_status_transient'), true);

        $onlineUsersIds = [];

        foreach ($transients as $userId => $timestamp) {
            if ($timestamp > (current_time('timestamp') - self::ONLINE_STATUS_MARGIN)) {
                $onlineUsersIds[] = $userId;
            }
        }

        return $onlineUsersIds;
    }
}
