#!/usr/bin/env php -d display_errors=stderr -d log_errors=On -d error_log=Off
<?php
// The MIT License
//
// Copyright (c) 2011 Iván -DrSlump- Montes
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.

// Set up default timezone
date_default_timezone_set('GMT');

$newIncludePath = null;

// For non pear packaged versions use relative include path
if (strpos('@php_bin@', '@php_bin') === 0) {
    $newIncludePath = __DIR__ . DIRECTORY_SEPARATOR . 'library';
}

// When being executed inside a valid Phar archive use it instead
if (class_exists('Phar')) {
    try {
        Phar::mapPhar('protobuf.phar');
        $newIncludePath = 'phar://protobuf.phar';
    } catch (PharException $e) {
    }
}

// Modify the include path if needed
if (NULL !== $newIncludePath) {
    set_include_path($newIncludePath . PATH_SEPARATOR . get_include_path());
}

// Disable strict errors for the compiler
error_reporting(error_reporting() & ~E_STRICT);

// Setup autoloader
require_once 'DrSlump/Protobuf.php';
\DrSlump\Protobuf::autoload();

try {
    // Run the cli interface
    \DrSlump\Protobuf\Compiler\Cli::run(__FILE__);
    exit(0);

} catch(Exception $e) {
    fputs(STDERR, (string)$e . PHP_EOL);
    exit(1);
}

// Allow this file to be used as a Phar stub
__HALT_COMPILER();
