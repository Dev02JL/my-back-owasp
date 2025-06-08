<?php
$hash = '$2y$13$1mlRNN0o/q0XQ8HSs0JGiuk300GGS78ht/xwrLB.lo7pI47kKAJlC';
$password = 'password123';
var_dump(password_verify($password, $hash));