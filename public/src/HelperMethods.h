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


#ifndef _SRC_HELPER_METHODS_H_
#define _SRC_HELPER_METHODS_H_

#include "ValidateMP4.h"

#define ABS(a) (((a) < 0) ? -(a) : (a));

int FindAtomOffsets( atomOffsetEntry *aoe, UInt64 minOffset, UInt64 maxOffset, 
			long *atomCountOut, atomOffsetEntry **atomOffsetsOut );
TrackInfoRec * check_track( UInt32 theID );
UInt32 getTrakIndexByID(UInt32 track_ID);
UInt32 getMoofIndexByOffset(MoofInfoRec *moofInfo, UInt32 numFragments, UInt64 offset);
UInt32 getSgpdIndex(SgpdInfoRec *sgpd, UInt32 numSgpd, UInt32 grouping_type);
SidxInfoRec *getSidxByOffset(SidxInfoRec *sidxInfo, UInt32 numSidx, UInt64 offset);
bool checkSegmentBoundry(UInt64 offsetLow, UInt64 offsetHigh);
int getSegmentNumberByOffset(UInt64 offset);
void logLeafInfo(MovieInfoRec *mir);

#endif //#define _SRC_HELPER_METHODS_H_

