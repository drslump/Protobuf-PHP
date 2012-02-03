/** @file buf.c
 * 
 * Simple memory buffers.
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

#include <lwpb/lwpb.h>


/**
 * Initializes a memory buffer. Sets the position to the base address.
 * @param buf Memory buffer
 * @param data Base address of memory
 * @param len Length of memory
 */
void lwpb_buf_init(struct lwpb_buf *buf, void *data, size_t len)
{
    buf->base = data;
    buf->pos = data;
    buf->end = &buf->base[len];
}

/**
 * Returns the number of used bytes in the buffer.
 * @param buf Memory buffer
 * @return Returns the number of used bytes.
 */
size_t lwpb_buf_used(struct lwpb_buf *buf)
{
    return buf->pos - buf->base;
}

/**
 * Returns the number of bytes left in the buffer.
 * @param buf Memory buffer
 * @return Returns the number of bytes left.
 */
size_t lwpb_buf_left(struct lwpb_buf *buf)
{
    return buf->end - buf->pos;
}
