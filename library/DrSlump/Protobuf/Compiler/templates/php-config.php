<?php
/*
    Configures the generator with defaults suitable for these templates.

    Receives the following variables:

        $data - A google.protobuf.compiler.CodeGeneratorRequest object
*/ 

$this->setNamespaceSeparator('\\');
$this->setPrefix('php');

// Setup the list of reserved words. These must be suffixed with an underscore
// since the base message class used by this template already offers a setExtension
// and setUnknown functions with special threatment.
$this->reserved = array('extension', 'unknown');

