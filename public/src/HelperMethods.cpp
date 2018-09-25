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


#include "HelperMethods.h"
#include "PostprocessData.h"
#include <math.h> 

//==========================================================================================

int FindAtomOffsets( atomOffsetEntry *aoe, UInt64 minOffset, UInt64 maxOffset, 
			long *atomCountOut, atomOffsetEntry **atomOffsetsOut )
{
	int err = noErr;
	long cnt = 0;
	atomOffsetEntry *atomOffsets = nil;
	long max = 20;
	startAtomType startAtom;
	UInt64 largeSize;
	uuidType uuid;
	UInt64 curOffset = minOffset;
	long minAtomSize;
	
	BAILIFNULL( atomOffsets = (atomOffsetEntry *)calloc( max, sizeof(atomOffsetEntry)), allocFailedErr );
	
	while (curOffset< maxOffset) {
		memset(&atomOffsets[cnt], 0, sizeof(atomOffsetEntry));	// clear out entry
		atomOffsets[cnt].offset = curOffset;
		BAILIFERR( GetFileDataN32( aoe, &startAtom.size, curOffset, &curOffset ) );
		BAILIFERR( GetFileDataN32( aoe, &startAtom.type, curOffset, &curOffset ) );
		minAtomSize = sizeof(startAtom);
		atomOffsets[cnt].size = startAtom.size;
		atomOffsets[cnt].type = startAtom.type;
		if (startAtom.size == 1) {
			BAILIFERR( GetFileDataN64( aoe, &largeSize, curOffset, &curOffset ) );
			atomOffsets[cnt].size = largeSize;
			minAtomSize += sizeof(largeSize);
			
		}
		if (startAtom.type == 'uuid') {
			BAILIFERR( GetFileData( aoe, &uuid, curOffset, sizeof(uuid), &curOffset ) );
			//atomOffsets[cnt].uuid = uuid;
			memcpy(&atomOffsets[cnt].uuid, &uuid, sizeof(uuid));
			minAtomSize += sizeof(uuid);
		}
		
		atomOffsets[cnt].atomStartSize = minAtomSize;
		atomOffsets[cnt].maxOffset = atomOffsets[cnt].offset + atomOffsets[cnt].size;
		
		if (atomOffsets[cnt].size == 0) {
			// we go to the end
			atomOffsets[cnt].size = maxOffset - atomOffsets[cnt].offset;
			break;
		}
		
		BAILIF( (atomOffsets[cnt].size < (UInt64)minAtomSize), badAtomSize );
		
		curOffset = atomOffsets[cnt].offset + atomOffsets[cnt].size;
		cnt++;
		if (cnt >= max) {
			max += 20;
			atomOffsets = (atomOffsetEntry *)realloc(atomOffsets, max * sizeof(atomOffsetEntry));
		}
	}

bail:
	if (err) {
		cnt = 0;
		if (atomOffsets) 
			free(atomOffsets);
		atomOffsets = nil;
	}
	*atomCountOut = cnt;
	*atomOffsetsOut = atomOffsets;
	return err;
}

TrackInfoRec * check_track( UInt32 theID )
{
	MovieInfoRec	*mir = vg.mir;
	UInt32 i;
	
	if (theID==0) {
		errprint("Track ID %d in track reference atoms cannot be zero\n",theID);
		return 0;
	}
	
	for (i=0; i<(UInt32)mir->numTIRs; ++i) {
			if ((mir->tirList[i].trackID) == theID) return &(mir->tirList[i]);
	}		
	errprint("Track ID %d in track reference atoms references a non-existent track\n",theID);
    
    return 0;
}

UInt32 getTrakIndexByID(UInt32 track_ID)
{
    UInt32 i = 0;
    
    for(i = 0 ; i < (UInt32)vg.mir->numTIRs ; i++)
        if(vg.mir->tirList[i].trackID == track_ID)
            return i;

    errprint("getTrakIndexByID: Track ID %d is not a known track!\n",track_ID);

    return vg.mir->numTIRs;
}

UInt32 getSgpdIndex(SgpdInfoRec *sgpd, UInt32 numSgpd, UInt32 grouping_type)
{
    for(UInt32 i = 0; i < numSgpd ; i++)
        if(sgpd[i].grouping_type == grouping_type)
            return i;

    return numSgpd;
}


UInt32 getMoofIndexByOffset(MoofInfoRec *moofInfo, UInt32 numFragments, UInt64 offset)
{
    UInt32 i;

    for(i = 0 ; i < numFragments ; i++)
    {
        if(moofInfo[i].offset == offset)
            return i;
    }

    return numFragments;
}


SidxInfoRec *getSidxByOffset(SidxInfoRec *sidxInfo, UInt32 numSidx, UInt64 offset)
{
    UInt32 i;

    for(i = 0 ; i < numSidx ; i++)
        if(sidxInfo[i].offset == offset)
            return &sidxInfo[i];

    return (SidxInfoRec *)NULL;
}

bool checkSegmentBoundry(UInt64 offsetLow, UInt64 offsetHigh)
{
    UInt64 currentBoundry = 0;

    for(int i = 0 ; i < vg.segmentInfoSize ; i++)
    {
        currentBoundry += vg.segmentSizes[i];
        
        if(offsetLow < currentBoundry && offsetHigh >= currentBoundry)
            return true;
    }

    return false;
        
}

int getSegmentNumberByOffset(UInt64 offset)
{
    UInt64 currentBoundry = 0;

    for(int i = 0 ; i < (vg.segmentInfoSize - 1) ; i++)
    {
        currentBoundry += vg.segmentSizes[i];
        
        if(offset >= currentBoundry )
            return i;
    }

    return vg.segmentInfoSize;
        
}

void logtempInfo(MovieInfoRec *mir)
{
    FILE *leafInfoFile = fopen("sidxinfo.txt","wt");
    if(leafInfoFile == NULL)
    {
        printf("Error opening sidxinfo.txt, logging will not be done!\n");
        return;
    }
    
    fprintf(leafInfoFile,"%ld\n",mir->numTIRs);
    
    for(int i = 0 ; i < mir->numTIRs ; i++)
    {
        TrackInfoRec *tir = &(mir->tirList[i]);
        fprintf(leafInfoFile,"%lu\n",tir->mediaTimeScale);
    }
        
    
    for(int i = 0 ; i < mir->numTIRs ; i++)
    {
        TrackInfoRec *tir = &(mir->tirList[i]);

        UInt32 actualLeafCount = 0;

        for(UInt32 j = 0 ; j < tir->numLeafs ; j++)
            if(tir->leafInfo[j].hasFragments)
                actualLeafCount ++;
        
        fprintf(leafInfoFile,"%u\n",(unsigned int)actualLeafCount);
        
        for(UInt32 j = 0 ; j < tir->numLeafs ; j++)
            if(tir->leafInfo[j].hasFragments)
                fprintf(leafInfoFile,"%d, %llu, %llu\n",tir->leafInfo[j].firstInSegment,(UInt64)roundl(tir->leafInfo[j].earliestPresentationTime*(long double)tir->mediaTimeScale),tir->leafInfo[j].offset);
            
    }

    fclose(leafInfoFile);
}

void logLeafInfo(MovieInfoRec *mir)
{
    FILE *leafInfoFile = fopen("leafinfo.txt","wt");
    if(leafInfoFile == NULL)
    {
        printf("Error opening leafinfo.txt, logging will not be done!\n");
        return;
    }
    
    fprintf(leafInfoFile,"%lu\n",vg.accessUnitDurationNonIndexedTrack);

    fprintf(leafInfoFile,"%ld\n",mir->numTIRs);

    
    for(int i = 0 ; i < mir->numTIRs ; i++)
    {
        TrackInfoRec *tir = &(mir->tirList[i]);
        fprintf(leafInfoFile,"%lu %lu\n",tir->trackID,tir->hdlrInfo->componentSubType);
    }
        
    
    for(int i = 0 ; i < mir->numTIRs ; i++)
    {
        TrackInfoRec *tir = &(mir->tirList[i]);

        UInt32 actualLeafCount = 0;

        for(UInt32 j = 0 ; j < tir->numLeafs ; j++)
            if(tir->leafInfo[j].hasFragments)
                actualLeafCount ++;
        
        fprintf(leafInfoFile,"%u\n",(unsigned int)actualLeafCount);
        
        for(UInt32 j = 0 ; j < tir->numLeafs ; j++)
            if(tir->leafInfo[j].hasFragments)
                fprintf(leafInfoFile,"%d %Lf %Lf\n",tir->leafInfo[j].firstInSegment,tir->leafInfo[j].earliestPresentationTime,tir->leafInfo[j].lastPresentationTime);
            
    }

    fclose(leafInfoFile);

    logtempInfo(mir);
}



