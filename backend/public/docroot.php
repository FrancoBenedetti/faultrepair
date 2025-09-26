<?php
include_once __DIR__ . '/../config/site.php';
echo 'Document root: '.$_SERVER['DOCUMENT_ROOT'].'<br>';
echo 'Server name: '.$_SERVER['SERVER_NAME'].'<br>';  
echo 'Domain: '.DOMAIN.'<br>';
echo 'Site name: '.SITE_NAME.'<br>';  
exit;