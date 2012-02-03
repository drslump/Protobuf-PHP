/** @file private.h
 * 
 * Private types and definitions needed by the library.
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

#ifndef __LWPB_CORE_PRIVATE_H__
#define __LWPB_CORE_PRIVATE_H__

#include <lwpb/lwpb.h>


/** Protocol buffer wire types */
enum wire_type {
    WT_VARINT = 0,
    WT_64BIT  = 1,
    WT_STRING = 2,
    WT_32BIT  = 5,
};

/** Protocol buffer wire values */
union wire_value {
    u64_t varint;
    u64_t int64;
    struct {
        u64_t len;
        void *data;
    } string;
    u32_t int32;
};

void lwpb_buf_init(struct lwpb_buf *buf, void *data, size_t len);

size_t lwpb_buf_used(struct lwpb_buf *buf);

size_t lwpb_buf_left(struct lwpb_buf *buf);

#endif // __LWPB_CORE_PRIVATE_H__
