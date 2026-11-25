<?php
// The retired single-form workflow had no participant authentication.
require 'access.php';
failRequest(410, 'Open a private negotiation from the CharterDesk dashboard.');
