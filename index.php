<?php
require 'vendor/autoload.php';

echo "<h1>PHP Quiz System</h1>";

if (extension_loaded("mongodb")) {
    echo "<p style='color:green'>MongoDB extension loaded!</p>";
} else {
    echo "<p style='color:red'>MongoDB extension NOT loaded!</p>";
    // Attempt to load it dynamically if possible (unlikely in this env but worth a shot if needed)
}

if (class_exists('MongoDB\Client')) {
     echo "<p style='color:green'>MongoDB Library loaded!</p>";
} else {
     echo "<p style='color:red'>MongoDB Library NOT loaded!</p>";
}
?>
