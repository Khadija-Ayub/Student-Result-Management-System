<?php
session_start();
session_destroy();
header("Location: /student-result-system/index.php");
exit();