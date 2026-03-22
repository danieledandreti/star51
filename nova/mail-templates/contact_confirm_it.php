<?php
/**
 * Email Template: Contact Confirmation - Italiano
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

$mail_subject = 'Conferma ricezione richiesta - {site_name}';

$mail_body = <<<EOT
Ciao {first_name},

Abbiamo ricevuto la tua richiesta di contatto.

RIEPILOGO RICHIESTA:
Nome: {first_name} {last_name}
Email: {email}
{phone_line}Messaggio: {message}

Il nostro team esaminerà la tua richiesta e ti risponderà entro 24-48 ore.

Grazie per averci contattato.

{site_name} Team
{site_url}
EOT;
