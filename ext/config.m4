dnl $Id$
dnl config.m4 for extension module

dnl Comments in this file start with the string 'dnl'.
dnl Remove where necessary. This file will not work
dnl without editing.

PHP_ARG_ENABLE(pbext, whether to enable pbext extension,
[  --enable-pbext   Enable pbext support])


if test "$PHP_PBEXT" != "no"; then
  dnl Link with the lwpb static library
  dnl LDFLAGS="$LDFLAGS -L./lwpb/src -llwpb"

  AC_DEFINE(HAVE_PBEXT, 1, [Whether you have PBExt extension])
  PHP_NEW_EXTENSION(pbext, php_pbext.c lwpb/*.c, $ext_shared)
fi
