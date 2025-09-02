<?php

/* header('Access-Control-Allow-Origin: http://localhost:3000'); */
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

/* if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
} */

// JWT секретный ключ
define('JWT_SECRET', '60}LEBfK704BhT1ElgzKdeM~Dy59CFWo');
define('JWT_ALGORITHM', 'HS256');