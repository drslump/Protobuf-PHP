/** @file encoder.h
 * 
 * Lightweight protocol buffers encoder interface.
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

#ifndef __LWPB_CORE_ENCODER2_H__
#define __LWPB_CORE_ENCODER2_H__

#include <lwpb/lwpb.h>

/** Protocol buffer encoder */
struct lwpb_encoder2 {
    lwpb_bool_t packed;
};

size_t lwpb_encode_varint(u8_t *buf, u64_t varint);

size_t lwpb_encode_32bit(u8_t *buf, u32_t value);

size_t lwpb_encode_64bit(u8_t *buf, u64_t value);

void lwpb_encoder2_init(struct lwpb_encoder2 *encoder);

void lwpb_encoder2_start(struct lwpb_encoder2 *encoder,
                         const struct lwpb_msg_desc *msg_desc);

lwpb_err_t lwpb_encoder2_packed_repeated_start(struct lwpb_encoder2 *encoder,
                                     const struct lwpb_field_desc *field_desc);

lwpb_err_t lwpb_encoder2_packed_repeated_end(struct lwpb_encoder2 *encoder);

size_t lwpb_encoder2_add_field(struct lwpb_encoder2 *encoder,
                               const struct lwpb_field_desc *field_desc,
                               union lwpb_value *value,
                               u8_t* buf);

#endif // __LWPB_CORE_ENCODER2_H__
