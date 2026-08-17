<?php
session_start();
session_unset();
session_destroy();

header('Location: /sistema-agendamentos/auth/login.php');
exit;