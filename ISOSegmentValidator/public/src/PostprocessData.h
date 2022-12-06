/*

This file contains Original Code and/or Modifications of Original Code
as defined in and that are subject to the Apple Public Source License
Version 2.0 (the 'License'). You may not use this file except in
compliance with the License. Please obtain a copy of the License at
http://www.opensource.apple.com/apsl/ and read it before using this
file.

The Original Code and all software distributed under the License are
distributed on an 'AS IS' basis, WITHOUT WARRANTY OF ANY KIND, EITHER
EXPRESS OR IMPLIED, AND APPLE HEREBY DISCLAIMS ALL SUCH WARRANTIES,
INCLUDING WITHOUT LIMITATION, ANY WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE, QUIET ENJOYMENT OR NON-INFRINGEMENT.
Please see the License for the specific language governing rights and
limitations under the License.

*/


#ifndef _SRC_POST_PROCESS_DATA_H_
#define _SRC_POST_PROCESS_DATA_H_

#include "ValidateMP4.h"

class AudioVisualRollRecoveryEntry
{
    public:
        SInt16 roll_distance;
};


OSErr postprocessFragmentInfo(MovieInfoRec *mir);
void verifyLeafDurations(MovieInfoRec *mir);
void initializeLeafInfo(MovieInfoRec *mir, SInt32 numMediaSegments);
void checkNonIndexedSamples(MovieInfoRec *mir);
void verifyAlignment(MovieInfoRec *mir);
void verifyBSS(MovieInfoRec *mir);
void processSAP34(MovieInfoRec *mir);
OSErr processIndexingInfo(MovieInfoRec *mir);
void checkDASHBoxOrder(SInt32 cnt, atomOffsetEntry *list, SInt32 segmentInfoSize, bool initializationSegment, UInt64 *segmentSizes, MovieInfoRec *mir);
void checkSegmentStartWithSAP(int startWithSAP, MovieInfoRec *mir);
void estimatePresentationTimes(MovieInfoRec*mir);
void processBuffering(SInt32 cnt, atomOffsetEntry *list, MovieInfoRec *mir);
//CMAF box order checks' function definitions.
void checkCMAFBoxOrder(SInt32 cnt, atomOffsetEntry *list, SInt32 segmentInfoSize, bool CMAFHeader, UInt64 *segmentSizes);
void checkCMAFBoxOrder_moov(SInt32 cnt,atomOffsetEntry *list);
void checkCMAFBoxOrder_trak(SInt32 cnt,atomOffsetEntry *list);
void checkCMAFBoxOrder_mdia(SInt32 cnt,atomOffsetEntry *list);
void checkCMAFBoxOrder_minf(SInt32 cnt,atomOffsetEntry *list);
void checkCMAFBoxOrder_stbl(SInt32 cnt,atomOffsetEntry *list);
void checkCMAFBoxOrder_sinf(SInt32 cnt,atomOffsetEntry *list);
void checkCMAFBoxOrder_moof(SInt32 cnt,atomOffsetEntry *list);
void checkCMAFBoxOrder_traf(SInt32 cnt,atomOffsetEntry *list);


#endif //#define _SRC_POST_PROCESS_DATA_H_
