#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"

#include "php_pbext.h"


/* True global resources - no need for thread safety here */
static int le_pbext;
static int le_pbext_msg_desc;


/* {{{ pbext_functions[]
 *
 * Every user visible function must have an entry in pbext_functions[].
 */
const zend_function_entry pbext_functions[] = {
    PHP_FE(pbext_desc_message, NULL)
    PHP_FE(pbext_desc_field, NULL)
    PHP_FE(pbext_decode, NULL)
    PHP_FE_END	/* Must be the last line in pbext_functions[] */
};
/* }}} */

/* {{{ pbext_module_entry
 */
zend_module_entry pbext_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"pbext",
	pbext_functions,
	PHP_MINIT(pbext),
	PHP_MSHUTDOWN(pbext),
	NULL, //PHP_RINIT(pbext),		/* Replace with NULL if there's nothing to do at request start */
	NULL, //PHP_RSHUTDOWN(pbext),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(pbext),
#if ZEND_MODULE_API_NO >= 20010901
	"0.1", /* Replace with version number for your extension */
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_PBEXT
ZEND_GET_MODULE(pbext)
#endif


/* Use a fixed stack to collect nested messages */
typedef struct {
	size_t count;
	zval * frames[LWPB_MAX_DEPTH];
} pbext_stack_t;


/* Destructor for the message descriptor resource */
static void pbext_msg_desc_dtor( /* {{{ */
  zend_rsrc_list_entry *rsrc TSRMLS_DC) 
{
    struct lwpb_msg_desc *msg = (struct lwpb_msg_desc*)rsrc->ptr;
    if (msg) {
        if (msg->name) {
        	efree((char*)msg->name);
		}

        int i;
        for (i=0; i<msg->num_fields; i++) {
        	if (msg->fields[i].name) {
            	efree((char*)msg->fields[i].name);
			}
        }

        efree((struct lwpb_field_desc*)msg->fields);
        efree(msg);
    }
} /* }}} */


inline static zval* pbext_lwpb2zval( /* {{{ */
  unsigned int type, 
  union lwpb_value *value)
{
	zval *ret;

	// Allocate the zval structure
	MAKE_STD_ZVAL(ret);

	switch (type) {
		case LWPB_DOUBLE:
			ZVAL_DOUBLE(ret, value->double_);
			return ret;
    	case LWPB_FLOAT:
      		ZVAL_DOUBLE(ret, (double)value->float_);
      		return ret;
      	// TODO: Shall we case to Double large integers?
		case LWPB_INT64:
		case LWPB_SINT64:
		case LWPB_SFIXED64:
			ZVAL_LONG(ret, value->int64);
    		return ret;
		case LWPB_UINT64:
		case LWPB_FIXED64:
			ZVAL_LONG(ret, value->uint64);
    		return ret;
		case LWPB_SFIXED32:
		case LWPB_SINT32:
		case LWPB_INT32:
		case LWPB_ENUM:
			ZVAL_LONG(ret, value->int32);
			return ret;
    	case LWPB_FIXED32:
    	case LWPB_UINT32:
			ZVAL_LONG(ret, value->uint32);
      		return ret;
		case LWPB_BOOL:
			ZVAL_BOOL(ret, value->bool_);
			return ret;
		case LWPB_STRING:
		case LWPB_BYTES:
			ZVAL_STRINGL(ret, value->string.str, value->string.len, 1);
			return ret;
		case LWPB_MESSAGE:
			php_printf("ERROR: Unable to parse message fields yet\n");
			return NULL;
		default:
			php_printf("ERROR: Unknown field type\n");
			return NULL;
	}
} /* }}} */

static void pbext_msg_start_handler( /* {{{ */
  struct lwpb_decoder *decoder,
  const struct lwpb_msg_desc *msg,
  void *arg)
{
} /* }}} */

static void pbext_msg_end_handler( /* {{{ */
  struct lwpb_decoder *decoder,
  const struct lwpb_msg_desc *msg,
  void *arg)
{
    if (!arg) return;

	if (!decoder->packed) {
		// Pop the current message from the stack
		pbext_stack_t *stack = arg;
		stack->count--;
		stack->frames[stack->count] = NULL;
		//php_printf("POP: %d\n", stack->count);
	}
} /* }}} */

static void pbext_field_handler( /* {{{ */
  struct lwpb_decoder *decoder,
  const struct lwpb_msg_desc *msg,
  const struct lwpb_field_desc *field,
  union lwpb_value *value,
  void *arg)
{
	zval *zv;
    zval *dict;
    pbext_stack_t *stack = arg;

    if (!arg) return;

	//php_printf("Field: %s\n", field->name);

	// Get the target array from the stack
	dict = stack->frames[stack->count-1];

	// A NULL value means we're handling a message field
	if (value) {
		// Convert the value to a zval
		zv = pbext_lwpb2zval(field->opts.typ, value);
		if (!zv) { 
			php_error_docref(NULL TSRMLS_CC, E_WARNING, 
					"PBExt: Unable to convert value for field \"%s\" (number: %d, type: %d) in message \"%s\".", 
					field->name, (int)field->number, field->opts.typ, msg->name);
			return;
		}
	} else {
		// Allocate a new zval structure for the nested array
	    MAKE_STD_ZVAL(zv);
		array_init(zv);

		// Insert it in the stack so that it's used on the following calls
		stack->frames[stack->count++] = zv;
		//php_printf("PUSH: %d\n", stack->count);
	}

	// Handle repeated fields
	if (field->opts.label == LWPB_REPEATED) {
		zval *zrep;
		zval **tmp;
		int exists;

		// If it already exists we just have to append the new value to the repetitions
		exists = field->name
			   ? zend_hash_find(Z_ARRVAL_P(dict), field->name, strlen(field->name)+1, (void**)&tmp)
			   : zend_hash_index_find(Z_ARRVAL_P(dict), field->number, (void**)&tmp);
		if (exists == SUCCESS) {
			add_next_index_zval(*tmp, zv);
			return;
		}

		// Create a new array to hold the repetitions
		MAKE_STD_ZVAL(zrep);
		array_init(zrep);
		add_next_index_zval(zrep, zv);
		// Swap the value with the repetitions container to attach the later
		zv = zrep;
	}

	// Assign the zval to the array under the field key
	if (field->name) {
		add_assoc_zval(dict, field->name, zv);
	} else {
		add_index_zval(dict, field->number, zv);
	}

}
/* }}} */


/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(pbext)
{
	/* Register resources */
	le_pbext_msg_desc = zend_register_list_destructors_ex(
			pbext_msg_desc_dtor, NULL, PHP_PBEXT_MSG_DESC_RES, module_number);

	return SUCCESS;
}
/* }}} */

PHP_MSHUTDOWN_FUNCTION(pbext) /* {{{ */
{
	return SUCCESS;
} /* }}} */

PHP_MINFO_FUNCTION(pbext) /* {{{ */
{
	php_info_print_table_start();
	php_info_print_table_header(2, "pbext support", "enabled");
	php_info_print_table_end();
} /* }}} */


// [name] : resource
PHP_FUNCTION(pbext_desc_message) /* {{{ */
{
	// TODO: Create a version using persisting resources (perhaps giving it a message name?)
    char *name = NULL;
    long name_len = 0;
    struct lwpb_msg_desc * msg;

    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
            "|s", &name, &name_len) == FAILURE) {
        return;
    }

    // Allocate memory for the message
	msg = emalloc(sizeof(struct lwpb_msg_desc));

    // Allocate memory for the fields
    msg->num_fields = 0;
    msg->fields = (struct lwpb_field_desc*) emalloc(0);
	msg->name = name_len ? estrndup(name, name_len) : NULL;

	// Register and return a resource
    ZEND_REGISTER_RESOURCE(return_value, msg, le_pbext_msg_desc);
} /* }}} */

// msg(res), num(int), label(int), type(int), [name, [flag(int), [nested(res)]]] : bool
PHP_FUNCTION(pbext_desc_field) /* {{{ */
{
    zval *zmsg;
    long num;
    long label;
    long type;
    long flags = 0;
    char *name = NULL;
    size_t name_len = 0;
    zval *znested = NULL;    
    
    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, 
            "rlll|slr", &zmsg, &num, &label, &type, &name, &name_len, &flags, &znested) == FAILURE) {
        return;
    }

    // Get the message description
	struct lwpb_msg_desc *m;
    ZEND_FETCH_RESOURCE(m, struct lwpb_msg_desc*, &zmsg, -1, PHP_PBEXT_MSG_DESC_RES, le_pbext_msg_desc);

    // Check the field number is ok
    if (num < 0) {
 		php_error_docref(NULL TSRMLS_CC, E_WARNING, 
 				"The field number (%d) should be greater or equal to 1.", (int)num);
        RETURN_FALSE;
    }

	// Check the label is ok
    if (label < 1 || label > 3) {
 		php_error_docref(NULL TSRMLS_CC, E_WARNING, 
 				"The label (%d) is not recognized. Try 1 for optional, 2 for required or 3 for repeated.", (int)label);
        RETURN_FALSE;
	}

	// Check if message types supply their description
	if (type == LWPB_MESSAGE && znested == NULL) {
 		php_error_docref(NULL TSRMLS_CC, E_WARNING, 
 				"Nested message fields must supply a message descriptor resource as 7th argument.");
        RETURN_FALSE;
	}

	// Grow the allocated memory for field definitions
	m->num_fields++;
    m->fields = (struct lwpb_field_desc*) erealloc(m->fields, m->num_fields * sizeof(struct lwpb_field_desc));

    // Setup the field
    struct lwpb_field_desc* f = (struct lwpb_field_desc*)&m->fields[m->num_fields-1];
    f->number = (u32_t)num;
    f->opts.label = (unsigned int)label;
    f->opts.typ = (unsigned int)type;
    f->opts.flags = (unsigned int)flags;
    if (NULL != znested) {
        ZEND_FETCH_RESOURCE(f->msg_desc, struct lwpb_msg_desc*, &znested, -1, PHP_PBEXT_MSG_DESC_RES, le_pbext_msg_desc);
    }
	f->name = name_len ? estrndup(name, name_len) : NULL;

    RETURN_TRUE;
} /* }}} */

// msg(res), data : array
PHP_FUNCTION(pbext_decode) /* {{{ */
{
	zval *zmsg;
	char *data;
	size_t data_len;

	pbext_stack_t stack;
    struct lwpb_decoder decoder;
    lwpb_err_t ret;


    if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC,
            "rs", &zmsg, &data, &data_len) == FAILURE) {
        return;
    }

    // Get the message description
    struct lwpb_msg_desc *msg;
    ZEND_FETCH_RESOURCE(msg, struct lwpb_msg_desc*, &zmsg, -1, PHP_PBEXT_MSG_DESC_RES, le_pbext_msg_desc);


    // Initialize the return value as an array 
	array_init(return_value);

	// Setup the stack placing as top element the return value
	stack.count = 1;
	stack.frames[0] = return_value;

    // Set up the decoder
    lwpb_decoder_arg(&decoder, &stack);
    lwpb_decoder_msg_handler(&decoder, pbext_msg_start_handler, pbext_msg_end_handler);
    lwpb_decoder_field_handler(&decoder, pbext_field_handler);

    // Perform decoding
    ret = lwpb_decoder_decode(&decoder, msg, data, data_len, NULL);

    //php_printf("END!\n");

	if (ret == LWPB_ERR_OK) {
		// The return value is already set
		return;
	} 
	
	// Free any allocated zvals still residing in the stack
	while (stack.count--) {
		zval_ptr_dtor(&stack.frames[stack.count]);
	}
		
	RETURN_NULL();
} /* }}} */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
