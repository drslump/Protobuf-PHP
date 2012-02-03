/** @file sys.h
 * 
 * System specifics.
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

#ifndef __LWPB_ARCH_SYS_H__
#define __LWPB_ARCH_SYS_H__

#include <stdlib.h>
#include <stdio.h>
#include <string.h>

#define LWPB_MALLOC(size) malloc(size)
#define LWPB_FREE(ptr) free(ptr)
#define LWPB_MEMCPY(dest, src, n) memcpy(dest, src, n)
#define LWPB_MEMMOVE(dest, src, n) memmove(dest, src, n)
#define LWPB_STRLEN(s) strlen(s)

#define LWPB_DIAG_PRINTF(fmt, args...) printf(fmt, ##args)
#define LWPB_ABORT() abort()

#endif // __LWPB_ARCH_CC_H__
