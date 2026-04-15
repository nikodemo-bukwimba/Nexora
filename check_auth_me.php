<?php

// Find the AuthController file
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('Modules')));
foreach ($files as $file) {
    if ($file->isFile() && str_contains($file->getFilename(), 'AuthController')) {
        echo $file->getPathname() . "\n";
    }
}

echo "\n";

// Also find where primaryRole is built in API responses
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (str_contains($content, 'primaryRole') || str_contains($content, 'primary_role')) {
            echo $file->getPathname() . "\n";
        }
    }
}
