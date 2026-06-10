<?php
/**
 * GroceryPOS - Flash Message Helpers
 */

/**
 * Store a flash message in the session.
 *
 * @param  string $type    Bootstrap alert type: success | danger | warning | info
 * @param  string $message
 */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Retrieve and clear the flash message from the session.
 *
 * @return array|null  ['type' => string, 'message' => string] or null
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    if (!empty($_SESSION['_flash'])) {
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $flash;
    }

    return null;
}
