<? 
/*
    Configures the generator with defaults suitable for these templates.

    Receives the following variables:

        $namespace - The current namespace (aka package) for the file
        $data - A google.protobuf.FileProto object
*/ 

$this->setNamespaceSeparator('\\');
$this->setPrefix('php');

// Setup the list of reserved words. These must be suffixed with an underscore
// since the base message class used by this template already offers a setExtension
// and setUnknown functions with special threatment.
$this->reserved = array('extension', 'unknown');

