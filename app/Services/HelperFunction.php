<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HelperFunction
{
    /**
     * Extracts the authenticated user from the request.
     * Used in methods like runDecision()
     */
    public function auth(Request $request, $log_action = null, $log_id = null)
    {
        // Try to get the user from your custom middleware attribute, fallback to standard Laravel auth
        $user = $request->attributes->get('auth_user') ?? $request->user();

        if (!$user) {
            if ($log_action && $log_id) {
                Log::warning("Auth failed for action: {$log_action}", ['log_id' => $log_id]);
            }
            throw new \Exception('UNAUTHORIZED');
        }

        return $user;
    }

    /**
     * A standardized way to attach contextual data to logs.
     * Used heavily across your ApplicationController.
     */
    public static function attachLogData(Request $request, array $data)
    {
        // Extract the severity level and message (defaulting to info)
        $level = $data['level'] ?? 'info';
        $message = $data['message'] ?? 'System Log';

        // Remove them from the array so we just have the raw context left
        unset($data['level'], $data['message']);

        // Append useful data that helps debugging on AWS/Vercel
        $context = array_merge($data, [
            'ip_address' => $request->ip(),
            'method'     => $request->method(),
            'endpoint'   => $request->path(),
        ]);

        // Some of your logs use custom levels like 'audit_log_local'. 
        // We need to catch those and write them as standard 'info' logs with a prefix.
        if (in_array($level, ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])) {
            Log::$level($message, $context);
        } else {
            Log::info("[CUSTOM_LEVEL: {$level}] " . $message, $context);
        }
    }
}