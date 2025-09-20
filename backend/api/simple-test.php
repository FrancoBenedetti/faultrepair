<?php
echo "Simple test through symlink<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Current file: " . __FILE__ . "<br>";
echo "PHP version: " . phpversion() . "<br>";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";
?>
