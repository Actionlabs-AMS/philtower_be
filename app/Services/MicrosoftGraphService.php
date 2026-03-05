<?php

namespace App\Services;

use App\Helpers\MicrosoftGraphHelper;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class MicrosoftGraphService
{
    /**
     * Send user registration email using Microsoft Graph
     */
    public static function sendUserRegistrationEmail(User $user, $verificationLink)
    {
        try {
            return MicrosoftGraphHelper::sendUserRegistrationEmail(
                $user->user_email,
                $user->user_login,
                $verificationLink
            );
        } catch (\Exception $e) {
            Log::error('Failed to send user registration email via Microsoft Graph: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send password reset email using Microsoft Graph
     */
    public static function sendPasswordResetEmail(User $user, $resetLink)
    {
        try {
            return MicrosoftGraphHelper::sendPasswordResetEmail(
                $user->user_email,
                $user->user_login,
                $resetLink
            );
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email via Microsoft Graph: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send two-factor authentication code email using Microsoft Graph
     */
    public static function sendTwoFactorCodeEmail(User $user, $code)
    {
        try {
            return MicrosoftGraphHelper::sendTwoFactorCodeEmail(
                $user->user_email,
                $user->user_login,
                $code
            );
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA code email via Microsoft Graph: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send custom notification email using Microsoft Graph
     */
    public static function sendNotificationEmail($to, $subject, $body, $cc = [])
    {
        try {
            return MicrosoftGraphHelper::sendNotificationEmail($to, $subject, $body, $cc);
        } catch (\Exception $e) {
            Log::error('Failed to send notification email via Microsoft Graph: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send email with attachments using Microsoft Graph
     */
    public static function sendEmailWithAttachments($to, $subject, $body, $attachments = [], $cc = [])
    {
        try {
            return MicrosoftGraphHelper::sendEmail($to, $subject, $body, $cc, $attachments);
        } catch (\Exception $e) {
            Log::error('Failed to send email with attachments via Microsoft Graph: ' . $e->getMessage());
            throw $e;
        }
    }
}
