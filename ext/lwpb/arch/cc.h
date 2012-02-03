/** @file cc.h
 * 
 * Compiler specifics.
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

#ifndef __LWPB_ARCH_CC_H__
#define __LWPB_ARCH_CC_H__

#ifndef NULL
#define NULL ((void *) 0)
#endif

#define U8_MIN  0
#define U8_MAX  0xff

#define U16_MIN 0
#define U16_MAX 0xffff

#define U32_MIN 0
#define U32_MAX 0xffffffff

#define U64_MIN 0
#define U64_MAX 0xffffffffffffffffLL

#define S8_MIN  (-127-1)
#define S8_MAX  127

#define S16_MIN (-32767-1)
#define S16_MAX 32767

#define S32_MIN (-2147483647-1)
#define S32_MAX 2147483647

#define S64_MIN (-9223372036854775807LL-1)
#define S64_MAX 9223372036854775807LL

typedef unsigned char u8_t;
typedef unsigned short int u16_t;
typedef unsigned int u32_t;
typedef unsigned long long int u64_t;

typedef signed char s8_t;
typedef short int s16_t;
typedef int s32_t;
typedef long long int s64_t;

#endif // __LWPB_ARCH_CC_H__
