dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

PHP_ARG_ENABLE(protobuf, whether to enable protobuf extension,
[  --enable-protobuf   Enable protobuf support])

if test "$PHP_PROTOBUF" != "no"; then
  AC_DEFINE(HAVE_PROTOBUF, 1, [Whether you have protobuf extension])
  PHP_NEW_EXTENSION(protobuf, php_protobuf.c lwpb/*.c, $ext_shared)
fi
