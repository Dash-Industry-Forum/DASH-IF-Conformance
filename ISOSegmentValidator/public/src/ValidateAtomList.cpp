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


#include "ValidateMP4.h"
#include "HelperMethods.h"
#include "PostprocessData.h"


extern ValidateGlobals vg;

	// for use with ostypetostr_r() and int64todstr_r() for example;
	// when you're using one of these routines more than once in the same print statement
	char   tempStr1[32];
	char   tempStr2[32];
	char   tempStr3[32];
	char   tempStr4[32];
	char   tempStr5[32];
	char   tempStr6[32];
	char   tempStr7[32];
	char   tempStr8[32];
	char   tempStr9[32];
	char   tempStr10[32];


//==========================================================================================

OSErr ValidateFileAtoms( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	char *test = NULL;
	UInt32 atom_length = 0;
		
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
		
	atomprint("<atomlist>\n"); vg.tabcnt++;
	
	// Process 'ftyp' atom

	atomerr = ValidateAtomOfType( 'ftyp', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne | kTypeAtomFlagMustBeFirst, 
		Validate_ftyp_Atom, cnt, list, nil );
	if (!err) err = atomerr;
	
	// Process 'moov' atoms ; check for more than 1 moov atoms done later
	vg.mir = NULL;
	if (vg.cmaf) {
		atomerr = ValidateAtomOfType('moov', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne,
									 Validate_moov_Atom, cnt, list, nil);
		if (!err)
			err = atomerr;
	} else {
		atomerr = ValidateAtomOfType('moov', kTypeAtomFlagMustHaveOne,
									 Validate_moov_Atom, cnt, list, nil);
		if (!err)
			err = atomerr;
	}

	// Process 'meta' atoms
	atomerr = ValidateAtomOfType( 'meta', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_meta_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	if (!vg.mir)
		goto bail;
	
	// Count the total fragments and sidx's (if present), and allocate the required memory for that
	vg.mir->numFragments = 0;
	vg.mir->numSidx = 0;

	if(vg.mir->fragmented)
	{
		for (i = 0; i < cnt; i++)
		{
			if (list[i].type == 'sidx')
				vg.mir->numSidx++;
			
			if (list[i].type == 'moof')
				vg.mir->numFragments++;
		}
		
		vg.mir->moofInfo = (MoofInfoRec *)malloc(vg.mir->numFragments*sizeof(MoofInfoRec));
		vg.mir->processedFragments = 0;

		for (i = 0; i < (SInt32)vg.mir->numFragments ; i++)
		{
			vg.mir->moofInfo[i].compositionInfoMissingPerTrack = (Boolean*)malloc(vg.mir->numTIRs*sizeof(Boolean));
			vg.mir->moofInfo[i].moofEarliestPresentationTimePerTrack = (long double*)malloc(vg.mir->numTIRs*sizeof(long double));
			vg.mir->moofInfo[i].moofPresentationEndTimePerTrack = (long double*)malloc(vg.mir->numTIRs*sizeof(long double));
			vg.mir->moofInfo[i].moofLastPresentationTimePerTrack = (long double*)malloc(vg.mir->numTIRs*sizeof(long double));
			vg.mir->moofInfo[i].tfdt = (UInt64*)malloc(vg.mir->numTIRs*sizeof(UInt64));
		}

		vg.mir->sidxInfo = (SidxInfoRec *)malloc(vg.mir->numSidx*sizeof(SidxInfoRec));
		vg.mir->processedSdixs = 0;
	}
	else
	{
		vg.mir->moofInfo = NULL;
		vg.mir->sidxInfo = NULL;
	}

	int numMoovBoxes;

	numMoovBoxes = 0;
				
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		switch (entry->type) {
			case 'skip':
			case 'ssix':
			case 'free':
				break;
			case 'mdat':
					toggleprintsample( 1 );
		err = Validate_mdat_Atom(entry, NULL);
					//sampleprinthexandasciidata(test, atom_length);
					toggleprintsample( 0 );
		if (err) {
			fprintf(stderr, "<%s> : mdat atom read failed\n", __FUNCTION__);
				}
		BAILIFERR (err);
				break;

			case 'styp':
				atomerr = ValidateAtomOfType( 'styp', 0, 
					Validate_styp_Atom, cnt, list, nil );
				if (!err) err = atomerr;
				break;
			
			case 'uuid':
					atomerr = ValidateAtomOfType( 'uuid', 0, 
						Validate_uuid_Atom, cnt, list, nil );
					if (!err) err = atomerr;
					break;
					
			case 'emsg':
					atomerr = ValidateAtomOfType( 'emsg', 0, 
						Validate_emsg_Atom, cnt, list, nil );
					if (!err) err = atomerr;
					break;
					
			case 'moof':
					if(!vg.mir->fragmented)
						errprint("'moof' boxes are not to be expected without an 'mvex' in 'moov'\n");

					atomerr = ValidateAtomOfType( 'moof', 0, 
						Validate_moof_Atom, cnt, list, vg.mir);
					if (!err) err = atomerr;

					break;

			case 'sidx':
					if(!vg.mir->fragmented)
						errprint("'sidx' boxes are not to be expected in a non-fragmented movie\n");

					if(!vg.initializationSegment && !vg.dashInFtyp)
						errprint("'sidx' found for self-initializing media, violating ISO/IEC 23009-1:2012(E), 6.3.5.2: The Indexed Self-Initializing Media Segment ... shall carry 'dash' as a compatible brand. \n");
					
					atomerr = ValidateAtomOfType( 'sidx', 0, 
						Validate_sidx_Atom, cnt, list, vg.mir);
					if (!err) err = atomerr;
					
					break;

			case 'moov':

					// Don't allow multiple moov boxes except for self-initializing DASH
					bool dsmsFound;

					numMoovBoxes++;

					if(numMoovBoxes > 1)
					{
						dsmsFound = false;
		
						for(int index = 0 ; index < vg.segmentInfoSize ; index++)
							if( vg.dsms[index] == true )
								dsmsFound = true;

						if(!dsmsFound)
							errprint("Multiple 'moov' boxes are not allowed\n");
					}

					break;
			
			default:
				if (!(entry->aoeflags & kAtomValidated)) 
					warnprint("WARNING: In %s - unknown file atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
				break;
		}
		
		if (!err) err = atomerr;
	}
	
	//Some Processing like: check ordering to some extend (first sidx in segment is checked later while verifying indexing since it comes with
	//the checks for duration
	if(vg.dashSegment)
		checkDASHBoxOrder(cnt,list,vg.segmentInfoSize,vg.initializationSegment,vg.segmentSizes,vg.mir);
	
	if(vg.cmaf)
		checkCMAFBoxOrder(cnt,list,vg.segmentInfoSize, vg.initializationSegment, vg.segmentSizes);

  if(vg.mir->fragmented)
	postprocessFragmentInfo(vg.mir);
  
  estimatePresentationTimes(vg.mir);

   if(vg.dashSegment)
   {
		processSAP34(vg.mir);
		processIndexingInfo(vg.mir);
		if(vg.minBufferTime != -1)
			processBuffering(cnt,list,vg.mir);
		logLeafInfo(vg.mir);
   }
   
   

	goto exit_ok;
		
bail:
	fprintf(stderr, "BAILED\n");

exit_ok:
   --vg.tabcnt; atomprint("</atomlist>\n");
 	aoe->aoeflags |= kAtomValidated;
	if ( vg.mir != NULL) {
		dispose_mir(vg.mir);
	}

	return err;
}

//==========================================================================================

OSErr Validate_dinf_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'dref' atoms
	atomerr = ValidateAtomOfType( 'dref', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_dref_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In %s - unknown data information atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}
//==========================================================================================
OSErr Validate_edts_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	TrackInfoRec	*tir = (TrackInfoRec*)refcon;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'elst' atoms
	if(vg.cmaf) {
			atomerr = ValidateAtomOfType( 'elst', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
					Validate_elst_Atom, cnt, list, tir );
		}
		else {
			atomerr = ValidateAtomOfType( 'elst', kTypeAtomFlagCanHaveAtMostOne, 
					Validate_elst_Atom, cnt, list, tir );
		}
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In %s - unknown edit list atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}
//==========================================================================================

OSErr Validate_minf_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// deal with the different header atoms
	switch (tir->mediaType) {
		case 'vide':
			// Process 'vmhd' atoms
			atomerr = ValidateAtomOfType( 'vmhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
				Validate_vmhd_Atom, cnt, list, nil );
			if (!err) err = atomerr;
			break;
		
		case 'soun':
			// Process 'smhd' atoms
			atomerr = ValidateAtomOfType( 'smhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
				Validate_smhd_Atom, cnt, list, nil );
			if (!err) err = atomerr;
			break;
		
		case 'hint':
			// Process 'hmhd' atoms
			atomerr = ValidateAtomOfType( 'hmhd',kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
				Validate_hmhd_Atom, cnt, list, nil );
			if (!err) err = atomerr;
			break;
		
				case 'subt':
			// Process 'sthd' atoms
						if(vg.cmaf || vg.dvb || vg.hbbtv || vg.ctawave){
							atomerr = ValidateAtomOfType( 'sthd',kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
									Validate_sthd_Atom, cnt, list, nil );
							if (!err) err = atomerr;
						}
			break;
				
		case 'odsm':
		case 'sdsm':
			// Process 'nmhd' atoms
			atomerr = ValidateAtomOfType( 'nmhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
				Validate_nmhd_Atom, cnt, list, nil );
			if (!err) err = atomerr;
			break;
				case 'text':
						atomerr = ValidateAtomOfType( 'nmhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
				Validate_nmhd_Atom, cnt, list, nil );
			if (!err) err = atomerr;
			break;
		default:
			warnprint("WARNING: In %s - unknown media type '%s'\n",vg.curatompath, ostypetostr(tir->mediaType));
	}
				 //Explicit check for ac-4
		if(!strcmp(vg.codecs, "ac-4") && strcmp(ostypetostr(tir->mediaType),"soun"))
			warnprint("Warning: Media Information Header Box should contain Sound Media Header Box for 'ac-4'\n" );	

	// Process 'dinf' atoms
	atomerr = ValidateAtomOfType( 'dinf', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_dinf_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'stbl' atoms
	atomerr = ValidateAtomOfType( 'stbl', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stbl_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		switch (entry->type) {
			case 'odhd':
			case 'crhd':
			case 'sdhd':
			case 'm7hd':
			case 'ochd':
			case 'iphd':
			case 'mjhd':
				errprint("'%s' media type is reserved but not currently used\n", ostypetostr(entry->type));				
				//atomprint("<%s *****>\n",ostypetostr(entry->type));
				//atomprint("</%s>\n",ostypetostr(entry->type));
				break;
				
			default:
				warnprint("WARNING: In %s - unknown/unexpected atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
				//atomprint("<%s *****>\n",ostypetostr(entry->type));
				//atomprint("</%s>\n",ostypetostr(entry->type));
				break;
		}
		
		if (!err) err = atomerr;
	}
	
	//if(vg.cmaf)
		//	checkCMAFBoxOrder_minf(cnt,list);
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

//==========================================================================================

OSErr Validate_mdia_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'mdhd' atoms
	atomerr = ValidateAtomOfType( 'mdhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_mdhd_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'hdlr' atoms
	atomerr = ValidateAtomOfType( 'hdlr', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_mdia_hdlr_Atom, cnt, list, tir );
	if (!err) err = atomerr;
		
		// Process 'elng' atoms
		if(vg.cmaf){
			atomerr = ValidateAtomOfType( 'elng', kTypeAtomFlagCanHaveAtMostOne, 
					Validate_elng_Atom, cnt, list, tir );
			if (!err) err = atomerr;
		}

	// Process 'minf' atoms
	atomerr = ValidateAtomOfType( 'minf', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_minf_Atom, cnt, list, tir );
	if (!err) err = atomerr;
	
	// Process 'uuid' atoms
	atomerr = ValidateAtomOfType( 'uuid', 0, 
		Validate_uuid_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In unknown media atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	//if(vg.cmaf)
		//	checkCMAFBoxOrder_mdia(cnt,list);
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

//==========================================================================================

OSErr Get_trak_Type( atomOffsetEntry *aoe, TrackInfoRec *tir )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;

	SInt32 entrycnt;
	atomOffsetEntry *entrylist;
	atomOffsetEntry *entryentry;
	SInt32	j;
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		if (entry->type == 'mdia') {
			minOffset = entry->offset + entry->atomStartSize;
			maxOffset = entry->offset + entry->size - entry->atomStartSize;
			BAILIFERR( FindAtomOffsets( entry, minOffset, maxOffset, &entrycnt, &entrylist ) );
			for (j=0; j<entrycnt; ++j) {
				entryentry = &entrylist[j];
				if (entryentry->type == 'hdlr') {
					Get_mdia_hdlr_mediaType(entryentry, tir);
					break;
				}
			}
			break;
		}
	}

bail:
	return err;
}

//==========================================================================================

OSErr Validate_trak_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	TrackInfoRec	*tir = (TrackInfoRec*)refcon;
	
	atomprintnotab(">\n"); 
			
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'tkhd' atoms
	atomerr = ValidateAtomOfType( 'tkhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tkhd_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'tref' atoms
	atomerr = ValidateAtomOfType( 'tref', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tref_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'edts' atoms
	atomerr = ValidateAtomOfType( 'edts', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_edts_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'mdia' atoms
	atomerr = ValidateAtomOfType( 'mdia', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_mdia_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'udta' atoms
	atomerr = ValidateAtomOfType( 'udta', 0, 
		Validate_udta_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'uuid' atoms
	atomerr = ValidateAtomOfType( 'uuid', 0, 
		Validate_uuid_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'meta' atoms
	atomerr = ValidateAtomOfType( 'meta', 0, 
		Validate_meta_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In %s - unknown trak atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}


	// Extra checks
	switch (tir->mediaType) {
		case 'vide':
			if (tir->trackVolume) {
				errprint("Video track has non-zero trackVolume\n");
				err = badAtomSize;
			}
			if ((tir->trackWidth==0) || (tir->trackHeight==0)) {
				errprint("Video track has zero trackWidth and/or trackHeight\n");
				err = badAtomSize;
			}
			if (vg.checklevel >= checklevel_samples && !vg.dashSegment) {
				UInt64 sampleOffset;
				UInt32 sampleSize;
				UInt32 sampleDescriptionIndex;
				Ptr dataP = nil;
				BitBuffer bb;
				
				sampleprint("<vide_SAMPLE_DATA>\n"); vg.tabcnt++;
					for (i = 1; i <= (SInt32)tir->sampleSizeEntryCnt; i++) {
						if ((vg.samplenumber==0) || (vg.samplenumber==i)) {
							err = GetSampleOffsetSize( tir, i, &sampleOffset, &sampleSize, &sampleDescriptionIndex );
							sampleprint("<sample num=\"%d\" offset=\"%s\" size=\"%d\" />\n",i,int64toxstr(sampleOffset),sampleSize); vg.tabcnt++;
							BAILIFNIL( dataP = (Ptr)malloc(sampleSize), allocFailedErr );
							err = GetFileData( vg.fileaoe, dataP, sampleOffset, sampleSize, nil );
							
							BitBuffer_Init(&bb, (UInt8 *)((void *)dataP), sampleSize);

							Validate_vide_sample_Bitstream( &bb, tir );
							free( dataP );
							--vg.tabcnt; sampleprint("</sample>\n");
						}
					}
				--vg.tabcnt; sampleprint("</vide_SAMPLE_DATA>\n");
			}
			break;

		case 'soun':
			if (tir->trackWidth || tir->trackHeight) {
				errprint("Sound track has non-zero trackWidth and/or trackHeight\n");
				err = badAtomSize;
			}
			if (vg.checklevel >= checklevel_samples && !vg.dashSegment) {
				UInt64 sampleOffset;
				UInt32 sampleSize;
				UInt32 sampleDescriptionIndex;
				Ptr dataP = nil;
				BitBuffer bb;
				
				sampleprint("<audi_SAMPLE_DATA>\n"); vg.tabcnt++;
					for (i = 1; i <= (SInt32)tir->sampleSizeEntryCnt; i++) {
						if ((vg.samplenumber==0) || (vg.samplenumber==i)) {
							err = GetSampleOffsetSize( tir, i, &sampleOffset, &sampleSize, &sampleDescriptionIndex );
							sampleprint("<sample num=\"%d\" offset=\"%s\" size=\"%d\" />\n",i,int64toxstr(sampleOffset),sampleSize); vg.tabcnt++;
							BAILIFNIL( dataP = (Ptr)malloc(sampleSize), allocFailedErr );
							err = GetFileData( vg.fileaoe, dataP, sampleOffset, sampleSize, nil );
							
							BitBuffer_Init(&bb, (UInt8 *)dataP, sampleSize);

							Validate_soun_sample_Bitstream( &bb, tir );
							free( dataP );
							--vg.tabcnt; sampleprint("</sample>\n");
						}
					}
				--vg.tabcnt; sampleprint("</audi_SAMPLE_DATA>\n");
			}
			break;
			
		case 'odsm':
			if (tir->trackVolume || tir->trackWidth || tir->trackHeight) {
				errprint("ObjectDescriptor track has non-zero trackVolume, trackWidth, or trackHeight\n");
				err = badAtomSize;
			}
			if (vg.checklevel >= checklevel_samples && !vg.dashSegment) {
				UInt64 sampleOffset;
				UInt32 sampleSize;
				UInt32 sampleDescriptionIndex;
				Ptr dataP = nil;
				BitBuffer bb;
				
				sampleprint("<odsm_SAMPLE_DATA>\n"); vg.tabcnt++;
				for (i = 1; i <= (SInt32)tir->sampleSizeEntryCnt; i++) {
					if ((vg.samplenumber==0) || (vg.samplenumber==i)) {
						err = GetSampleOffsetSize( tir, i, &sampleOffset, &sampleSize, &sampleDescriptionIndex );
						sampleprint("<sample num=\"%d\" offset=\"%s\" size=\"%d\" />\n",1,int64toxstr(sampleOffset),sampleSize); vg.tabcnt++;
							BAILIFNIL( dataP = (Ptr)malloc(sampleSize), allocFailedErr );
							err = GetFileData( vg.fileaoe, dataP, sampleOffset, sampleSize, nil );
							
							BitBuffer_Init(&bb, (UInt8 *)dataP, sampleSize);

							Validate_odsm_sample_Bitstream( &bb, tir );
							free( dataP );
						--vg.tabcnt; sampleprint("</sample>\n");
					}
				}
				--vg.tabcnt; sampleprint("</odsm_SAMPLE_DATA>\n");
			}
			break;

		case 'sdsm':
			if (tir->trackVolume || tir->trackWidth || tir->trackHeight) {
				errprint("SceneDescriptor track has non-zero trackVolume, trackWidth, or trackHeight\n");
				err = badAtomSize;
			}
			if (vg.checklevel >= checklevel_samples && !vg.dashSegment) {
				UInt64 sampleOffset;
				UInt32 sampleSize;
				UInt32 sampleDescriptionIndex;
				Ptr dataP = nil;
				BitBuffer bb;
				sampleprint("<sdsm_SAMPLE_DATA>\n"); vg.tabcnt++;
				for (i = 1; i <= (SInt32)tir->sampleSizeEntryCnt; i++) {
					if ((vg.samplenumber==0) || (vg.samplenumber==i)) {
						err = GetSampleOffsetSize( tir, i, &sampleOffset, &sampleSize, &sampleDescriptionIndex );
						sampleprint("<sample num=\"%d\" offset=\"%s\" size=\"%d\" />\n",1,int64toxstr(sampleOffset),sampleSize); vg.tabcnt++;
							BAILIFNIL( dataP = (Ptr)malloc(sampleSize), allocFailedErr );
							err = GetFileData( vg.fileaoe, dataP, sampleOffset, sampleSize, nil );
							
							BitBuffer_Init(&bb, (UInt8 *)dataP, sampleSize);

							Validate_sdsm_sample_Bitstream( &bb, tir);
							free( dataP );
						--vg.tabcnt; sampleprint("</sample>\n");
					}
				}
				--vg.tabcnt; sampleprint("</sdsm_SAMPLE_DATA>\n");
			}
			break;

		case 'hint':
			Validate_Hint_Track(aoe, tir);
			break;

		default:
			if (tir->trackVolume || tir->trackWidth || tir->trackHeight) {
				errprint("Non-visual/audio track has non-zero trackVolume, trackWidth, or trackHeight\n");
				err = badAtomSize;
			}
			break;
	}
	
	//if(vg.cmaf)
		//	checkCMAFBoxOrder_trak(cnt,list);
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}
//==========================================================================================

OSErr Validate_stbl_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

	tir->identicalDecCompTimes = true;
	
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		if (entry->type == 'ctts')
			tir->identicalDecCompTimes = false; //Section 8.6.1.1.
	}
	
	// Process 'stsd' atoms
	atomerr = ValidateAtomOfType( 'stsd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stsd_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stts' atoms
	atomerr = ValidateAtomOfType( 'stts', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stts_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'ctts' atoms
	atomerr = ValidateAtomOfType( 'ctts', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_ctts_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stss' atoms
	atomerr = ValidateAtomOfType( 'stss', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stss_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stsc' atoms
	atomerr = ValidateAtomOfType( 'stsc', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stsc_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stsz' atoms
	atomerr = ValidateAtomOfType( 'stsz', /* kTypeAtomFlagMustHaveOne | */  kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stsz_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stz2' atoms;  we need to check there is one stsz or one stz2 but not both...
	atomerr = ValidateAtomOfType( 'stz2', /* kTypeAtomFlagMustHaveOne | */  kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stz2_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stco' atoms
	atomerr = ValidateAtomOfType( 'stco', /* kTypeAtomFlagMustHaveOne | */ kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stco_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'co64' atoms
	atomerr = ValidateAtomOfType( 'co64', /* kTypeAtomFlagMustHaveOne | */ kTypeAtomFlagCanHaveAtMostOne, 
		Validate_co64_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stsh' atoms	- shadow sync
	atomerr = ValidateAtomOfType( 'stsh', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stsh_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'stdp' atoms	- degradation priority
	atomerr = ValidateAtomOfType( 'stdp', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_stdp_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'sdtp' atoms	- sample dependency
	atomerr = ValidateAtomOfType( 'sdtp', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_sdtp_Atom, cnt, list, tir );
	if (!err) err = atomerr;

	// Process 'padb' atoms
	atomerr = ValidateAtomOfType( 'padb', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_padb_Atom, cnt, list, tir );
	if (!err) err = atomerr;
	
	// Process 'sgpd' atoms
	if (0) {
		UInt32 moofIndex = getMoofIndexByOffset(vg.mir->moofInfo, vg.mir->numFragments, aoe->offset);
		MoofInfoRec *moofInfoRec = &vg.mir->moofInfo[moofIndex];
		atomerr = ValidateAtomOfType( 'sgpd', kTypeAtomFlagCanHaveAtMostOne, 
			Validate_sgpd_Atom, cnt, list, &moofInfoRec->trafInfo[moofInfoRec->processedTrackFragments]);
		if (!err) err = atomerr;
	}

	// Process 'subs' atoms
	if(vg.cmaf){
		atomerr = ValidateAtomOfType( 'subs', 0, 
				Validate_subs_Atom, cnt, list, tir );
		if (!err) err = atomerr;
	}

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In %s - unknown sample table atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	if (tir->sampleSizeEntryCnt != tir->timeToSampleSampleCnt) {
		errprint("Number of samples described by SampleSize table ('stsz') does NOT match"
				 " number of samples described by TimeToSample table ('stts') \n");
		err = badAtomErr;
	}
	if (!vg.dashSegment && tir->mediaDuration != tir->timeToSampleDuration) {
	
		errprint("Media duration (%s) in MediaHeader does NOT match"
				 " sum of durations described by TimeToSample table (%s) \n", 
				  int64todstr_r( tir->mediaDuration, tempStr1 ),
				  int64todstr_r( tir->timeToSampleDuration, tempStr2 ));
		err = badAtomErr;
	}
	if (tir->sampleToChunk) {

		if(tir->sampleToChunkEntryCnt)
		{
			UInt32 s;		// number of samples
			UInt32 leftover;

			if (tir->sampleToChunk[tir->sampleToChunkEntryCnt].firstChunk 
				> tir->chunkOffsetEntryCnt) {
				errprint("SampleToChunk table describes more chunks than"
						 " the ChunkOffsetTable table\n");
				err = badAtomErr;
			} 
			
			s = tir->sampleSizeEntryCnt - tir->sampleToChunkSampleSubTotal;
			leftover = s % (tir->sampleToChunk[tir->sampleToChunkEntryCnt].samplesPerChunk);
			if (leftover) {
				errprint("SampleToChunk table does not evenly describe"
						 " the number of samples as defined by the SampleToSize table\n");
				err = badAtomErr;
			}
		}
		else if(!vg.dashSegment)
			warnprint("WARNING: STSC empty; with an empty STSC atom, chunk mapping is not verifiable\n");
	}
	
	//if(vg.cmaf)
		//	checkCMAFBoxOrder_stbl(cnt,list);

	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}
//==========================================================================================

OSErr Validate_mvex_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

	vg.mir->fragmented = true;
	vg.mir->sequence_number = 0;

	/*Section 8.8.3.1, Quantity:   Exactly one for each track in the Movie Box
	  Doesnt say they have to be in order, so we have to manually check it.
	  Since bit(4)	reserved=0, setting default_sample_flags is set to a test exception
	  Not the cleanest approach though*/
	  
	for(i = 0 ; i < vg.mir->numTIRs; i++)
	{
		tir[i].default_sample_flags = 0xFFFFFFFF;
	}

	//todo: add optional 'leva' boxes
	if(vg.subRepLevel && vg.initializationSegment)
	{
		bool levaFound = false;
		
		for (i = 0; i < cnt; i++) {
			entry = &list[i];
			if(entry->type == 'leva')
				levaFound = true;
		}

		if(!levaFound)
			errprint("leva box not found in intialization segment, violating: ISO/IEC 23009-1:2012(E), 7.3.4: The Initialization Segment shall contain the Level Assignment ('leva') box");
		
	}
	// Process 'mehd' atoms
	atomerr = ValidateAtomOfType( 'mehd', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_mehd_Atom, cnt, list, vg.mir );
	if (!err) err = atomerr;
	
	// Process 'trex' atoms
	atomerr = ValidateAtomOfType( 'trex', kTypeAtomFlagMustHaveOne, 
		Validate_trex_Atom, cnt, list, tir );
	if (!err) err = atomerr;
		
		// Process 'trep' atoms
		if(vg.dvb || vg.hbbtv){
			atomerr = ValidateAtomOfType( 'trep', 0, 
					Validate_trep_Atom, cnt, list, tir );
			if (!err) err = atomerr;
		}

	/*Now check if any track information is missing*/
	for(i = 0 ; i < vg.mir->numTIRs ; i++)
	{
		if(tir[i].default_sample_flags == 0xFFFFFFFF)
			errprint("'mxvex' found but 'trex' box missing for track %d\n",i);
	}

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In %s - unknown mvex atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}

	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}


//==========================================================================================

OSErr ValidateAtomOfType( OSType theType, SInt32 flags, ValidateAtomTypeProcPtr validateProc, 
		SInt32 cnt, atomOffsetEntry *list, void *refcon )
{
	SInt32 i;
	OSErr err = noErr;
	char cstr[5] = {};
	SInt32 typeCnt = 0;
	atomOffsetEntry *entry;
	OSErr atomerr;
	atompathType curatompath;
	Boolean curatomprint;
	Boolean cursampleprint;
	Boolean traf_exists = false;
	SInt32 traf_cnt = 0;
	
	cstr[0] = (theType >> 24) & 0xff;
	cstr[1] = (theType >> 16) & 0xff;
	cstr[2] = (theType >>  8) & 0xff;
	cstr[3] = (theType >>  0) & 0xff;
	
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		
		if (entry->aoeflags & kAtomValidated) continue;
		
		if ((entry->type == theType) && ((entry->aoeflags & kAtomSkipThisAtom) == 0)) {
			if ((flags & kTypeAtomFlagCanHaveAtMostOne) && (typeCnt > 1)) {
				errprint("Multiple '%s' atoms not allowed\n", cstr);
			}
			if ((flags & kTypeAtomFlagMustBeFirst) && (i>0)) {
								if(vg.cmaf){
									if(theType=='ftyp')
										errprint("CMAF check violated: Section 7.3.1. \"The CMAF Header SHALL start with a FileTypeBox.\", but actually found at position %d", i+1);
									if(theType =='mvhd')
										errprint("CMAF check violated: Section 7.3.1. \"The MovieBox SHALL start with a MovieHeaderBox.\", but actually found at position %d", i+1);
								}
				if (i==1) warnprint("Warning: atom %s before ftyp atom MUST be a signature\n",ostypetostr((&list[0])->type));
				else errprint("Atom %s must be first and is actually at position %d\n",ostypetostr(theType),i+1);
			}			
			typeCnt++;
			
			if(vg.cmaf){
				if(theType == 'traf'){
					traf_exists = true;
					traf_cnt = typeCnt;
				}
				if(traf_exists && theType == 'tfdt'){
					if(typeCnt - traf_cnt == 0)
						traf_exists = false;
					else
						errprint("CMAF check violated: Section 7.5.16. \"Every Track Fragment Box SHALL contain a Track Fragment Decode Time Box\", but 'traf' at position %d has none.\n", i);
				}
			}
			  
			addAtomToPath( vg.curatompath, theType, typeCnt, curatompath );
			if (vg.print_atompath) {
				fprintf(stdout,"%s\n", vg.curatompath);
			}
			curatomprint = vg.printatom;
			cursampleprint = vg.printsample;
			if ((vg.atompath[0] == 0) || (strcmp(vg.atompath,vg.curatompath) == 0)) {
				if (vg.print_atom)
					vg.printatom = true;
				if (vg.print_sample)
					vg.printsample = true;
			}
			atomprint("<%s",cstr); vg.tabcnt++;
				atomerr = CallValidateAtomTypeProc(validateProc, entry, 
											entry->refconOverride?((void*) (entry->refconOverride)):refcon);
			--vg.tabcnt; atomprint("</%s>\n",cstr);
			vg.printatom = curatomprint;
			vg.printsample = cursampleprint;
			restoreAtomPath( vg.curatompath, curatompath );
			if (!err) err = atomerr;
		}
	}

	// 
	if ((flags & kTypeAtomFlagMustHaveOne)  && (typeCnt == 0)) {
		if( theType == IODSAID ) {
//			warnprint( "\nWARNING: no 'iods' atom\n");
		} else {
			errprint("No '%s' atoms\n",cstr);
		}
		
		if( vg.cmaf){
					if(theType =='moov')
			errprint("CMAF check violated: Section 7.3.1. \"CMAF Header SHALL include one MovieBox.\", found %d 'moov' box\n", typeCnt);
					if(theType =='trex')
			errprint("CMAF check violated: Section 7.5.14. \"Track Extends Boxes SHALL be present in a CMAF Track\", found %d\n", typeCnt);
					if(theType =='trak')
			errprint("CMAF check violated: Section 7.3.1. \"The MovieBox SHALL contain exactly one track containing media data.\", found %d\n", typeCnt);
					if(theType =='mfhd')
						errprint("CMAF check violated: Section 7.3.2.4. \"Each CMAF Fragment SHALL contain a MovieFragmentHeaderBox.\", found %d\n", typeCnt);
					if(theType =='mvex')
						errprint("CMAF check violated: Section 7.3.1. \"The MovieBox SHALL contain a MovieExtendsBox.\", found %d\n", typeCnt);
					if(theType =='tenc')
						errprint("CMAF check violated: Section 7.3.1. \"The SchemeInformationbox SHALL contain a TrackEncryptionBox.\", found %d\n", typeCnt);
					if(theType =='dref')
						errprint("CMAF check violated: Section 7.3.1. \"There SHALL be a Data Reference Box in Data Information Box.\", found %d\n", typeCnt);
					if(theType =='vmhd')
						errprint("CMAF check violated: Section 7.3.1. \"The Media Information Box SHALL contain a Video Media Header for media type video\", found %d\n", typeCnt);
					if(theType =='smhd')
						errprint("CMAF check violated: Section 7.3.1. \"The Media Information Box SHALL contain a Sound Media Header for media type audio\", found %d\n", typeCnt);
					if(theType =='sthd')
						errprint("CMAF check violated: Section 7.3.1. \"The Media Information Box SHALL contain a Subtitle Media Header for media type subtitle\", found %d\n", typeCnt);
					if(theType =='saio')
						errprint("CMAF check violated: Section 8.2.2.1. \"For encrypted CMAF Fragments that contain Sample Auxiliary Information, each TrackFragmentBox SHALL contain a 'saio'\", found %d\n", typeCnt);
					
		}
	} else if ((flags & kTypeAtomFlagCanHaveAtMostOne) && (typeCnt > 1)) {
				if(vg.cmaf){
					if(theType =='moov')
			errprint("CMAF check violated: Section 7.3.1. \"CMAF Header SHALL include one MovieBox.\", found %d 'moov' box\n", typeCnt);
					if(theType =='trak')
						errprint("CMAF check violated: Section 7.3.1. \"The MovieBox SHALL contain exactly one track containing media data.\", found %d\n", typeCnt);
					if(theType =='mfhd')
						errprint("CMAF check violated: Section 7.3.2.3. \"Each CMAF Chunk/Fragment SHALL contain a MovieFragmentHeaderBox.\", found %d\n", typeCnt);
					if(theType =='mvex')
						errprint("CMAF check violated: Section 7.3.1 \"The MovieBox SHALL contain a MovieExtendsBox.\", found %d\n", typeCnt);
					if(theType =='tenc')
						errprint("CMAF check violated: Section 7.3.1. \"The SchemeInformationbox SHALL contain a TrackEncryptionBox.\", found %d\n", typeCnt);
					if(theType =='dref')
						errprint("CMAF check violated: Section 7.3.1. \"There SHALL be a Data Reference Box in Data Information Box.\", found %d\n", typeCnt);
					if(theType =='vmhd')
						errprint("CMAF check violated: Section 7.3.1. \"The Media Information Box SHALL contain a Video Media Header for media type video\", found %d\n", typeCnt);
					if(theType =='smhd')
						errprint("CMAF check violated: Section 7.3.1. \"The Media Information Box SHALL contain a Sound Media Header for media type audio\", found %d\n", typeCnt);
					if(theType =='sthd')
						errprint("CMAF check violated: Section 7.3.1. \"The Media Information Box SHALL contain a Subtitle Media Header for media type subtitle\", found %d\n", typeCnt);
				}
		errprint("Multiple '%s' atoms not allowed\n",cstr);
	}

	return err;
}


//==========================================================================================

int mapStringToUInt32(char *src, UInt32 *target);


OSErr Validate_ftyp_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset = 0;
	OSType majorBrand = 0;
	UInt32 version = 0;
	UInt32 compatBrandListSize = 0, numCompatibleBrands = 0;
	char tempstr1[5] = {}, tempstr2[5] = {};
	
	offset = aoe->offset + aoe->atomStartSize;
	
	BAILIFERR( GetFileDataN32( aoe, &majorBrand, offset, &offset ) );
	BAILIFERR( GetFileDataN32( aoe, &version, offset, &offset ) );

	atomprintnotab("\tmajorbrand=\"%.4s\" version=\"%s\" compatible_brands='[\n", ostypetostr_r(majorBrand, tempstr1), 
						int64toxstr((UInt64) version));

	vg.majorBrand = majorBrand;
	if( majorBrand == brandtype_isom ) {
		// the isom can only be a compatible brand
		errprint("The brand 'isom' can only be a compatible, not major, brand\n");
	}
	
	if(vg.cmaf){
			if((majorBrand=='cmfc' || majorBrand=='cmf2') && version != 0)
				errprint("CMAF Check violated : Section 7.2. \"If any of the structural CMAF brands is the major_brand, the minor_version SHALL be 0.\", found %ld\n",version);
		}
	
	compatBrandListSize = (UInt32)(aoe->size - 8 - aoe->atomStartSize);
	numCompatibleBrands = compatBrandListSize / sizeof(OSType);
	
	if (0 != (compatBrandListSize % sizeof(OSType))) {
		errprint("FileType compatible brands array has leftover %d bytes\n", compatBrandListSize % sizeof(OSType));
	}
	if (numCompatibleBrands <= 0) {
		// must have at least one compatible brand, it must be the major brand
		errprint("There must be at least one compatible brand\n");
	}
	else {
		UInt32 ix;
		OSType currentBrand;
		Boolean majorBrandFoundAmongCompatibleBrands = false;
		vg.msixInFtyp = false;
		vg.dashInFtyp = false;
				
		for (ix=0; ix < numCompatibleBrands; ix++) {
			BAILIFERR( GetFileDataN32( aoe, &currentBrand, offset, &offset ) );
			if (ix<(numCompatibleBrands-1)) atomprint(" \"%s\" \n", ostypetostr_r(currentBrand, tempstr1));
				  else atomprint(" \"%s\"\n",  ostypetostr_r(currentBrand, tempstr1));
			
			if (majorBrand == currentBrand) {
				majorBrandFoundAmongCompatibleBrands = true;
			}
			if (currentBrand == 'dash')
			{
				vg.dashInFtyp = true;
				vg.dashSegment = true;
			}
			else if (currentBrand == 'msdh') {  //Although expected in styp, it seems conforming to slip it in the initialization segment. We should use this information.
				vg.dashSegment = true;
			}
			else if (currentBrand == 'msix')
			{
				vg.msixInFtyp = true;
				vg.dashSegment = true;
			}
			else if(currentBrand == 'dsms') {
				vg.dsms[0] = true;
				vg.dashSegment = true;
			}
			else if(currentBrand == 'cmfc'  || currentBrand == 'cmf2'){
				vg.dashSegment = true; // Equivalent to CMAF Fragment. Can be directly used in CMAF Fragment conformances.
				//vg.cmaf = true; //Niteesh: This might not be required if -cmaf is passed as an arg.
			}
			
		}

		if (!majorBrandFoundAmongCompatibleBrands) {
				warnprint("Warning: major brand ('%.4s') not also found in list of compatible brands\n", 
							 ostypetostr_r(majorBrand,tempstr2));
			}

			
 	}
 	
 	atomprint("]'>\n"); 
	
	aoe->aoeflags |= kAtomValidated;

bail:
	return noErr;
}

OSErr Validate_styp_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	OSType majorBrand;
	UInt32 version;
	UInt32 compatBrandListSize, numCompatibleBrands;
	char tempstr1[5], tempstr2[5];
	
	
	offset = aoe->offset + aoe->atomStartSize;
	
	BAILIFERR( GetFileDataN32( aoe, &majorBrand, offset, &offset ) );
	BAILIFERR( GetFileDataN32( aoe, &version, offset, &offset ) );

	atomprintnotab(" majorbrand=\"%.4s\" version=\"%s\" compatible_brands='[\n", ostypetostr_r(majorBrand, tempstr1), 
						int64toxstr((UInt64) version));

	vg.majorBrand = majorBrand;
	if( majorBrand == brandtype_isom ) {
		// the isom can only be a compatible brand
		errprint("The brand 'isom' can only be a compatible, not major, brand\n");
	}
	
	compatBrandListSize = (UInt32)(aoe->size - 8 - aoe->atomStartSize);
	numCompatibleBrands = compatBrandListSize / sizeof(OSType);
	
	if (0 != (compatBrandListSize % sizeof(OSType))) {
		errprint("FileType compatible brands array has leftover %d bytes\n", compatBrandListSize % sizeof(OSType));
	}
	if (numCompatibleBrands <= 0) {
		// must have at least one compatible brand, it must be the major brand
		errprint("There must be at least one compatible brand\n");
	}
	else {
		UInt32 ix;
		OSType currentBrand;
		Boolean majorBrandFoundAmongCompatibleBrands = false;
		Boolean lmsgFoundInCompatibleBrands = false;
		bool msdhFound = false;
		bool msixFound = false;

		//Which segment is it?
		int segmentNum;
		bool segmentFound = false;
		UInt64 offset = 0;
		for(segmentNum = 0 ; segmentNum < vg.segmentInfoSize ; segmentNum++)
		{	   
			if(aoe->offset == offset)
			{
				segmentFound = true;
				break;
			}
			
			offset += vg.segmentSizes[segmentNum];
		}

		if(segmentFound)
			vg.simsInStyp[segmentNum] = false;

		if(!segmentFound)
			errprint("styp not at the begining of a segment (abs. file offset %ld), this is unexpected\n",aoe->offset);
					
		/*skip styp size, tag, major brand and version*/
		offset += 16;
		for (ix=0; ix < numCompatibleBrands; ix++) {
			BAILIFERR( GetFileDataN32( aoe, &currentBrand, offset, &offset ) );
			if (ix<(numCompatibleBrands-1)) atomprint("\"%s\",\n", ostypetostr_r(currentBrand, tempstr1));
				  else atomprint("\"%s\"\n",  ostypetostr_r(currentBrand, tempstr1));
			
			if (majorBrand == currentBrand) {
				majorBrandFoundAmongCompatibleBrands = true;
			}
						
			if (currentBrand == 'msdh') {
				msdhFound = true;
				vg.dashSegment = true;
			}
			else if(currentBrand == 'msix') {
				msixFound = true;
				vg.dashSegment = true;
			}
			else if(segmentFound && currentBrand == 'sims') {
				vg.simsInStyp[segmentNum] = true;
				vg.dashSegment = true;
			}
			else if(segmentFound && currentBrand == 'dsms') {
				vg.dsms[segmentNum] = true;
				vg.dashSegment = true;
			}
			else if(currentBrand == 'lmsg') {
				vg.dashSegment = true;
				lmsgFoundInCompatibleBrands = true;
				if(segmentFound && segmentNum != (vg.segmentInfoSize-1))
					errprint("Brand 'lmsg' found as a compatible brand for segment number %d (not the last segment %d); violates ISO/IEC 23009-1:2012(E), 7.3.1: In all cases for which a Representation contains more than one Media Segment ... If the Media Segment is not the last Media Segment in the Representation, the 'lmsg' compatibility brand shall not be present.\n",segmentNum+1,vg.segmentInfoSize);
			}
			else if(currentBrand == 'cmfc'  || currentBrand == 'cmf2'){
								vg.dashSegment = true; // Equivalent to CMAF Fragment. Can be directly used in CMAF Fragment conformances.
				//vg.cmaf = true; //Niteesh: This might not be required if -cmaf is passed as an arg.
			}
			else if(currentBrand == 'cmfs'){
							vg.cmafSegment = true; // To be used for CMAF Segment conformances.
						}
			else if(currentBrand == 'cmfl'){
							vg.cmafChunk = true;// To be used for CMAF Chunk conformances.
						}
			else if(currentBrand == 'cmff'){
							vg.cmafFragment = true; // To be used for CMAF Fragment conformances
						}
						
		}

		if (!majorBrandFoundAmongCompatibleBrands) {
				errprint("major brand ('%.4s') not also found in list of compatible brands\n", 
							 ostypetostr_r(majorBrand,tempstr2));
			}

		if (segmentFound && (segmentNum == (vg.segmentInfoSize - 1)) && (vg.dash264base || vg.dashifbase) && (vg.dynamic || vg.isoLive) && !lmsgFoundInCompatibleBrands) {
			if (segmentFound && segmentNum != vg.segmentInfoSize)
				warnprint("Warning: Brand 'lmsg' not found as a compatible brand for the last segment (number %d); violates Section 3.2.3. of Interoperability Point DASH264: If the MPD@type is equal to \"dynamic\" or if it includes MPD@profile attribute in-cludes \"urn:mpeg:dash:profile:isoff-live:2011\", then: if the Media Segment is the last Media Segment in the Representation, this Me-dia Segment shall carry the 'lmsg' compatibility brand\n", segmentNum + 1);
		}

		if (!msdhFound) {
				errprint("Brand msdh not found as a compatible brand; violates ISO/IEC 23009-1:2012(E), 6.3.4.2\n");
			}
		
		if (!msixFound && (vg.mir->numSidx > 0)) {
				warnprint("Warning: msix not found in styp of a segment, while indxing info found, violating: ISO/IEC 23009-1:2012(E), 6.3.4.3: Each Media Segment shall carry 'msix' as a compatible brand \n");
			}

		if (vg.isomain && (vg.startWithSAP <= 0 || vg.startWithSAP > 3) && !msixFound)
			errprint("msix not found in styp of a segment, with main profile and startWithSAP %d, violating: ISO/IEC 23009-1:2012(E), 8.5.3: Each Media Segment of the Representations not having @startWithSAP present or having @startWithSAP value 0 or greater than 3 shall comply with the formats defined in 6.3.4.3, i.e. the brand 'msix'\n",vg.startWithSAP);
		
/*
		if (vg.checklevel && segmentFound && !vg.simsInStyp[segmentNum]) {
				errprint("sims not found in styp of a segment, while SubRepresentation@level checks invoked, violating: Section 7.3.4. of ISO/IEC 23009-1:2012(E): If a SubRepresentation element is present in a Representation in the MPD and the attribute SubRepresentation@level is present, then the Media Segments in this Representation shall conform to a Sub-Indexed Media Segment as defined in 6.3.4.4 \n");
			}
*/	
 	}
 	
 	atomprint("]'>\n"); 
	
	aoe->aoeflags |= kAtomValidated;

bail:
	return noErr;
}


typedef struct track_track {
	UInt32 chunk_num;
	UInt32 chunk_cnt;
} track_track;

OSErr Validate_moov_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	SInt32 trakCnt = 0;
	SInt32 thisTrakIndex = 0;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	MovieInfoRec		*mir = NULL;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

	if(vg.initializationSegment && ((aoe->offset + aoe->size) > vg.segmentSizes[0]))
		errprint("Complete moov not found in initialization segment: ISO/IEC 23009-1:2012(E), 6.3.3: The Initialization Segment shall contain an \"ftyp\" box, and a \"moov\" box\n");	

	// find out how many tracks we have so we can allocate our struct. Also check if we have encryption-related boxes
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		if (entry->type == 'trak') {
			++trakCnt;
		}
		if (entry->type == 'pssh') {
			vg.psshInInit = true;
		}
		if (entry->type == 'tenc') {
			vg.tencInInit = true;
		}
	}
	
	if (trakCnt > 0) {
		i = (trakCnt-1) * sizeof(TrackInfoRec);
	} else {
		i = 0;
	}

	BAILIFNIL( vg.mir = (MovieInfoRec	*)calloc(1, sizeof(MovieInfoRec) + i), allocFailedErr );
	mir = vg.mir;
	mir->fragmented = false; //unless 'mvex' is found in 'moov'
	
		if(vg.cmaf){
			atomerr = ValidateAtomOfType( 'mvhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne | kTypeAtomFlagMustBeFirst, 
		Validate_mvhd_Atom, cnt, list, mir);
			if (!err) err = atomerr;
			
		}
		else{
	atomerr = ValidateAtomOfType( 'mvhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_mvhd_Atom, cnt, list, mir);
	if (!err) err = atomerr;
		}


	// pre-process 'trak' atoms - get the track types
	// set refconOverride so Validate_trak_Atom gets a tir
	thisTrakIndex = 0;
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		if (entry->type == 'trak') {
			++(mir->numTIRs);
			atomerr = Get_trak_Type(entry, &(mir->tirList[thisTrakIndex]));
			entry->refconOverride = (void*)&(mir->tirList[thisTrakIndex]);
			++thisTrakIndex;
		}
	}
		
	// disable processing hint 'trak' atoms
	//   adding ability to flag a text track to avoid reporting error when its matrix is non-identity
	thisTrakIndex = 0;
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		if (entry->type == 'trak') {
			if (mir->tirList[thisTrakIndex].mediaType == 'hint') {
				entry->aoeflags |= kAtomSkipThisAtom;
			}
			//	need to pass info that this is a text track to ValidateAtomOfType 'trak' below (refcon arg doesn't seem to work)
	
// ����
			++thisTrakIndex;
		}
	}


	// Process non-hint 'trak' atoms
	if(vg.cmaf){
			atomerr = ValidateAtomOfType( 'trak', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
					Validate_trak_Atom, cnt, list, nil );
			if (!err) err = atomerr;
		}
		
	atomerr = ValidateAtomOfType( 'trak', 0, Validate_trak_Atom, cnt, list, nil );
	if (!err) err = atomerr;


	// enable processing hint 'trak' atoms
	thisTrakIndex = 0;
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		if (entry->type == 'trak') {
			if (mir->tirList[thisTrakIndex].mediaType == 'hint') {
				entry->aoeflags &= ~kAtomSkipThisAtom;
			}

// ����
			++thisTrakIndex;
		}
	}


	// Process hint 'trak' atoms
	atomerr = ValidateAtomOfType( 'trak', 0, Validate_trak_Atom, cnt, list, nil );
	if (!err) err = atomerr;
	
	// Process 'mvex' atoms
		if(vg.cmaf){
			atomerr = ValidateAtomOfType( 'mvex', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
					Validate_mvex_Atom, cnt, list, mir->tirList );
			if (!err) err = atomerr;
		}
		else{
			atomerr = ValidateAtomOfType( 'mvex', kTypeAtomFlagCanHaveAtMostOne, 
					Validate_mvex_Atom, cnt, list, mir->tirList );
			if (!err) err = atomerr;
		}

	// Process 'iods' atoms
	atomerr = ValidateAtomOfType( 'iods', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_iods_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'udta' atoms
	atomerr = ValidateAtomOfType( 'udta', 0, 
		Validate_udta_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'uuid' atoms
	atomerr = ValidateAtomOfType( 'uuid', 0, 
		Validate_uuid_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'meta' atoms
	atomerr = ValidateAtomOfType( 'meta', 0, 
		Validate_meta_Atom, cnt, list, nil );
	if (!err) err = atomerr;
		
	// Process 'pssh' atoms
	atomerr = ValidateAtomOfType( 'pssh', 0, 
		Validate_pssh_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++){
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		switch (entry->type) {
			case 'mdat':
			case 'skip':
			case 'free':
				break;
				
			case 'wide':	// this guy is QuickTime specific
			// �� if !qt, mpeg may be unfamiliar
				break;
				
			default:
				warnprint("WARNING: In %s - unknown movie atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
				break;
		}
		
		if (!err) err = atomerr;
	}
	
	for (i=0; i<mir->numTIRs; ++i) {
			TrackInfoRec *tir;
			UInt8 all_single;\
			UInt32 j;
			
			tir = &(mir->tirList[i]);
			all_single = 1;

			tir->numLeafs = 0;
			tir->leafInfo = NULL;
			
			if (tir->chunkOffsetEntryCnt > 1) {
				for (j=1; j<=tir->sampleToChunkEntryCnt; j++) {
					if (tir->sampleToChunk[j].samplesPerChunk > 1) 
						{ all_single = 0; break; }
				}
				if (all_single == 1) warnprint("Warning: track %d has %d chunks all containing 1 sample only\n",
												i,tir->chunkOffsetEntryCnt );
			}
		}
		
	// Check for overlapped sample chunks [dws]
	//  this re-write relies on the fact that most tracks are in offset order and most files behave;
	//  we pick the track with the lowest unprocessed chunk offset;
	//  if that is beyond the highest chunk end we have seen, we append it;  otherwise (the rare case)
	//   we insert it into the sorted list.  this gives us a rapid check and an output sorted list without
	//   an n-squared overlap check and without a post-sort
	if(!vg.dashSegment)
	{
		UInt32 totalChunks = 0;
		TrackInfoRec *tir;
		UInt64 highwatermark;
		UInt32 trk_cnt, topslot = 0;
		track_track *trk;
		
		chunkOverlapRec *corp;
		
		UInt8 done = 0;
		
		trk_cnt = mir->numTIRs;
		
		BAILIFNULL( trk = (track_track *)calloc(trk_cnt,sizeof(track_track)), allocFailedErr );

		for (i=0; i<(SInt32)trk_cnt; ++i) {
			// find the chunk counts for each track and setup structures
			tir = &(mir->tirList[i]);
			totalChunks += tir->chunkOffsetEntryCnt;
			
			trk[i].chunk_cnt = tir->chunkOffsetEntryCnt;
			trk[i].chunk_num = 1;	// the next chunk to work on for each track
			
		}
		BAILIFNULL( corp = (chunkOverlapRec *)calloc(totalChunks,sizeof(chunkOverlapRec)), allocFailedErr );
		
		highwatermark = 0;		// the highest chunk end seen

		do { // until we have processed all chunks of all tracks
			UInt32 lowest;
			UInt64 low_offset = 0;
			UInt64 chunkOffset, chunkStop;
			UInt32 chunkSize;
			UInt32 slot;
	
			// find the next lowest chunk start
			lowest = -1;		// next chunk not identified
			for (i=0; i<(SInt32)trk_cnt; i++) {
				UInt64 offset;
				tir = &(mir->tirList[i]);
				if (trk[i].chunk_num <= trk[i].chunk_cnt) {		// track has chunks to process
					offset = tir->chunkOffset[ trk[i].chunk_num ].chunkOffset;
					if ((lowest == (UInt32)-1)  || ((lowest != (UInt32)-1) && (offset<low_offset)))
					{
						low_offset = offset;
						lowest = i;
					}
				}
			}
			if (lowest == (UInt32)-1) 
				errprint("aargh: program error!!!\n");
						
			tir = &(mir->tirList[lowest]);
			BAILIFERR( GetChunkOffsetSize(tir, trk[lowest].chunk_num, &chunkOffset, &chunkSize, nil) );
			if (chunkSize == 0) {
				errprint("Tracks with zero length chunks\n");
				err = badPublicMovieAtom;
				goto bail;
			}
			chunkStop = chunkOffset + chunkSize -1;
			
			if (chunkOffset != low_offset) errprint("Aargh! program error\n");
			
			if (chunkOffset >= (UInt64)vg.inMaxOffset) 
			{
				errprint("Chunk offset %s is at or beyond file size  0x%lx\n", int64toxstr(chunkOffset), vg.inMaxOffset);
			} else if (chunkStop > (UInt64)vg.inMaxOffset) 
			{
				errprint("Chunk end %s is beyond file size  0x%lx\n", int64toxstr(chunkStop), vg.inMaxOffset);
			}
			
			if (chunkOffset >= highwatermark)
			{	// easy, it starts after all other chunks end
				slot = topslot;
			} 
			else 
			{
				// have to insert the chunk into the list somewhere; it might overlap
				UInt32 k, priorslot, nextslot;
				
				// find the first chunk we already have that is starts after the candidate starts (if any)
				slot = topslot;
				for (k=0; k<topslot; k++) {
					// this could be done with binary chop, but it happens rarely
					if (corp[k].chunkStart > chunkOffset) { 
						slot = k; 
						break; 
					}
				}
				
				// Note we only warn if hint track chunks share data with other chunks, of if
				//  two tracks of the same type share data
				
				// do we overlap the prior slots (if any)?
				//   we might overlap slots before that, but if so, they must also overlap the slot
				//   prior to us, and we would have already reported that error
				if (slot > 0) {
					priorslot = slot-1;
					if ((chunkOffset >= corp[priorslot].chunkStart) && (chunkOffset <= corp[priorslot].chunkStop)) {
						if ((tir->mediaType == corp[priorslot].mediaType) || 
							(tir->mediaType == 'hint') || 
							(corp[priorslot].mediaType == 'hint')) 
						warnprint("Warning: chunk %d of track ID %d at %s overlaps chunk from track ID %d at %s\n",
							trk[lowest].chunk_num, tir->trackID, int64todstr_r( chunkOffset, tempStr1 ), 
							corp[priorslot].trackID, int64todstr_r( corp[priorslot].chunkStart, tempStr2 ));
						else errprint("Error: chunk %d of track ID %d at %s overlaps chunk from track ID %d at %s\n",
							trk[lowest].chunk_num, tir->trackID, int64todstr_r( chunkOffset, tempStr1 ), 
							corp[priorslot].trackID, int64todstr_r( corp[priorslot].chunkStart, tempStr2 ));
					}
				}

				// do we overlap the next slots (if any)?
				//   again, we might overlap slots after that, but if so, we also overlap the next slot
				//   and one report is enough
				if (slot < topslot) {
					if ((chunkStop >= corp[slot].chunkStart) && (chunkStop <= corp[slot].chunkStop)) {
						if ((tir->mediaType == corp[slot].mediaType) || 
							(tir->mediaType == 'hint') || 
							(corp[slot].mediaType == 'hint')) 
						warnprint("Warning: chunk %d of track ID %d at %s overlaps chunk from track ID %d at %s\n",
							trk[lowest].chunk_num, tir->trackID, int64todstr_r( chunkOffset, tempStr1 ), 
							corp[slot].trackID, int64todstr_r( corp[slot].chunkStart, tempStr2 ));
						else errprint("Error: chunk %d of track ID %d at %s overlaps chunk from track ID %d at %s\n",
							trk[lowest].chunk_num, tir->trackID, int64todstr_r( chunkOffset, tempStr1 ), 
							corp[slot].trackID, int64todstr_r( corp[slot].chunkStart, tempStr2 ));
					}
				}
					
				// now shuffle the array up prior to inserting this record into the sorted list
				for (nextslot = topslot; nextslot>slot; nextslot-- ) 
					corp[nextslot] = corp[ nextslot-1 ];
			}
			corp[ slot ].chunkStart = chunkOffset;
			corp[ slot ].chunkStop  = chunkStop;
			corp[ slot ].trackID 	= tir->trackID;
			corp[ slot ].mediaType  = tir->mediaType;  
			
				
			if (chunkStop > highwatermark) highwatermark = chunkStop;
			topslot++;

			trk[lowest].chunk_num += 1;		// done that chunk
			
			// see whether we have eaten all chunks for all tracks			
			done = 1;
			for (i=0; i<(SInt32)trk_cnt; i++) {
				if (trk[i].chunk_num <= trk[i].chunk_cnt) { done = 0; break; }
			}
		} while (done != 1);
		// until we have processed all chunks of all tracks
	}
		
		//if(vg.cmaf)
		//	checkCMAFBoxOrder_moov(cnt,list);
			
	aoe->aoeflags |= kAtomValidated;
bail:
//	if (mir != NULL) {
//		dispose_mir(mir);
//	}
	return err;
}

//==========================================================================================

OSErr Validate_moof_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	MovieInfoRec *mir = (MovieInfoRec *)refcon;
	
	MoofInfoRec *moofInfo = &mir->moofInfo[mir->processedFragments];
	
	atomprint("size=\"%ld\"\n", aoe->size);
	atomprint("offset=\"%ld\"\n", aoe->offset);
	atomprint(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;

	moofInfo->offset = aoe->offset;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

	atomerr = ValidateAtomOfType( 'mfhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_mfhd_Atom, cnt, list, moofInfo );
	if (!err) err = atomerr;

	//if((mir->processedFragments > 0) && (moofInfo->sequence_number <= vg.mir->sequence_number))
	  //  errprint( "sequence_number %d in violation of: the value in a given movie fragment be greater than in any preceding movie fragment\n",moofInfo->sequence_number );

	vg.mir->sequence_number = moofInfo->sequence_number;

	moofInfo->index = mir->processedFragments;
	moofInfo->numTrackFragments = 0;
	moofInfo->processedTrackFragments = 0;
	moofInfo->firstFragmentInSegment = false;
	moofInfo->announcedSAP = false;
	moofInfo->samplesToBePresented = true;

	for(i = 0 ;  i < mir->numTIRs ; i++)
	{
		moofInfo->compositionInfoMissingPerTrack[i] = false;
	}
	
	for (i = 0; i < cnt; i++)
	{
		if (list[i].type == 'traf')
			moofInfo->numTrackFragments++;
		
		if (list[i].type == 'pssh') {
			vg.psshFoundInSegment[getSegmentNumberByOffset(moofInfo->offset)] = true;
		}
		
		if (list[i].type == 'tenc') {
			vg.tencFoundInSegment[getSegmentNumberByOffset(moofInfo->offset)] = true;
		}
	}

	if(moofInfo->numTrackFragments > 0)
		moofInfo->trafInfo = (TrafInfoRec *)malloc(moofInfo->numTrackFragments*sizeof(TrafInfoRec));
	else
		moofInfo->trafInfo = NULL;

	if(vg.dashSegment && moofInfo->numTrackFragments == 0)
		errprint("ISO/IEC 23009-1:2012(E), 6.3.4.2: 16: Each 'moof' box shall contain at least one track fragment.\n");
	if(vg.hbbtv && moofInfo->numTrackFragments != 1)
		errprint("###HbbTV check violated Section E.3.1.1: 'The movie fragment boxx (moof) shall contain only one track fragment box(traf)', but found %d\n",moofInfo->numTrackFragments);

	
	atomerr = ValidateAtomOfType( 'traf', 0, 
		Validate_traf_Atom, cnt, list, moofInfo );
	if (!err) err = atomerr;

	atomerr = ValidateAtomOfType( 'pssh', 0, 
		Validate_pssh_Atom, cnt, list, moofInfo );
	if (!err) err = atomerr;

	
	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {		
		//	default:
				warnprint("WARNING: In %s - unknown moof atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	mir->processedFragments++;
	
		//if(vg.cmaf)
		//	checkCMAFBoxOrder_moof(cnt,list);

	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

//==========================================================================================

OSErr Validate_traf_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	MoofInfoRec *moofInfo = (MoofInfoRec *)refcon;
	
	TrafInfoRec *trafInfo = &moofInfo->trafInfo[moofInfo->processedTrackFragments];
	trafInfo->moofInfo = moofInfo;

	moofInfo->processedTrackFragments++;
	trafInfo->cummulatedSampleDuration = 0;
	trafInfo->compositionInfoMissing = false;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

	atomerr = ValidateAtomOfType( 'tfhd', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tfhd_Atom, cnt, list, trafInfo );
	if (!err) err = atomerr;
	
	trafInfo->numTrun = 0;
	trafInfo->processedTrun = 0;
	trafInfo->numSgpd = 0;
	trafInfo->processedSgpd = 0;
	trafInfo->numSbgp = 0;
	trafInfo->processedSbgp = 0;
	trafInfo->tfdtFound = false;
	trafInfo->earliestCompositionTimeInTrackFragment = 0xFFFFFFFFFFFFFFFF;
	trafInfo->compositionEndTimeInTrackFragment = 0;
	trafInfo->latestCompositionTimeInTrackFragment = 0;
	
	for (i = 0; i < cnt; i++)
	{
		if (list[i].type == 'trun')
			trafInfo->numTrun++;
		
		if (list[i].type == 'sgpd')
			trafInfo->numSgpd++;
		
		if (list[i].type == 'sbgp')
			trafInfo->numSbgp++;
	}

	if(trafInfo->duration_is_empty && trafInfo->numTrun > 0)
		errprint("If the duration-is-empty flag is set in the tf_flags, there are no track runs.");

	if(trafInfo->numTrun > 0)
		trafInfo->trunInfo = (TrunInfoRec *)malloc(trafInfo->numTrun*sizeof(TrunInfoRec));
	else
		trafInfo->trunInfo = NULL;
	
	if(trafInfo->numSgpd > 0)
		trafInfo->sgpdInfo = (SgpdInfoRec *)malloc(trafInfo->numSgpd*sizeof(SgpdInfoRec));
	else
		trafInfo->sgpdInfo = NULL;
	
	if(trafInfo->numSbgp > 0)
		trafInfo->sbgpInfo = (SbgpInfoRec *)malloc(trafInfo->numSbgp*sizeof(SbgpInfoRec));
	else
		trafInfo->sbgpInfo = NULL;
	
	atomerr = ValidateAtomOfType( 'trun', 0, 
		Validate_trun_Atom, cnt, list, trafInfo );
	if (!err) err = atomerr;
	
	atomerr = ValidateAtomOfType( 'sgpd', 0, 
		Validate_sgpd_Atom, cnt, list, trafInfo );
	if (!err) err = atomerr;

	atomerr = ValidateAtomOfType( 'sbgp', 0, 
		Validate_sbgp_Atom, cnt, list, trafInfo );
	if (!err) err = atomerr;
	
	if(vg.cmaf){
		atomerr = ValidateAtomOfType( 'subs', 0, 
			Validate_subs_Atom, cnt, list, trafInfo );
		if (!err) err = atomerr;
	}
	
	SInt32 flags;

	flags = kTypeAtomFlagCanHaveAtMostOne;

	if(vg.dashSegment) // This is also suitable for CMAF Fragment checks.
		flags |= kTypeAtomFlagMustHaveOne;
	
	atomerr = ValidateAtomOfType( 'tfdt', flags, 
		Validate_tfdt_Atom, cnt, list, trafInfo );
	if (!err) err = atomerr;
	
	if(vg.cmaf || vg.ctawave){
		atomerr = ValidateAtomOfType( 'senc', 0, 
			Validate_senc_Atom, cnt, list, trafInfo );
		if (!err) err = atomerr;
		
		if(vg.sencFound){
			atomerr = ValidateAtomOfType( 'saio', kTypeAtomFlagMustHaveOne, 
				Validate_saio_Atom, cnt, list, trafInfo );
			if (!err) err = atomerr;
		}
	
	}
	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {			
		//	default:
				warnprint("WARNING: In %s - unknown traf atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}


	//Accumulate durations now for later checking
	for (i = 0; i < (SInt32)trafInfo->numTrun; i++)
	{
		trafInfo->cummulatedSampleDuration+=trafInfo->trunInfo[i].cummulatedSampleDuration;

		//Needed for DASH-specific processing of EPT
		TrackInfoRec *tir = check_track(trafInfo->track_ID);

		if(!tir->identicalDecCompTimes)
			trafInfo->compositionInfoMissing = trafInfo->compositionInfoMissing || (trafInfo->trunInfo[i].sample_count > 0 && trafInfo->trunInfo[i].sample_composition_time_offsets_present != true);
		else
		{
			trafInfo->compositionInfoMissing = false;
			for(UInt32 j = 0 ;  j < trafInfo->trunInfo[i].sample_count ; j++)
				if(trafInfo->trunInfo[i].sample_composition_time_offset[j] != 0) {
					;// Incorrect interpertation: CTTS shall be absent when all CT = DT does not imply CTTS shall be absent iff all CT = DT
					//errprint("CTTS is missing, indicating composition time = decode times, as per Section 8.6.1.1 of ISO/IEC 14496-12 4th edition, while non-zero composition offsets found in track run.\n");
				}
		}
	}

	if(check_track(trafInfo->track_ID) == NULL)
		return badAtomErr;
	
	UInt32 index;

	index = getTrakIndexByID(trafInfo->track_ID);

	moofInfo->compositionInfoMissingPerTrack[index] = moofInfo->compositionInfoMissingPerTrack[index] || trafInfo->compositionInfoMissing;

		//if(vg.cmaf)
		//	checkCMAFBoxOrder_traf(cnt,list);
		
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

void dispose_mir( MovieInfoRec *mir )
{
	
	if(mir->moofInfo)
	{
		UInt32 i;

		for(i = 0 ; i < mir->numFragments ; i++)
		{

			//printf("Fragment number %d / %d\n",i,mir->numFragments);

			if(mir->moofInfo[i].trafInfo != NULL)
			{
					UInt32 j;
					
					for(j = 0 ; j < mir->moofInfo[i].numTrackFragments ; j++)
					{
						//printf("Track Fragment number %d / %d, ptr %x\n",j,mir->moofInfo[i].numTrackFragments,&(mir->moofInfo[i].trafInfo[j]));
						
						if(mir->moofInfo[i].trafInfo[j].trunInfo != NULL)
						{
							UInt32 k;

							for(k = 0 ; k < mir->moofInfo[i].trafInfo[j].numTrun ; k++)
							{
								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_duration != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_duration);
								
								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_size != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_size);
								
								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_flags != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_flags);
								
								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_composition_time_offset != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].sample_composition_time_offset);

								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].samplePresentationTime != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].samplePresentationTime);
								
								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].sampleToBePresented != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].sampleToBePresented);
								
								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].sap3 != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].sap3);
								
								if(mir->moofInfo[i].trafInfo[j].trunInfo[k].sap4 != NULL)
									free(mir->moofInfo[i].trafInfo[j].trunInfo[k].sap4);
							}

							if(mir->moofInfo[i].trafInfo[j].trunInfo != NULL)
								free(mir->moofInfo[i].trafInfo[j].trunInfo);

							if(mir->moofInfo[i].trafInfo[j].sbgpInfo != NULL)
							{
								for(k = 0 ; k < mir->moofInfo[i].trafInfo[j].numSbgp ; k++)
								{									
									free(mir->moofInfo[i].trafInfo[j].sbgpInfo[k].sample_count);
									free(mir->moofInfo[i].trafInfo[j].sbgpInfo[k].group_description_index);
								}
								
								free(mir->moofInfo[i].trafInfo[j].sbgpInfo);
							}
							
							if(mir->moofInfo[i].trafInfo[j].sgpdInfo != NULL)
							{
								for(k = 0 ; k < mir->moofInfo[i].trafInfo[j].numSgpd ; k++)
								{									
									for(UInt32 l = 0 ; l < mir->moofInfo[i].trafInfo[j].sgpdInfo[k].entry_count ; l++)
										free(mir->moofInfo[i].trafInfo[j].sgpdInfo[k].SampleGroupDescriptionEntry[l]);

									free(mir->moofInfo[i].trafInfo[j].sgpdInfo[k].SampleGroupDescriptionEntry);
									free(mir->moofInfo[i].trafInfo[j].sgpdInfo[k].description_length);
								}
								
								free(mir->moofInfo[i].trafInfo[j].sgpdInfo);
							}
						}
					}
						
				free(mir->moofInfo[i].trafInfo);
				free(mir->moofInfo[i].compositionInfoMissingPerTrack);
				free(mir->moofInfo[i].moofEarliestPresentationTimePerTrack);
				free(mir->moofInfo[i].moofPresentationEndTimePerTrack);
				free(mir->moofInfo[i].moofLastPresentationTimePerTrack);
				free(mir->moofInfo[i].tfdt);
			}
		}
		
		free(mir->moofInfo);
	}

	for(int i = 0 ; i < mir->numTIRs ; i++)
		if(mir->tirList[i].leafInfo)
			free(mir->tirList[i].leafInfo);

	
	if(mir->sidxInfo)
	{
		UInt32 i;

		for(i = 0 ; i < mir->numSidx ; i++)
		{
			free(mir->sidxInfo[i].references);
		}

		free(mir->sidxInfo);
	}

	// for each track, get rid of the stuff in it
	free( mir );
}

//==========================================================================================

//==========================================================================================

OSErr Validate_tref_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'tref_hint' atoms
	atomerr = ValidateAtomOfType( 'hint', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tref_hint_Atom, cnt, list, refcon );
	if (!err) err = atomerr;

	// Process 'tref_dpnd' atoms
	atomerr = ValidateAtomOfType( 'dpnd', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tref_dpnd_Atom, cnt, list, refcon );
	if (!err) err = atomerr;

	// Process 'tref_ipir' atoms
	atomerr = ValidateAtomOfType( 'ipir', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tref_ipir_Atom, cnt, list, refcon );
	if (!err) err = atomerr;

	// Process 'tref_mpod' atoms
	atomerr = ValidateAtomOfType( 'mpod', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tref_mpod_Atom, cnt, list, refcon );
	if (!err) err = atomerr;

	// Process 'tref_sync' atoms
	atomerr = ValidateAtomOfType( 'sync', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_tref_sync_Atom, cnt, list, refcon );
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In %s - unknown track reference atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

//==========================================================================================

OSErr Validate_udta_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'cprt' atoms
	atomerr = ValidateAtomOfType( 'cprt', 0,		// can have multiple copyright atoms 
		Validate_cprt_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'loci' atoms
	atomerr = ValidateAtomOfType( 'loci', 0,		// can have multiple copyright atoms 
								 Validate_loci_Atom, cnt, list, nil );
	if (!err) err = atomerr;
		
		// Process 'kind' atoms
		if(vg.cmaf){
				atomerr = ValidateAtomOfType( 'kind', 0,		// can have multiple track kind atoms 
						Validate_kind_Atom, cnt, list, nil );
				if (!err) err = atomerr;
		}

	// Process 'hnti' atoms
	atomerr = ValidateAtomOfType( 'hnti', kTypeAtomFlagCanHaveAtMostOne,
		Validate_moovhnti_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	//
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
				warnprint("WARNING: In %s - unknown/unexpected atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}


//==========================================================================================

//==========================================================================================
//==========================================================================================

static OSErr Validate_rtp_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr 			err = noErr;
	UInt32			dataSize;
	char			*current;
	OSType			subType;
	Ptr				rtpDataP = NULL;
	Ptr				sdpDataP = NULL;
	UInt64			temp64;
	
	atomprintnotab(">\n"); 

	BAILIFNIL( rtpDataP = (Ptr)malloc((UInt32)aoe->size), allocFailedErr );

	dataSize = (UInt32)(aoe->size - aoe->atomStartSize);
	BAILIFERR( GetFileData(aoe, rtpDataP, aoe->offset + aoe->atomStartSize, dataSize, &temp64) );
	
	current = rtpDataP;
	subType = EndianU32_BtoN(*((UInt32*)current));
	current += sizeof(UInt32);

	if (subType == 'sdp ') {
		// we found the sdp data
		// make a copy and null terminate it
		dataSize -= 4; // subtract the subtype field from the length 
		BAILIFNIL( sdpDataP = (Ptr)malloc(dataSize+1), allocFailedErr );
		memcpy(sdpDataP, current, dataSize);
		sdpDataP[dataSize] = '\0';
		
		BAILIFERR( Validate_Movie_SDP(sdpDataP) );
	} else {
		errprint("no sdp in movie user data\n");
		err = outOfDataErr;
		goto bail;
	}
	
bail:
	return err;
}

OSErr Validate_moovhnti_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	
	atomprintnotab(">\n"); 
			
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'rtp ' atoms
	atomerr = ValidateAtomOfType( 'rtp ', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_rtp_Atom, cnt, list, NULL );
	if (!err) err = atomerr;
	
	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {
		//	default:
			// �� should warn
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

//==========================================================================================

OSErr Validate_sinf_Atom( atomOffsetEntry *aoe, void *refcon, UInt32 flags )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;
	
	atomprintnotab(">\n"); 
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'frma' atoms
	atomerr = ValidateAtomOfType( 'frma', flags | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_frma_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'schm' atoms
	atomerr = ValidateAtomOfType( 'schm', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_schm_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'schi' atoms
	atomerr = ValidateAtomOfType( 'schi', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_schi_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {				
		//	default:
				warnprint("WARNING: In %s - unknown security information atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	//if(vg.cmaf)
		//	checkCMAFBoxOrder_sinf(cnt,list);
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

//==========================================================================================

OSErr Validate_meta_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	SInt32 cnt;
	atomOffsetEntry *list;
	SInt32 i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 offset, minOffset, maxOffset;
	
	UInt32 version;
	UInt32 flags;

	atomprintnotab(">\n"); 
	
	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	FieldMustBe( version, 0, "version must be %d not %d" );
	FieldMustBe( flags, 0, "flags must be %d not %d" );

	minOffset = offset;
	maxOffset = aoe->offset + aoe->size;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	
	// Process 'hdlr' atoms
	atomerr = ValidateAtomOfType( 'hdlr', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_hdlr_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'pitm' atoms
	atomerr = ValidateAtomOfType( 'pitm', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_pitm_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'dinf' atoms
	atomerr = ValidateAtomOfType( 'dinf', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_dinf_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'iloc' atoms
	atomerr = ValidateAtomOfType( 'iloc', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_iloc_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'ipro' atoms
	atomerr = ValidateAtomOfType( 'ipro', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_ipro_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'iinf' atoms
	atomerr = ValidateAtomOfType( 'iinf', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_iinf_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'xml ' atoms
	atomerr = ValidateAtomOfType( 'xml ', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_xml_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	// Process 'bxml' atoms
	atomerr = ValidateAtomOfType( 'bxml', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_xml_Atom, cnt, list, nil );
	if (!err) err = atomerr;

	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		//switch (entry->type) {				
		//	default:
				warnprint("WARNING: In %s - unknown meta atom '%s'\n",vg.curatompath, ostypetostr(entry->type));
		//		break;
		//}
		
		if (!err) err = atomerr;
	}
	
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}


