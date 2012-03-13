/* Copyright (c) 2012 Iván -DrSlump- Montes
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

#ifndef PHP_PROTOBUF_H
#define PHP_PROTOBUF_H

extern zend_module_entry protobuf_module_entry;
#define protobuf_module_ptr &protobuf_module_entry

#ifdef PHP_WIN32
#	define PHP_MODULE_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#	define PHP_MODULE_API __attribute__ ((visibility("default")))
#else
#	define PHP_MODULE_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

/* Include LWPB library */
#include <lwpb/lwpb.h>


/* Define the extension resource types */
#define PHP_PROTOBUF_MSG_DESC_RES "Protobuf Message descriptor"


PHP_MINIT_FUNCTION(protobuf);
PHP_MSHUTDOWN_FUNCTION(protobuf);
PHP_RINIT_FUNCTION(protobuf);
PHP_RSHUTDOWN_FUNCTION(protobuf);
PHP_MINFO_FUNCTION(protobuf);


PHP_FUNCTION(protobuf_desc_message);
PHP_FUNCTION(protobuf_desc_field);
PHP_FUNCTION(protobuf_decode);

/* In every utility function you add that needs to use variables 
   in php_module_globals, call TSRMLS_FETCH(); after declaring other 
   variables used by that function, or better yet, pass in TSRMLS_CC
   after the last function argument and declare your utility function
   with TSRMLS_DC after the last declared argument.  Always refer to
   the globals in your function as MODULE_G(variable).  You are 
   encouraged to rename these macros something shorter, see
   examples in any other php module directory.
*/

#ifdef ZTS
#define PROTOBUF_G(v) TSRMG(module_globals_id, zend_module_globals *, v)
#else
#define PROTOBUF_G(v) (module_globals.v)
#endif

#endif	/* PHP_PROTOBUF_H */

