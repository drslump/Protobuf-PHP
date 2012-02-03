/** @file types.h
 * 
 * Public types and definitions needed by the library.
 * 
 * Copyright 2009 Simon Kallweit
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 *     
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

#ifndef __LWPB_CORE_TYPES_H__
#define __LWPB_CORE_TYPES_H__

#include <lwpb/lwpb.h>


/* Maximum depth of message embedding */
#ifndef LWPB_MAX_DEPTH
#define LWPB_MAX_DEPTH 32
#endif

/* Maximum number of required fields in a message */
#ifndef LWPB_MAX_REQUIRED_FIELDS
#define LWPB_MAX_REQUIRED_FIELDS 16
#endif

/* Provide field names as strings */
#ifndef LWPB_FIELD_NAMES
#define LWPB_FIELD_NAMES 1
#endif

/* Provide field default values */
#ifndef LWPB_FIELD_DEFAULTS
#define LWPB_FIELD_DEFAULTS 1
#endif

/* Provide message names as strings */
#ifndef LWPB_MESSAGE_NAMES
#define LWPB_MESSAGE_NAMES 1
#endif

/* Provide method names as strings */
#ifndef LWPB_METHOD_NAMES
#define LWPB_METHOD_NAMES 1
#endif

/* Provide service names as strings */
#ifndef LWPB_SERVICE_NAMES
#define LWPB_SERVICE_NAMES 1
#endif

/** Protocol buffer error codes */
typedef enum {
    LWPB_ERR_OK,                /**< OK */
    LWPB_ERR_CANCEL,            /**< Cancelled by user */
    LWPB_ERR_UNKNOWN_FIELD,     /**< User tried to encode an unknown field */
    LWPB_ERR_INVALID_FIELD,     /**< Invalid field in current context */
    LWPB_ERR_END_OF_BUF,        /**< End of buffer reached */
    LWPB_ERR_MEM,               /**< Memory allocation failed */
    // Socket service error codes
    LWPB_ERR_NET_INIT,          /**< Network initialization failed */
} lwpb_err_t;

/* Field labels */
#define LWPB_OPTIONAL       1
#define LWPB_REQUIRED       2
#define LWPB_REPEATED       3

/* Field value types */
#define LWPB_DOUBLE         1
#define LWPB_FLOAT          2
#define LWPB_INT64          3
#define LWPB_UINT64         4
#define LWPB_INT32          5
#define LWPB_FIXED64        6
#define LWPB_FIXED32        7
#define LWPB_BOOL           8
#define LWPB_STRING         9
#define LWPB_GROUP          10
#define LWPB_MESSAGE        11
#define LWPB_BYTES          12
#define LWPB_UINT32         13
#define LWPB_ENUM           14
#define LWPB_SFIXED32       15
#define LWPB_SFIXED64       16
#define LWPB_SINT32         17
#define LWPB_SINT64         18

/* Field flags */
#define LWPB_HAS_DEFAULT    (1 << 0)
#define LWPB_IS_PACKED      (1 << 1)
#define LWPB_IS_DEPRECATED  (1 << 2)

/** Protocol buffer field options */
typedef struct {
    unsigned int label : 2;
    unsigned int typ : 6;
    unsigned int flags : 8;
} lwpb_field_opts_t;

/** Protocol buffer bool type */
typedef int lwpb_bool_t;

/** Protocol buffer enum type */
typedef int lwpb_enum_t;

/** Protocol buffer value */
union lwpb_value {
    double double_;
    float float_;
    s32_t int32;
    s64_t int64;
    u32_t uint32;
    u64_t uint64;
    lwpb_bool_t bool_;
    struct {
        char *str;
        size_t len;
    } string;
    struct {
        u8_t *data;
        size_t len;
    } bytes;
    struct {
        void *data;
        size_t len;
    } message;
    lwpb_enum_t enum_;
    int null;
};

/* Forward declaration */
struct lwpb_msg_desc;

/** Protocol buffer field descriptor */
struct lwpb_field_desc {
    u32_t number;               /**< Field number */
    lwpb_field_opts_t opts;     /**< Field options (label, value type, flags) */
    const struct lwpb_msg_desc *msg_desc; /**< Message descriptor, if field is message */
#if LWPB_FIELD_NAMES
    const char *name;           /**< Field name */
#endif
#if LWPB_FIELD_DEFAULTS
    union lwpb_value def;       /**< Field default value */
#endif
};

/** Checks if a field is 'packed repeated' */
#define LWPB_IS_PACKED_REPEATED(field_desc)                                 \
    ((field_desc)->opts.label == LWPB_REPEATED &&                           \
     (field_desc)->opts.flags & LWPB_IS_PACKED)

/** Protocol buffer message descriptor */
struct lwpb_msg_desc {
    u32_t num_fields;           /**< Number of fields */
    const struct lwpb_field_desc *fields; /**< Array of field descriptors */
#if LWPB_MESSAGE_NAMES
    const char *name;
#endif
};

/* Forward declaration */
struct lwpb_service_desc;

/** Protocol buffer method descriptor */
struct lwpb_method_desc {
    const struct lwpb_service_desc *service; /**< Service descriptor */
    const struct lwpb_msg_desc *req_desc; /**< Request message descriptor */ 
    const struct lwpb_msg_desc *res_desc; /**< Response message descriptor */
#if LWPB_METHOD_NAMES
    const char *name;           /**< Method name */
#endif
};

/** Protocol buffer service descriptor */
struct lwpb_service_desc {
    const u32_t num_methods;    /**< Number of methods */
    const struct lwpb_method_desc *methods; /**< Array of method descriptors */
#if LWPB_SERVICE_NAMES
    const char *name;           /**< Service name */
#endif
};

/** RPC result codes. */
typedef enum {
    LWPB_RPC_OK,                /**< Call was successfully processed on the server */
    LWPB_RPC_NOT_CONNECTED,     /**< Not connected to the server */
    LWPB_RPC_FAILED,            /**< Call failed to execute on the server */
} lwpb_rpc_result_t;

/** Simple memory buffer */
struct lwpb_buf {
    u8_t *base;     /**< Buffers base address */
    u8_t *pos;      /**< Buffers current position */
    u8_t *end;      /**< Buffers end address (first invalid byte) */
};

#endif // __LWPB_CORE_TYPES_H__
