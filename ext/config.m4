dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

PHP_ARG_ENABLE(pbext, whether to enable pbext extension,
[  --enable-pbext   Enable pbext support])

if test "$PHP_PBEXT" != "no"; then
  AC_DEFINE(HAVE_PBEXT, 1, [Whether you have PBExt extension])
  PHP_NEW_EXTENSION(pbext, php_pbext.c lwpb/*.c, $ext_shared)
fi
