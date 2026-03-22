<?php
/**
 * Email Template: Password Reset - English
 *
 * Variables available:
 * - {first_name} : Admin first name
 * - {reset_url}  : Password reset URL with token
 */

$mail_subject = 'Password Recovery - {site_name}';

$mail_body = <<<EOT
Hi {first_name},

You have requested a password reset for your Nova Admin account.

Click the following link to set a new password:
{reset_url}

IMPORTANT:
- This link is valid for 30 minutes
- It can only be used once
- If you did not request this reset, please ignore this email

For security, we recommend:
1. Using a strong password (at least 8 characters)
2. Never sharing your credentials
3. Logging out when you finish working

{site_name} Team
{site_url}
EOT;
