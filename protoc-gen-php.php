#!/usr/bin/env php -d display_errors=stderr -d log_errors=On -d error_log=Off
<?php
//  Protobuf for PHP
//  Copyright (C) 2011 Iván -DrSlump- Montes <drslump@pollinimini.net>
//
//  This program is free software: you can redistribute it and/or modify
//  it under the terms of the GNU Affero General Public License as
//  published by the Free Software Foundation, either version 3 of the
//  License, or (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU Affero General Public License for more details.
//
//  You should have received a copy of the GNU Affero General Public License
//  along with this program.  If not, see <http://www.gnu.org/licenses/>.

// NOTE: The strange shebang line above is to force PHP into output
// all errors it founds to STDERR instead of STDOUT.

// Set up default timezone
date_default_timezone_set('GMT');

// For non pear packaged versions use relative include path
if (strpos('@php_bin@', '@php_bin') === 0) {
    set_include_path(__DIR__ . DIRECTORY_SEPARATOR . 'library' . PATH_SEPARATOR . get_include_path());
}

require_once 'DrSlump/Protobuf.php';

// Setup autoloader
\DrSlump\Protobuf::autoload();

try {
    // Run the cli interface
    \DrSlump\Protobuf\Compiler\Cli::run(__FILE__);
    exit(0);

} catch(Exception $e) {
    fputs(STDERR, (string)$e . PHP_EOL);
    exit(1);
}
