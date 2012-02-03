
#ifndef PHP_PBEXT_H
#define PHP_PBEXT_H

extern zend_module_entry pbext_module_entry;
#define pbext_module_ptr &pbext_module_entry

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
#define PHP_PBEXT_MSG_DESC_RES "PBExt Message descriptor"


PHP_MINIT_FUNCTION(pbext);
PHP_MSHUTDOWN_FUNCTION(pbext);
PHP_RINIT_FUNCTION(pbext);
PHP_RSHUTDOWN_FUNCTION(pbext);
PHP_MINFO_FUNCTION(pbext);


PHP_FUNCTION(pbext_desc_message);
PHP_FUNCTION(pbext_desc_field);
PHP_FUNCTION(pbext_decode);

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
#define PBEXT_G(v) TSRMG(module_globals_id, zend_module_globals *, v)
#else
#define PBEXT_G(v) (module_globals.v)
#endif

#endif	/* PHP_PBEXT_H */

