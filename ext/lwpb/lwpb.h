/** @file lwpb.h
 * 
 * Lightweight protocol buffers.
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

/** @mainpage Lightweight protocol buffers
 * 
 * @section sec_introduction Introduction
 * 
 * lwpb (short for lightweight protocol buffers) is an implementation of
 * Google's protocol buffers (in short protobuf) in C for systems with limited
 * resources. The latest version of lwpb can be found on Google Code:
 * 
 * http://code.google.com/p/lwpb
 * 
 * For more information about protocol buffers in general, see:
 * 
 * http://code.google.com/p/protobuf/
 * 
 * @section sec_design Design
 * 
 * Most protobuf implementations represent messages as hierarchies of objects
 * during runtime, which can be encoded/decoded to/from binary data streams.
 * lwpb in contrast, encodes and decodes messages on the fly, much like a SAX
 * parser does with XML. Encoding is done by calling encoder functions for each
 * message and field to be encoded. Decoding uses callback functions to notify
 * about messages and fields. Type information used for encoding and decoding
 * is gathered from the compiled protobuf schema, generated with the protoc
 * compiler using the lwpb output module.
 * 
 * @section sec_limitations Limitations
 * 
 * There are a few inherent limitations in lwpb, due to the simplicity of it's
 * design:
 * 
 * - The decoder does not warn the client when 'required' fields are missing
 * - The decoder does not warn the client when 'required' or 'optional' fields
 *   have multiple occurances
 * - The encoder does not implicitly encode 'required' fields with their
 *   default values, when the client does not manually encode them
 * 
 * @section sec_usage Usage
 * 
 * The following shows a few examples of how to use lwpb. We use the following
 * protobuf definitions:
 * 
 * @code
 * 
 * package test;
 * 
 * message Info {
 *     required int32 result = 1;
 *     required string msg = 2;
 * }
 * 
 * message TestMessage {
 *     required int32 count = 1;
 *     required Info info = 2;
 * }
 * 
 * @endcode
 * 
 * @subsection subsec_encoder Encoder
 * 
 * The following example will encode a simple message of the type 'TestMessage':
 * 
 * @code
 * 
 * void encode_example(void)
 * {
 *     struct lwpb_encoder encoder;
 *     unsigned char buf[128];
 *     size_t len;
 *     
 *     // Initialize the encoder
 *     lwpb_encoder_init(&encoder);
 *     
 *     // Start encoding a message of type 'test.TestMessage' into buf
 *     lwpb_encoder_start(&encoder, test_TestMessage, buf, sizeof(buf));
 *     
 *     // Encode a 55 to the field 'count'
 *     lwpb_encoder_add_int32(&encoder, test_TestMessage_count, 55);
 *     
 *     // Start encoding the nested message of type 'test.Info' in field 'info'
 *     lwpb_encoder_nested_start(&encoder, test_TestMessage_info);
 *     
 *     // Encode a -1 to the field 'result'
 *     lwpb_encoder_add_int32(&encoder, test_Info_result, -1);
 *     
 *     // Encode a "Unknown" to the field 'msg'
 *     lwpb_encoder_add_string(&encoder, test_Info_msg, "Unknown");
 *     
 *     // Finish encoding the nested message of type 'test.Info'
 *     lwpb_encoder_nested_end(&encoder);
 *     
 *     // Finish encoding the message of type 'test.TestMessage'
 *     len = lwpb_encoder_finish(&encoder);
 *     
 *     // buf now holds the encoded message which is len bytes long
 * }
 * 
 * @endcode
 * 
 * @subsection subsec_decoder Decoder
 * 
 * The following example will decode a simple message of the type 'TestMessage':
 * 
 * @code
 * 
 * // Structure to decode into
 * struct TestMessage {
 *     int32 count;
 *     struct {
 *         int32 result;
 *         char msg[32];
 *     } info;
 * }
 * 
 * void msg_start_handler(struct lwpb_decoder *decoder,
 *                        const struct lwpb_msg_desc *msg_desc, void *arg)
 * {
 *     // We don't use the message start handler
 * }
 * 
 * void msg_end_handler(struct lwpb_decoder *decoder,
 *                      const struct lwpb_msg_desc *msg_desc, void *arg)
 * {
 *     // We don't use the message end handler
 * }
 * 
 * void field_handler(struct lwpb_decoder *decoder,
 *                    const struct lwpb_msg_desc *msg_desc,
 *                    const struct lwpb_field_desc *field_desc,
 *                    union lwpb_value *value, void *arg)
 * {
 *     struct TestMessage *msg = arg;
 *     
 *     // Copy fields into local structure
 *     if (msg_desc == test_TestMessage) {
 *         if (field_desc == test_TestMessage_count)
 *             msg->count = value->int32;
 *     } else if (msg_desc == test_Info) {
 *         if (field_desc == test_Info_result)
 *             msg->info.result = value->int32;
 *         if (field_desc == test_Info_msg)
 *             strncpy(msg->info.msg, sizeof(msg->info.msg), value->string.str);
 *     }
 * }
 * 
 * void decode_example(void)
 * {
 *     struct lwpb_decoder decoder;
 *     unsigned char buf[128];
 *     size_t len;
 *     struct TestMessage msg;
 *     
 *     // Initialize the decoder
 *     lwpb_decoder_init(&decoder);
 *     
 *     // Register a pointer to our local structure we want to decode into as
 *     // the argument for the decoder event handlers
 *     lwpb_decoder_arg(&decoder, &msg);
 *     
 *     // Register event handlers
 *     lwpb_decoder_msg_handler(&decoder, msg_start_handler, msg_end_handler);
 *     lwpb_decoder_field_handler(&decoder, field_handler);
 *     
 *     // Decode the binary buffer from the encode example
 *     lwpb_decoder_decode(&decoder, test_TestMessage, buf, len, NULL);
 *     
 *     // The local structure 'msg' will now hold the decoded values
 * }
 * 
 * @endcode
 * 
 * @subsection subsec_struct_map Struct map
 * 
 */

#ifndef __LWPB_H__
#define __LWPB_H__

#include <lwpb/arch.h>
#include <lwpb/types.h>
#include <lwpb/decoder.h>
#include <lwpb/encoder2.h>


/** Simple assert macro */
#define LWPB_ASSERT(_expr_, _msg_)                                          \
    do {                                                                    \
        if (!(_expr_)) {                                                    \
            LWPB_DIAG_PRINTF(_msg_ "\n");                                   \
            LWPB_ABORT();                                                   \
        }                                                                   \
    } while (0)

/** Simple failure macro */
#define LWPB_FAIL(_msg_)                                                    \
    do {                                                                    \
        LWPB_DIAG_PRINTF(_msg_ "\n");                                       \
        LWPB_ABORT();                                                       \
    } while(0)

/* Logging macros */
#define LWPB_DEBUG(_format_, _args_...) \
    LWPB_DIAG_PRINTF("DBG: " _format_ "\n", ##_args_)
#define LWPB_INFO(_format_, _args_...) \
    LWPB_DIAG_PRINTF("INF: " _format_ "\n", ##_args_)
#define LWPB_ERR(_format_, _args_...) \
    LWPB_DIAG_PRINTF("ERR: " _format_ "\n", ##_args_)


#endif // __LWPB_H__
