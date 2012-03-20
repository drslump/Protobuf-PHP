/** @file decoder.c
 * 
 * Implementation of the protocol buffers decoder.
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

#include "private.h"


// Debug handlers

static int debug_indent;

static void debug_print_indent(void)
{
    int i;
    
    for (i = 0; i < debug_indent; i++)
        LWPB_DIAG_PRINTF("  ");
}

static void debug_msg_start_handler(struct lwpb_decoder *decoder,
                                    const struct lwpb_msg_desc *msg_desc,
                                    void *arg)
{
    const char *name;

#if LWPB_MESSAGE_NAMES
    name = msg_desc->name;
#else
    name = "<message>";
#endif
    
    debug_print_indent();
    LWPB_DIAG_PRINTF("%s:\n", name);
    debug_indent++;
}

static void debug_msg_end_handler(struct lwpb_decoder *decoder,
                                  const struct lwpb_msg_desc *msg_desc,
                                  void *arg)
{
    debug_indent--;
}

static void debug_field_handler(struct lwpb_decoder *decoder,
                                const struct lwpb_msg_desc *msg_desc,
                                const struct lwpb_field_desc *field_desc,
                                union lwpb_value *value, void *arg)
{
    static char *typ_names[] = {
        "(double)",
        "(float)",
        "(int32)",
        "(int64)",
        "(uint32)",
        "(uint64)",
        "(sint32)",
        "(sint64)",
        "(fixed32)",
        "(fixed64)",
        "(sfixed32)",
        "(sfixed64)",
        "(bool)",
        "(enum)",
        "(string)",
        "(bytes)",
        "(message)",
    };
    
    const char *name;
    
#if LWPB_FIELD_NAMES
    name = field_desc->name;
#else
    name = "<field>";
#endif
    
    debug_print_indent();
    LWPB_DIAG_PRINTF("%-20s %-10s = ", name, typ_names[field_desc->opts.typ]);
    
    switch (field_desc->opts.typ) {
    case LWPB_DOUBLE:
        LWPB_DIAG_PRINTF("%f", value->double_);
        break;
    case LWPB_FLOAT:
        LWPB_DIAG_PRINTF("%f", value->float_);
        break;
    case LWPB_INT32:
    case LWPB_SINT32:
    case LWPB_SFIXED32:
        LWPB_DIAG_PRINTF("%d", value->int32);
        break;
    case LWPB_INT64:
    case LWPB_SINT64:
    case LWPB_SFIXED64:
        LWPB_DIAG_PRINTF("%lld", value->int64);
        break;
    case LWPB_UINT32:
    case LWPB_FIXED32:
        LWPB_DIAG_PRINTF("%u", value->int32);
        break;
    case LWPB_UINT64:
    case LWPB_FIXED64:
        LWPB_DIAG_PRINTF("%llu", value->int64);
        break;
    case LWPB_BOOL:
        LWPB_DIAG_PRINTF("%s", value->bool_ ? "true" : "false");
        break;
    case LWPB_ENUM:
        LWPB_DIAG_PRINTF("%d", value->enum_);
        break;
    case LWPB_STRING:
        while (value->string.len--)
            LWPB_DIAG_PRINTF("%c", *value->string.str++);
        break;
    case LWPB_BYTES:
        while (value->bytes.len--)
            LWPB_DIAG_PRINTF("%02x ", *value->bytes.data++);
        break;
    default:
        break;
    }
    
    LWPB_DIAG_PRINTF("\n");
}

// Decoder utilities

/**
 * Decodes a variable integer in base-128 format.
 * See http://code.google.com/apis/protocolbuffers/docs/encoding.html for more
 * information.
 * @param buf Memory buffer
 * @param varint Buffer to decode into
 * @return Returns LWPB_ERR_OK if successful or LWPB_ERR_END_OF_BUF if there
 * were not enough bytes in the memory buffer. 
 */
lwpb_err_t lwpb_decode_varint(struct lwpb_buf *buf, u64_t *varint)
{
    int bitpos;
    
    *varint = 0;
    for (bitpos = 0; *buf->pos & 0x80 && bitpos < 64; bitpos += 7, buf->pos++) {
        *varint |= (u64_t) (*buf->pos & 0x7f) << bitpos;
        if (buf->end - buf->pos < 2)
            return LWPB_ERR_END_OF_BUF;
    }
    *varint |= (u64_t) (*buf->pos & 0x7f) << bitpos;
    buf->pos++;
    
    return LWPB_ERR_OK;
}


static inline lwpb_err_t lwpb_decode_varint_fast(struct lwpb_buf *buf, u64_t *value) 
{
    // First check if it's just a single byte (very common case)
    if (buf->pos < buf->end && *(buf->pos) < 0x80) {

        *value = *buf->pos;
        buf->pos++;
        return LWPB_ERR_OK;
        
    // Now lets check if we can optimize medium values
    } else if (buf->end - buf->pos >= 4) {
        u64_t result;
        u64_t b;

        // Process the first 32bits in a speedy way
        b = *(buf->pos++); result = (b & 0x7F)       ; if (!(b & 0x80)) goto done;
        b = *(buf->pos++); result |= (b & 0x7F) <<  7; if (!(b & 0x80)) goto done;
        b = *(buf->pos++); result |= (b & 0x7F) << 14; if (!(b & 0x80)) goto done;
        b = *(buf->pos++); result |= (b & 0x7F) << 21; if (!(b & 0x80)) goto done;
        b = *(buf->pos++); result |= (b & 0x7F) << 28; if (!(b & 0x80)) goto done;

        // If we're still decoding lets continue with a slower alternative
        int bits;
        for (bits=35; buf->pos < buf->end && bits < 64; bits += 7) {
            b = *(buf->pos++); 
            result |= (b & 0x7F) << bits; 
            if (!(b & 0x80)) goto done;
        }

        // We have overrun the maximum size of a varint (10 bytes).  Assume
        // the data is corrupt.
        return LWPB_ERR_END_OF_BUF;

      done:
        *value = result;
        return LWPB_ERR_OK;

    // Fallback to the slow decoder for the remaining cases
    } else {
        return lwpb_decode_varint(buf, value);
    }
}



/**
 * Decodes a 32 bit integer
 * @param buf Memory buffer
 * @param value Buffer to decode into
 * @return Returns LWPB_ERR_OK if successful or LWPB_ERR_END_OF_BUF if there
 * were not enough bytes in the memory buffer. 
 */
lwpb_err_t lwpb_decode_32bit(struct lwpb_buf *buf, u32_t *value)
{
    if (lwpb_buf_left(buf) < 4)
        return LWPB_ERR_END_OF_BUF;

    *value = buf->pos[0] | (buf->pos[1] << 8) |
             (buf->pos[2] << 16) | (buf->pos[3] << 24);
    buf->pos += 4;
    
    return LWPB_ERR_OK;
}

/**
 * Decodes a 64 bit integer
 * @param buf Memory buffer
 * @param value Buffer to decode into
 * @return Returns LWPB_ERR_OK if successful or LWPB_ERR_END_OF_BUF if there
 * were not enough bytes in the memory buffer. 
 */
lwpb_err_t lwpb_decode_64bit(struct lwpb_buf *buf, u64_t *value)
{
    int i;
    
    if (lwpb_buf_left(buf) < 8)
        return LWPB_ERR_END_OF_BUF;
    
    *value = 0;
    for (i = 7; i >= 0; i--)
        *value = (*value << 8) | buf->pos[i];
    buf->pos += 8;
    
    return LWPB_ERR_OK;
}

static enum wire_type field_wire_type(const struct lwpb_field_desc *field_desc)
{
    switch (field_desc->opts.typ) {
    case LWPB_DOUBLE:
        return WT_64BIT;
    case LWPB_FLOAT:
        return WT_32BIT;
    case LWPB_INT32:
    case LWPB_INT64:
    case LWPB_UINT32:
    case LWPB_UINT64:
    case LWPB_SINT32:
    case LWPB_SINT64:
        return WT_VARINT;
    case LWPB_FIXED32:
        return WT_32BIT;
    case LWPB_FIXED64:
        return WT_64BIT;
    case LWPB_SFIXED32:
        return WT_32BIT;
    case LWPB_SFIXED64:
        return WT_64BIT;
    case LWPB_BOOL:
    case LWPB_ENUM:
        return WT_VARINT;
    case LWPB_STRING:
    case LWPB_BYTES:
    case LWPB_MESSAGE:
        return WT_STRING;
    }
}

/**
 * Pushes the decoder stack.
 * @param decoder Dncoder
 * @return Returns the top stack frame.
 */
static struct lwpb_decoder_stack_frame *push_stack_frame(struct lwpb_decoder *decoder)
{
    decoder->depth++;
    LWPB_ASSERT(decoder->depth <= LWPB_MAX_DEPTH, "Message nesting too deep");
    return &decoder->stack[decoder->depth - 1];
}

// Decoder

/**
 * Initializes the decoder.
 * @param decoder Decoder
 */
void lwpb_decoder_init(struct lwpb_decoder *decoder)
{
    decoder->arg = NULL;
    decoder->msg_start_handler = NULL;
    decoder->msg_end_handler = NULL;
    decoder->field_handler = NULL;
}

/**
 * Sets the user argument to be passed back with the handlers.
 * @param decoder Decoder
 * @param arg User argument
 */
void lwpb_decoder_arg(struct lwpb_decoder *decoder, void *arg)
{
    decoder->arg = arg;
}

/**
 * Sets the message start and end handlers.
 * @param decoder Decoder
 * @param msg_start_handler Message start handler
 * @param msg_end_handler Message end handler
 */
void lwpb_decoder_msg_handler(struct lwpb_decoder *decoder,
                            lwpb_decoder_msg_start_handler_t msg_start_handler,
                            lwpb_decoder_msg_end_handler_t msg_end_handler)
{
    decoder->msg_start_handler = msg_start_handler;
    decoder->msg_end_handler = msg_end_handler;
}

/**
 * Sets the field handler.
 * @param decoder Decoder
 * @param field_handler Field handler
 */
void lwpb_decoder_field_handler(struct lwpb_decoder *decoder,
                              lwpb_decoder_field_handler_t field_handler)
{
    decoder->field_handler = field_handler;
}

/**
 * Setups the decoder to use the verbose debug handlers which output the
 * message contents to the console.
 * @param decoder Decoder 
 */
void lwpb_decoder_use_debug_handlers(struct lwpb_decoder *decoder)
{
    lwpb_decoder_msg_handler(decoder, debug_msg_start_handler,
                             debug_msg_end_handler);
    lwpb_decoder_field_handler(decoder, debug_field_handler);
}

/**
 * Decodes a protocol buffer.
 * @param decoder Decoder
 * @param msg_desc Root message descriptor of the protocol buffer
 * @param data Data to decode
 * @param len Length of data to decode
 * @param used Returns the number of decoded bytes when not NULL.
 * @return Returns LWPB_ERR_OK when data was successfully decoded.
 */
lwpb_err_t lwpb_decoder_decode(struct lwpb_decoder *decoder,
                               const struct lwpb_msg_desc *msg_desc,
                               void *data, size_t len, size_t *used)
{
    lwpb_err_t ret;
    int i;
    u64_t key;
    int number;
    const struct lwpb_field_desc *field_desc = NULL;
    enum wire_type wire_type;
    union wire_value wire_value;
    union lwpb_value value;
    struct lwpb_decoder_stack_frame *frame, *new_frame;


    // Setup initial stack frame
    decoder->depth = 1;
    decoder->packed = 0;
    frame = &decoder->stack[decoder->depth - 1];
    lwpb_buf_init(&frame->buf, data, len);
    frame->msg_desc = msg_desc;
    frame->ofs = 0;
    
    while (decoder->depth >= 1) {
decode_nested:

        // Get current frame
        frame = &decoder->stack[decoder->depth - 1];
        
        // Notify start message
        if (frame->msg_desc && lwpb_buf_used(&frame->buf) == 0)
            if (decoder->msg_start_handler)
                decoder->msg_start_handler(decoder, frame->msg_desc, decoder->arg);

        // Process buffer
        while ((int)lwpb_buf_left(&frame->buf) > 0) {

            if (decoder->packed) {
                wire_type = field_wire_type(field_desc);
            } else {
                // Decode the field key
                ret = lwpb_decode_varint_fast(&frame->buf, &key);
                if (ret != LWPB_ERR_OK)
                    return ret;
            
                number = key >> 3;
                wire_type = key & 0x07;
            
                // Reset the field descriptor, otherwise we may end up confusing 
                // unknown fields with the previous one.
                field_desc = NULL;

                // Find a matching field using a circular list. Assumes that probably 
                // they appear in the stream in the order they have been defined.
                // When we find a repeated field we keep the offset at the found
                // position, otherwise we position it to the next element, this
                // way the next iteration starts checking from an optimum position.
                
                int ofs = frame->ofs;
                for (i=0; i < frame->msg_desc->num_fields; i++) {
                    if (frame->msg_desc->fields[ofs].number == number) {
                        field_desc = &frame->msg_desc->fields[ofs];
                        //php_printf("F: %d - %d - %s W: %d\n", number, field_desc->number, field_desc->name, wire_type);
                        if (field_desc->opts.label != LWPB_REPEATED)
                            ofs = (ofs+1) % frame->msg_desc->num_fields;
                        break;
                    }
                    ofs = (ofs+1) % frame->msg_desc->num_fields;
                }

                // Keep the next offset in the current stack frame
                frame->ofs = ofs;
                

                /*
                for (i=0; i< frame->msg_desc->num_fields; i++) {
                    if (frame->msg_desc->fields[i].number == number) {
                        field_desc = &frame->msg_desc->fields[i];
                        break;                   
                    }
                }
                */


            }
            
            // Decode field's wire value
            switch(wire_type) {
            case WT_VARINT:
                ret = lwpb_decode_varint_fast(&frame->buf, &wire_value.varint);
                if (ret != LWPB_ERR_OK)
                    return ret;
                break;
            case WT_64BIT:
                ret = lwpb_decode_64bit(&frame->buf, &wire_value.int64);
                if (ret != LWPB_ERR_OK)
                    return ret;
                break;
            case WT_STRING:
                ret = lwpb_decode_varint_fast(&frame->buf, &wire_value.string.len);
                if (ret != LWPB_ERR_OK)
                    return ret;
                if (wire_value.string.len > lwpb_buf_left(&frame->buf))
                    return LWPB_ERR_END_OF_BUF;

                wire_value.string.data = frame->buf.pos;
                frame->buf.pos += wire_value.string.len;
                break;
            case WT_32BIT:
                ret = lwpb_decode_32bit(&frame->buf, &wire_value.int32);
                if (ret != LWPB_ERR_OK)
                    return ret;
                break;
            default:
                LWPB_ASSERT(1, "Unknown wire type");
                break;
            }
            
            // Skip unknown fields
            if (!field_desc)
                continue;
            
            // Handle packed repeated fields
            if ((wire_type == WT_STRING) && LWPB_IS_PACKED_REPEATED(field_desc)) {
                // Create new stack frame
                new_frame = push_stack_frame(decoder);
                lwpb_buf_init(&new_frame->buf, wire_value.string.data, wire_value.string.len);
                new_frame->msg_desc = frame->msg_desc;
                
                // Enter packed repeated mode
                decoder->packed = 1;
                
                goto decode_nested;
            }
            
            switch (field_desc->opts.typ) {
            case LWPB_DOUBLE:
                LWPB_MEMCPY(&value.double_, &wire_value.int64, sizeof(double));
                break;
            case LWPB_FLOAT:
                LWPB_MEMCPY(&value.float_, &wire_value.int32, sizeof(float));
                break;
            case LWPB_INT32:
                value.int32 = wire_value.varint;
                break;
            case LWPB_INT64:
                value.int64 = wire_value.varint;
                break;
            case LWPB_UINT32:
                value.uint32 = wire_value.varint;
                break;
            case LWPB_UINT64:
                value.uint64 = wire_value.varint;
                break;
            case LWPB_SINT32:
                // Zig-zag encoding
                value.int32 = (wire_value.varint >> 1) ^ -((s32_t) (wire_value.varint & 1));
                break;
            case LWPB_SINT64:
                // Zig-zag encoding
                value.int64 = (wire_value.varint >> 1) ^ -((s64_t) (wire_value.varint & 1));
                break;
            case LWPB_FIXED32:
                value.uint32 = wire_value.int32;
                break;
            case LWPB_FIXED64:
                value.uint64 = wire_value.int64;
                break;
            case LWPB_SFIXED32:
                value.int32 = wire_value.int32;
                break;
            case LWPB_SFIXED64:
                value.int64 = wire_value.int64;
                break;
            case LWPB_BOOL:
                value.bool_ = wire_value.varint;
                break;
            case LWPB_ENUM:
                value.enum_ = wire_value.varint;
                break;
            case LWPB_STRING:
                value.string.len = wire_value.string.len;
                value.string.str = wire_value.string.data;
                break;
            case LWPB_BYTES:
                value.bytes.len = wire_value.string.len;
                value.bytes.data = wire_value.string.data;
                break;
            case LWPB_MESSAGE:
                if (decoder->field_handler)
                    decoder->field_handler(decoder, msg_desc, field_desc, NULL, decoder->arg);
                
                // Create new stack frame
                new_frame = push_stack_frame(decoder);
                lwpb_buf_init(&new_frame->buf, wire_value.string.data, wire_value.string.len);
                new_frame->msg_desc = field_desc->msg_desc;
                new_frame->ofs = 0;
                
                goto decode_nested;
            default:
                // Unknown types are simple ignored
                break;
            }
            
            if (decoder->field_handler)
                decoder->field_handler(decoder, frame->msg_desc, field_desc, &value, decoder->arg);
        }
        
        // Notify end message
        if (frame->msg_desc)
            if (decoder->msg_end_handler)
                decoder->msg_end_handler(decoder, frame->msg_desc, decoder->arg);
        
        // Pop the stack
        decoder->depth--;
        
        // Leave packed repeated mode
        decoder->packed = 0;
    }
    
    if (used)
        *used = lwpb_buf_used(&decoder->stack[0].buf);
    
    return LWPB_ERR_OK;
}
