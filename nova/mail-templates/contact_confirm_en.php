<?php
/**
 * Email Template: Contact Confirmation - English
 * Solo Edition - No Newsletter Section
 *
 * Variables available:
 * - {first_name}  : User first name
 * - {last_name}   : User last name
 * - {email}       : User email
 * - {phone_line}  : User phone (optional, formatted)
 * - {message}     : User message
 * - {site_name}   : Site name
 * - {site_url}    : Site URL
 */

$mail_subject = 'Contact Request Received - {site_name}';

$mail_body = <<<EOT
Hi {first_name},

We have received your contact request.

REQUEST SUMMARY:
Name: {first_name} {last_name}
Email: {email}
{phone_line}Message: {message}

Our team will review your request and respond within 24-48 hours.

Thank you for contacting us.

{site_name} Team
{site_url}
EOT;
