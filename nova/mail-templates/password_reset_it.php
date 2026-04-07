<?php
/**
 * Email Template: Password Reset - Italiano
 *
 * Variables available:
 * - {first_name} : Admin first name
 * - {reset_url}  : Password reset URL with token
 */

$mail_subject = 'Recupero Password - {site_name}';

$mail_body = <<<EOT
Ciao {first_name},

Hai richiesto il recupero della password per il tuo account Nova Admin.

Clicca sul seguente link per impostare una nuova password:
{reset_url}

IMPORTANTE:
- Questo link è valido per 30 minuti
- Può essere utilizzato una sola volta
- Se non hai richiesto tu questo recupero, ignora questa email

Per sicurezza, ti consigliamo di:
1. Utilizzare una password robusta (almeno 8 caratteri)
2. Non condividere mai le tue credenziali
3. Effettuare il logout quando finisci di lavorare

{site_name} Team
{site_url}
EOT;
