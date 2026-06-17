<?php

session_start();
session_destroy();

header('Location: ../client/login.php?message=Je bent uitgelogd&type=success');
exit;