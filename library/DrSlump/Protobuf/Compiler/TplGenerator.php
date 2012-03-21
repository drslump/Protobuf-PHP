<?php

namespace DrSlump\Protobuf\Compiler;

use DrSlump\Protobuf as Protobuf;
use google\protobuf as proto;

class TplGenerator extends AbstractGenerator
{
    /** @var string Path prefix for template files */
    protected $tpl;


    public function __construct(\DrSlump\Protobuf\Compiler $compiler)
    {
        parent::__construct($compiler);

        // Set the default template prefix
        $this->tpl = $this->option('tpl');
        if (!$this->tpl) {
            $this->tpl = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'php';
        } else if (basename($this->tpl) === $this->tpl) {
            $this->tpl = __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $this->tpl;
        }
    }

    /**
     * Initialize the generator to serve files for the given request
     *
     * @param google\protobuf\compiler\CodeGeneratorRequest $req
     */
    public function init(proto\compiler\CodeGeneratorRequest $req)
    {
        // Run the template's configure script
        $this->template('config', $req, null);
    }

    public function setTemplate($tpl)
    {
        $this->tpl = $tpl;
    }

    public function getTemplate()
    {
        return $this->tpl;
    }


    public function generate(proto\FileDescriptorProto $proto)
    {
        // Keep a reference to the current proto
        $this->proto = $proto;
        
        // Obtain the root namespace
        $ns = $proto->getPackage();

        // Reset the extensions dictionary
        $this->extensions = array();

        $result = array();

        // Generate Enums
        if (!empty($proto->enum_type)) {
            $result += $this->generateEnums($proto->enum_type, $ns);
        }

        // Generate Messages
        if (!empty($proto->message_type)) {
            $result += $this->generateMessages($proto->message_type, $ns);
        }

        // Collect extensions
        if (!empty($proto->extension_)) {
            foreach ($proto->extension_ as $field) {
                $this->extensions[$field->getExtendee()][] = $field;
            }
        }

        // Generate all extensions found in this proto file
        if (count($this->extensions)) {
            // In multifile mode we output all the extensions in a file named after
            // the proto file, since it's not trivial or even possible in all cases
            // to include the extensions with the extended message file.
            $fname = pathinfo($proto->name, PATHINFO_FILENAME) . '-extensions';

            $src = array();
            foreach ($this->extensions as $extendee=>$fields) {
                $src[] = $this->template('extension', $fields, $extendee);
            }

            $result[$fname] = implode("\n", $src);
        }


        // Generate services
        if ($this->option('generic_services') && $proto->hasService()) {
            foreach ($proto->getServiceList() as $service) {
                $src = $this->template('service', $service, $ns);
                $result[$namespace . '.' . $service->getName()] = $src;
            }
        }


        $suffix = $this->option('suffix', '.php');

        $files = array();
        if ($this->option('multifile', false)) {
            foreach ($result as $ns => $content) {
                if (empty($content)) {
                    continue;
                }

                // Generate a filename from the mapped namespace
                $fname = str_replace($this->nsSep, DIRECTORY_SEPARATOR, $this->ns($ns));
                $fname .= $suffix;

                $file = new proto\compiler\CodeGeneratorResponse\File();
                $file->setName($fname);

                $src = $this->template('file', $content, $ns);
                $file->setContent($src);

                $files[] = $file;
            }

        } else {

            $fname = pathinfo($proto->name, PATHINFO_FILENAME) . $suffix;

            $file = new \google\protobuf\compiler\CodeGeneratorResponse\File();
            $file->setName($fname);

            $src = $this->template('file', implode("\n", $result), $ns);
            $file->setContent($src);

            $files[] = $file;
        }

        return $files;
    }


    /**
     * Runs a template file
     *
     * @param string $tpl
     * @param mixed $data
     * @param string $namespace
     * @return string
     */
    public function template($tpl, $data, $namespace)
    {
        $tpl = "{$this->tpl}-{$tpl}.php";
        if (!is_readable($tpl)) {
            throw new \RuntimeException('Unable to open generator template file "' . $tpl . '"');
        }

        ob_start();
        include($tpl);
        return ob_get_clean();
    }


    protected function generateEnums($enums, $namespace)
    {
        $result = array();
        foreach ($enums as $enum) {
            $ns = $namespace . '.' . $enum->name;
            $result[$ns] = $this->template('enum', $enum, $namespace);
        }
        return $result;
    }

    protected function generateMessages($messages, $namespace)
    {
        $result = array();
        foreach ($messages as $msg) {
            $ns = $namespace . '.' . $msg->name;
            $result[$ns] = $this->template('message', $msg, $namespace);

            if (!empty($msg->enum_type)) {
                $result += $this->generateEnums($msg->getEnumType(), $ns);
            }
            if (!empty($msg->nested_type)) {
                $result += $this->generateMessages($msg->getNestedType(), $ns);
            }

            // Collect extensions
            if (!empty($msg->extension_)) {
                foreach ($msg->extension_ as $field) {
                    $this->extensions[$field->getExtendee()][] = $field;
                }
            }
        }

        return $result;
    }

}
