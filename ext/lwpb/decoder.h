/** @file decoder.h
 * 
 * Lightweight protocol buffers decoder interface.
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

#ifndef __LWPB_CORE_DECODER_H__
#define __LWPB_CORE_DECODER_H__

#include <lwpb/lwpb.h>


/* Forward declaration */
struct lwpb_decoder;

/**
 * This handler is called when the decoder encountered a new message.
 * @param decoder Decoder
 * @param msg_desc Message descriptor
 * @param arg User argument
 */
typedef void (*lwpb_decoder_msg_start_handler_t)
    (struct lwpb_decoder *decoder,
     const struct lwpb_msg_desc *msg_desc, void *arg);

/**
 * This handler is called when the decoder finished decoding a message.
 * @param decoder Decoder
 * @param msg_desc Message descriptor
 * @param arg User argument
 */
typedef void (*lwpb_decoder_msg_end_handler_t)
    (struct lwpb_decoder *decoder,
     const struct lwpb_msg_desc *msg_desc, void *arg);

/**
 * This handler is called when the decoder has decoded a field.
 * @param decoder Decoder
 * @param msg_desc Message descriptor of the message containing the field
 * @param field_desc Field descriptor
 * @param value Field value
 * @param arg User argument
 */
typedef void (*lwpb_decoder_field_handler_t)
    (struct lwpb_decoder *decoder,
     const struct lwpb_msg_desc *msg_desc,
     const struct lwpb_field_desc *field_desc,
     union lwpb_value *value, void *arg);


/** Decoder stack frame */
struct lwpb_decoder_stack_frame {
    struct lwpb_buf buf;
    const struct lwpb_msg_desc *msg_desc;
    // Keep the last found field offset
    int ofs;
};

/** Protocol buffer decoder */
struct lwpb_decoder {
    void *arg;
    lwpb_decoder_msg_start_handler_t msg_start_handler;
    lwpb_decoder_msg_end_handler_t msg_end_handler;
    lwpb_decoder_field_handler_t field_handler;
    struct lwpb_decoder_stack_frame stack[LWPB_MAX_DEPTH];
    int depth;
    int packed;
};

void lwpb_decoder_init(struct lwpb_decoder *decoder);

void lwpb_decoder_arg(struct lwpb_decoder *decoder, void *arg);

void lwpb_decoder_msg_handler(struct lwpb_decoder *decoder,
                            lwpb_decoder_msg_start_handler_t msg_start_handler,
                            lwpb_decoder_msg_end_handler_t msg_end_handler);

void lwpb_decoder_field_handler(struct lwpb_decoder *decoder,
                              lwpb_decoder_field_handler_t field_handler);

void lwpb_decoder_use_debug_handlers(struct lwpb_decoder *decoder);

lwpb_err_t lwpb_decoder_decode(struct lwpb_decoder *decoder,
                               const struct lwpb_msg_desc *msg_desc,
                               void *data, size_t len, size_t *used);

lwpb_err_t lwpb_decode_varint(struct lwpb_buf *buf, u64_t *varint);

lwpb_err_t lwpb_decode_32bit(struct lwpb_buf *buf, u32_t *value);

lwpb_err_t lwpb_decode_64bit(struct lwpb_buf *buf, u64_t *value);

#endif // __LWPB_CORE_DECODER_H__
