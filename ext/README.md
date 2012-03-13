# Protobuf-PHP C extension

Decoding the Protocol Buffers binary format using only _user functions_ is slow
due to the nature of the PHP language and interpreter. This C language extension
to the PHP interpreter exposes a set of functions allowing the library to delegate
the decoding of binary messages to a C runtime.

The extension includes a stripped down version of the 
[Lightweight Protocol Buffers](http://code.google.com/p/lwpb/) (lwpb) library, although 
actually the source files have been taken from the 
[port available with the Python extension](https://github.com/acg/lwpb). So kudos to
[Simon Kallweit](http://code.google.com/p/lwpb/), [Alan Grow](https://github.com/acg) and
[Nick Vatamaniuc](https://github.com/nickva) for the good job.

## Status

The extension is still under development although it's able to decode messages already.
There are still tests to do regarding performance, memory leaks and a robust build setup. 

That said, use it at your own risk at report back any bugs or patches!


## Performance

The observed performance when using the extension is that it is between 2 and 3 times
faster than using `json_decode` on the same data. Obviously this doesn't take into account
the time needed to map the decoded array structures to the generated PHP classes of the
messages.

TBD


## Installing

The build setup is only compatible with *nix like systems (Bsd, Linux, OSX). There is
no automated way neither instructions to build the extension for Windows systems, sorry,
please feel free to provide them if you have the knowledge.

The following steps should get the extension ready to be used in your PHP installation:

    phpize
    ./configure --enable-protobuf
    make
    make test
    make install

If everything works as expected and no errors are shown with those commands, then you
should have the compiled shared library containing the extension in the correct location.
Now it's only a matter of enabling it in your `php.ini` file. Just add the following line
to your configuration:

    extension=protobuf.so


## API

While the Protobuf-PHP library already provides a _frontend_ to the functions provided 
by the extension, it might be desirable for some extreme use cases, to bypass it completely 
and use directly the extension.

Note that since the primary use case is to be wrapped by an _user land_ class, the API design
is very simple and more importantly, it does not check in detail the arguments given to the
functions. If you access the exposed functions directly be extra carefull when feeding them
arguments to make sure they work as intended.

> To allow the implementation of a _lazy decoding_ scheme, just define nested message fields
as having a binary type. The decoder will return the original encoded string without further
analyzing it.


### Constants

    PROTOBUF_FLAG_PACKED - Use it as a field's flags to force packed encoding of repeated values

### resource protobuf_desc_message( $name = NULL )

Create a new message descriptor to whom attach field descriptors with `protobuf_desc_field`.
The return value of this function is a `resource`, as such it's opaque in the PHP user land, 
it's need however to bind field descriptors to messages and to define nested messages.

Optionally you can assign a descriptive name to the message descriptor.

> Take into account that the created resources will not be freed until the script ends, so
  remember to store the returned value somewhere instead of creating a descriptor every time
  you want to decode a given message.

### void protobuf_desc_field( $message, $number, $label, $type, $name = NULL, $flags = 0, $nested = NULL )

TBD

### mixed protobuf_decode( $message, $data )

TBD


## License

The extension code is assumed to be part of the Protobuf-PHP package and as such is
distributed under the Mit license. The Lightweight Protocol Buffers project however 
is distributed according to the Apache License 2.0. This specifically means that
for the files residing in the `lwpb` directory the Apache License 2.0 applies, while
the remaining files fall under the Mit license terms.

