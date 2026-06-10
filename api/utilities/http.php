<?php
/**
 * Quantum BlocX — tiny HTTP helper.
 *
 * finish_response(): send the JSON response to the client and close the connection,
 * so the request can keep running (e.g. a slow SMTP send) without making the user wait.
 * Works under both PHP-FPM (fastcgi_finish_request) and mod_php (Connection: close).
 */

if (!function_exists('finish_response')) {
    function finish_response(array $payload): void
    {
        $json = json_encode($payload);
        ignore_user_abort(true);
        while (ob_get_level() > 0) { ob_end_clean(); }
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('Content-Length: ' . strlen($json));
            header('Connection: close');
        }
        echo $json;
        // Release the session lock so the user's next request isn't blocked while we finish.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            flush();
        }
    }
}
