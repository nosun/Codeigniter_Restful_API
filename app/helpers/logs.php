<?php

function file_force_contents($dir, $contents)
{
    $parts = explode('/', $dir);
    $file = array_pop($parts);
    $dir = '';
    foreach ($parts as $part)
        if (!is_dir($dir .= "/$part")) mkdir($dir);
    file_put_contents("$dir/$file", $contents, FILE_APPEND);
}
