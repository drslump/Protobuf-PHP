Protobuf for PHP
================

Protobuf for PHP is an implementation of Google's Protocol Buffers for the PHP
language, supporting its binary data serialization and including a `protoc` 
plugin to generate PHP classes from .proto files.

## Requirements

  - PHP 5.3  
  - Pear's Console_CommandLine (for the protoc wrapper tool)
  - Google's `protoc` compiler version 2.3 or above


## Features

### Working

  - Standard types (numbers, string, enums, messages, etc)
  - Pluggable serialization backends or codecs
    - Standard binary serialization
  - Protoc compiler plugin to generate the PHP classes
  - Extensions
  - Unknown fields

### Upcoming

  - Pear package
  - ProtoJson compatible serialization codec
  - Improve binary codec speed and memory usage

### Future

  - Lazy-loading of nested messages
  - Speed optimized code generation mode
  

## Example usage

	$person = new Tutorial\Person();
	$person->name = 'DrSlump';
	$person->setId(12);
	
	$book = new Tutorial\AddressBook();
	$book->addPerson($person);
	
	// Use default codec
	$data = $book->serialize();
	
	// Use custom codec
	$codec = new \DrSlump\Protobuf\Codec\Binary();
	$data = $codec->encode($book);
	// ... or ...
	$data = $book->serialize($codec);
	
	
## Generating PHP classes

The generation tool is designed to be run as a `protoc` plugin, thus it should
work with any proto file supported by the official compiler.

	protoc --plugin=protoc-gen-php --php_out=./build tutorial.proto
	
To make use of non-standard options in your proto files (like `php.namespace`) you'll
have to import the `php.proto` file included with the library. It's location will 
depend on where you've installed this library.

	protoc -I=./Protobuf-PHP/library/DrSlump/Protobuf/Compiler/protos \
	       --plugin=protoc-gen-php --php_out=./build tutorial.proto
	
In order to make your life easier, the supplied protoc plugin offers an additional
execution mode, where it acts as a wrapper for the `protoc` invocation. It will
automatically include the `php.proto` path so that you don't need to worry about it.

	protoc-gen-php -o ./build tutorial.proto
	
	









	
