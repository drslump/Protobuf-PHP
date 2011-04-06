<?php

namespace DrSlump\Protobuf;

// Load descriptor messages
require_once __DIR__ . '/Compiler/protos/descriptor.pb.php';
require_once __DIR__ . '/Compiler/protos/plugin.pb.php';
require_once __DIR__ . '/Compiler/protos/php.pb.php';
require_once __DIR__ . '/Compiler/protos/json.pb.php';

use DrSlump\Protobuf;
use google\protobuf as proto;

class Compiler
{
    /** @var bool */
    protected $verbose = false;

    /** @var array */
    protected $packages = array();


    public function __construct($verbose = false)
    {
        $this->verbose = $verbose;
    }

    public function stderr($str)
    {
        $str = str_replace("\n", PHP_EOL, $str);
        fputs(STDERR, $str . PHP_EOL);
    }

    public function notice($str)
    {
        if ($this->verbose) {
            $this->stderr('NOTICE: ' . $str);
        }
    }

    public function warning($str)
    {
        $this->stderr('WARNING: ' . $str);
    }

    protected function error($str)
    {
        $this->stderr('ERROR: ' . $str);
    }

    public function getPackages()
    {
        return $this->packages;
    }

    public function hasPackage($package)
    {
        return isset($this->packages[$package]);
    }

    public function getPackage($package)
    {
        return $this->packages[$package];
    }

    public function setPackage($package, $namespace)
    {
        $this->packages[$package] = $namespace;
    }

    public function camelize($name)
    {
        return preg_replace_callback(
                    '/_([a-z])/i',
                    function($m){ return strtoupper($m[1]); },
                    $name
                 );
    }

    public function compile($data)
    {
        // Parse the request
        $req = new proto\compiler\CodeGeneratorRequest($data);

        // Set default generator class
        $generator = __CLASS__ . '\PhpGenerator';

        // Get plugin arguments
        if ($req->hasParameter()) {
            parse_str($req->getParameter(), $args);
            foreach ($args as $arg=>$val) {
                switch($arg){
                case 'verbose':
                    $this->verbose = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    break;
                case 'json':
                    $this->notice("Using ProtoJson generator");
                    $generator = __CLASS__ . '\JsonGenerator';
                    break;
                default:
                    $this->warning('Skipping unknown option ' . $arg);
                }
            }
        }

        // Create a suitable generator
        $generator = new $generator($this);

        // Setup response object
        $resp = new proto\Compiler\CodeGeneratorResponse();

        // First iterate over all the protos to get a map of namespaces
        $this->packages = array();
        foreach($req->getProtoFileList() as $proto) {
            $package = $proto->getPackage();
            $namespace = $generator->getNamespace($proto);
            $this->packages[$package] = $namespace;
            $this->notice("Mapping $package to $namespace");
        }

        // Get the list of files to generate
        $files = $req->getFileToGenerate();

        // Run each file
        foreach($req->getProtoFileList() as $file) {
            // Only compile those given to generate, not the imported ones
            if (!in_array($file->getName(), $files)) {
                $this->notice('Skipping generation of imported file "' . $file->getName() . '"');
                continue;
            }

            $sources = $generator->compileProtoFile($file);
            foreach($sources as $source) {
                $this->notice('Generating "' . $source->getName() . '"');
                $resp->addFile($source);
            }
        }

        // Finally serialize the response object
        return $resp->serialize();
    }

}

