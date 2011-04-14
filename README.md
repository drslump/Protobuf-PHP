Protobuf for PHP
================

Protobuf for PHP is an implementation of Google's Protocol Buffers for the PHP
language, supporting its binary data serialization and including a `protoc` 
plugin to generate PHP classes from .proto files.

Great effort has been put into generating PHP files that include all sort of type
hints to aide IDE's with autocompletion. Therefore, it can not only be used to
communicate with Protocol Buffers services but also as a generation tool for 
_data objects_ no matter what the final serialization is.

For more information see the included man pages.


## Requirements

  - PHP 5.3  
  - Pear's Console_CommandLine (for the protoc wrapper tool)
  - Google's `protoc` compiler version 2.3 or above


## Features

### Working

  - Standard types (numbers, string, enums, messages, etc)
  - Pluggable serialization backends (codecs)
    - Standard Binary serialization
    - Standard TextFormat (serialization only)
    - JSON
    - ProtoJson with _HashTag_ variant
    - ProtoJson with _Indexed_ variant
  - Protoc compiler plugin to generate the PHP classes
  - Extensions
  - Unknown fields
  - Packed fields
  - Reflection

### Upcoming

  - Pear package
  - XML serialization codec
  - Improve binary codec speed and memory usage

### Future

  - Lazy-loading of nested messages
  - Speed optimized code generation mode
  - Support numbers beyond PHP's native limits
  - Service stubs


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
	
	
## LICENSE:

	The MIT License

	Copyright (c) 2011 Iván -DrSlump- Montes

	Permission is hereby granted, free of charge, to any person obtaining
	a copy of this software and associated documentation files (the
	'Software'), to deal in the Software without restriction, including
	without limitation the rights to use, copy, modify, merge, publish,
	distribute, sublicense, and/or sell copies of the Software, and to
	permit persons to whom the Software is furnished to do so, subject to
	the following conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
	MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
	IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
	CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
	TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
	SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.






	
