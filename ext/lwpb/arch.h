/** @file arch.h
 * 
 * Architecture specifics.
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

#ifndef __LWPB_CORE_ARCH_H__
#define __LWPB_CORE_ARCH_H__

#include <lwpb/arch/cc.h>
#include <lwpb/arch/sys.h>

#ifndef NULL
#define NULL ((void *) 0)
#endif

// Provide default implementations if none are given

#ifndef LWPB_MALLOC
#define LWPB_MALLOC(size)                                                   \
    do {                                                                    \
        LWPB_DIAG_PRINTF("No LWPB_MALLOC() implementation\n");              \
        LWPB_ABORT();                                                       \
    } while (0)
#endif

#ifndef LWPB_FREE
#define LWPB_FREE(ptr)                                                      \
    do {                                                                    \
        LWPB_DIAG_PRINTF("No LWPB_FREE() implementation\n");                \
        LWPB_ABORT();                                                       \
    } while (0)
#endif

#ifndef LWPB_MEMCPY
extern void *__lwpb_memcpy(void *, const void *, size_t);
#define LWPB_MEMCPY(dest, src, n) __lwpb_memcpy(dest, src, n)
#endif

#ifndef LWPB_MEMMOVE
extern void *__lwpb_memmove(void *, const void *, size_t);
#define LWPB_MEMMOVE(dest, src, n) __lwpb_memmove(dest, src, n)
#endif

#ifndef LWPB_MEMCMP
extern int __lwpb_memcmp(const void *, const void *, size_t);
#define LWPB_MEMCMP(s1, s2, n) __lwpb_memcmp(s1, s2, n)
#endif

#ifndef LWPB_STRLEN
extern size_t __lwpb_strlen(const char *);
#define LWPB_STRLEN(s) __lwpb_strlen(s)
#endif

#ifndef LWPB_DIAG_PRINTF
#define LWPB_DIAG_PRINTF(fmt, args...)
#endif

#ifndef LWPB_ABORT
#define LWPB_ABORT()                                                        \
    do {                                                                    \
        LWPB_DIAG_PRINTF("lwpb was aborted!\n")                             \
        while (1) {};                                                       \
    } while (0)

#endif

#endif // __LWPB_CORE_ARCH_H__
