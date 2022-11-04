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

#include "string.h"
#include "stdio.h"
#include "stdlib.h"

extern ValidateGlobals vg;

//===============================================

OSErr CheckMatrixForUnity( MatrixRecord mr )
{
	OSErr err = noErr;
	if ( 	(mr[0][0] != 0x00010000)
		||  (mr[0][1] != 0)
		||  (mr[0][2] != 0)
		||  (mr[1][0] != 0)
		||  (mr[1][1] != 0x00010000)
		||  (mr[1][2] != 0)
		||  (mr[2][0] != 0)
		||  (mr[2][1] != 0)
		||  (mr[2][2] != 0x40000000)
		) {
		err = badAtomErr;
		errprint("has non-identity matrix" "\n");
	}
	return err;
}

//===============================================

OSErr GetFullAtomVersionFlags( atomOffsetEntry *aoe, UInt32 *version, UInt32 *flags, UInt64 *offsetOut)
{
	OSErr err = noErr;
	UInt32 versFlags;
	UInt64 offset;

	// Get version/flags
	offset = aoe->offset + aoe->atomStartSize;
	BAILIFERR( GetFileDataN32( aoe, &versFlags, offset, &offset ) );
	*version = (versFlags >> 24) & 0xFF;
	*flags   =  versFlags & 0x00ffffff;
	*offsetOut = offset;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_iods_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	Ptr odDataP = nil;
	unsigned long odSize;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	FieldMustBe( flags, 0, "'iods' version must be %d not %d" );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint(">\n");

	// Get the ObjectDescriptor
	BAILIFERR( GetFileBitStreamDataToEndOfAtom( aoe, &odDataP, &odSize, offset, &offset ) );
	BAILIFERR( Validate_iods_OD_Bits( odDataP, odSize, true ) );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	if (odDataP)
		free(odDataP);

	return err;
}

//==========================================================================================

typedef struct MovieHeaderCommonRecord {
	Fixed						   preferredRate;			  // must be 1.0 for mp4

	SInt16						  preferredVolume;		   	// must be 1.0 for mp4
	short						   reserved1;					// must be 0

	long							preferredLong1;				// must be 0 for mp4
	long							preferredLong2;				// must be 0 for mp4

	MatrixRecord					matrix;						// must be identity for mp4

	TimeValue					   previewTime;				// must be 0 for mp4
	TimeValue					   previewDuration;			// must be 0 for mp4

	TimeValue					   posterTime;					// must be 0 for mp4

	TimeValue					   selectionTime;  			// must be 0 for mp4
	TimeValue					   selectionDuration;  		// must be 0 for mp4
	TimeValue					   currentTime;		  		// must be 0 for mp4

	long							nextTrackID;
} MovieHeaderCommonRecord;

typedef struct MovieHeaderVers0Record {
	UInt32					creationTime;
	UInt32					modificationTime;
	UInt32					timeScale;
	UInt32					duration;
} MovieHeaderVers0Record;

typedef struct MovieHeaderVers1Record {
	UInt64					creationTime;
	UInt64					modificationTime;
	UInt32					timeScale;
	UInt64					duration;
} MovieHeaderVers1Record;

OSErr Validate_mvhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	MovieHeaderVers1Record	mvhdHead;
	MovieHeaderCommonRecord	mvhdHeadCommon;
	MovieInfoRec	*mir = (MovieInfoRec	*)refcon;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );


	// Get data based on version
	if (version == 0) {
		MovieHeaderVers0Record	mvhdHead0;
		BAILIFERR( GetFileData( aoe, &mvhdHead0, offset, sizeof(mvhdHead0), &offset ) );
		mvhdHead.creationTime = EndianU32_BtoN(mvhdHead0.creationTime);
		mvhdHead.modificationTime = EndianU32_BtoN(mvhdHead0.modificationTime);
		mvhdHead.timeScale = EndianU32_BtoN(mvhdHead0.timeScale);
		mvhdHead.duration = EndianU32_BtoN(mvhdHead0.duration);
	} else if (version == 1) {
		BAILIFERR( GetFileData( aoe, &mvhdHead.creationTime, offset, sizeof(mvhdHead.creationTime), &offset ) );
		mvhdHead.creationTime = EndianU64_BtoN(mvhdHead.creationTime);
		BAILIFERR( GetFileData( aoe, &mvhdHead.modificationTime, offset, sizeof(mvhdHead.modificationTime), &offset ) );
		mvhdHead.modificationTime = EndianU64_BtoN(mvhdHead.modificationTime);
		BAILIFERR( GetFileData( aoe, &mvhdHead.timeScale, offset, sizeof(mvhdHead.timeScale), &offset ) );
		mvhdHead.timeScale = EndianU32_BtoN(mvhdHead.timeScale);
		BAILIFERR( GetFileData( aoe, &mvhdHead.duration, offset, sizeof(mvhdHead.duration), &offset ) );
		mvhdHead.duration = EndianU64_BtoN(mvhdHead.duration);
	} else {
		errprint("Movie header is version other than 0 or 1\n");
		err = badAtomErr;
		goto bail;
	}

	BAILIFERR( GetFileData( aoe, &mvhdHeadCommon, offset, sizeof(mvhdHeadCommon), &offset ) );
	mvhdHeadCommon.preferredRate = EndianU32_BtoN(mvhdHeadCommon.preferredRate);
	mvhdHeadCommon.preferredVolume = EndianS16_BtoN(mvhdHeadCommon.preferredVolume);
	mvhdHeadCommon.reserved1 = EndianS16_BtoN(mvhdHeadCommon.reserved1);
	mvhdHeadCommon.preferredLong1 = EndianS32_BtoN(mvhdHeadCommon.preferredLong1);
	mvhdHeadCommon.preferredLong2 = EndianS32_BtoN(mvhdHeadCommon.preferredLong2);
	EndianMatrix_BtoN(&mvhdHeadCommon.matrix);
	mvhdHeadCommon.previewTime = EndianS32_BtoN(mvhdHeadCommon.previewTime);
	mvhdHeadCommon.previewDuration = EndianS32_BtoN(mvhdHeadCommon.previewDuration);
	mvhdHeadCommon.posterTime = EndianS32_BtoN(mvhdHeadCommon.posterTime);
	mvhdHeadCommon.selectionTime = EndianS32_BtoN(mvhdHeadCommon.selectionTime);
	mvhdHeadCommon.selectionDuration = EndianS32_BtoN(mvhdHeadCommon.selectionDuration);
	mvhdHeadCommon.currentTime = EndianS32_BtoN(mvhdHeadCommon.currentTime);
	mvhdHeadCommon.nextTrackID = EndianS32_BtoN(mvhdHeadCommon.nextTrackID);

	if(vg.cmaf){
		if(mvhdHeadCommon.preferredRate != 0x00010000){
			errprint("CMAF check violated: Section 7.5.1. \"The field rate SHALL be set to its default value\", found 0x%lx\n", mvhdHeadCommon.preferredRate);
		}
		if(mvhdHeadCommon.preferredVolume != 0x0100){
			errprint("CMAF check violated: Section 7.5.1. \"The field volume SHALL be set to its default value\", found 0x%lx\n", mvhdHeadCommon.preferredVolume);
		}
		if(mvhdHeadCommon.matrix[0][0] != 0 && mvhdHeadCommon.matrix[1][1] != 0 && mvhdHeadCommon.matrix[2][2] != 0x40000000){
			errprint("CMAF check violated: Section 7.5.1. \"The field matrix SHALL be set to its default value\", found (0x%lx, 0x%lx, 0x%lx)\n", mvhdHeadCommon.matrix[0][0], mvhdHeadCommon.matrix[1][1], mvhdHeadCommon.matrix[2][2]);
		}
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("creationTime=\"%s\"\n", int64toxstr(mvhdHead.creationTime));
	atomprint("modificationTime=\"%s\"\n", int64toxstr(mvhdHead.modificationTime));
	atomprint("timeScale=\"%s\"\n", int64todstr(mvhdHead.timeScale));
	atomprint("duration=\"%s\"\n", int64todstr(mvhdHead.duration));
	atomprint("nextTrackID=\"%ld\"\n", mvhdHeadCommon.nextTrackID);
	atomprint(">\n");

	mir->mvhd_timescale = mvhdHead.timeScale;	//Used for edit lists

	// Check required field values
	FieldMustBe( mvhdHeadCommon.preferredRate, 0x00010000, "'mvhd' preferredRate must be 0x%lx not 0x%lx" );
	FieldMustBe( mvhdHeadCommon.preferredVolume, 0x0100, "'mvhd' preferredVolume must be 0x%lx not 0x%lx" );
	FieldMustBe( mvhdHeadCommon.reserved1, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.reserved1, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.preferredLong1, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.preferredLong2, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.previewTime, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.previewDuration, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.posterTime, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.selectionTime, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.selectionDuration, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );
	FieldMustBe( mvhdHeadCommon.currentTime, 0, "'mvhd' has a non-zero reserved field, should be %d is %d" );

	BAILIFERR( CheckMatrixForUnity( mvhdHeadCommon.matrix ) );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}


//===============================================

typedef struct TrackHeaderCommonRecord {
	TimeValue			   movieTimeOffset;			// reserved in mp4
	PriorityType			priority;					// reserved in mp4
	SInt16					layer;						// reserved in mp4
	SInt16					alternateGroup;				// reserved in mp4

	SInt16					volume;						// 256 for audio, 0 otherwise
	SInt16					reserved;

	MatrixRecord			matrix;						// must be identity matrix
	Fixed					trackWidth;					// 320 for video, 0 otherwise
	Fixed					trackHeight;				// 240 for video, 0 otherwise
} TrackHeaderCommonRecord;

typedef struct TrackHeaderVers0Record {
	UInt32					creationTime;
	UInt32					modificationTime;
	UInt32					trackID;
	UInt32					reserved;
	TimeValue			   duration;
} TrackHeaderVers0Record;

typedef struct TrackHeaderVers1Record {
	UInt64					creationTime;
	UInt64					modificationTime;
	UInt32					trackID;
	UInt32					reserved;
	UInt64					duration;
} TrackHeaderVers1Record;

OSErr Validate_tkhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	TrackHeaderVers1Record	tkhdHead;
	TrackHeaderCommonRecord	tkhdHeadCommon;
	TrackInfoRec			*tir = (TrackInfoRec*)refcon;


	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );


	// Get data based on version
	if (version == 0) {
		TrackHeaderVers0Record	tkhdHead0;
		BAILIFERR( GetFileData( aoe, &tkhdHead0, offset, sizeof(tkhdHead0), &offset ) );
		tkhdHead.creationTime = EndianU32_BtoN(tkhdHead0.creationTime);
		tkhdHead.modificationTime = EndianU32_BtoN(tkhdHead0.modificationTime);
		tkhdHead.trackID = EndianU32_BtoN(tkhdHead0.trackID);
		tkhdHead.reserved = EndianU32_BtoN(tkhdHead0.reserved);
		tkhdHead.duration = EndianU32_BtoN(tkhdHead0.duration);

		FieldMustBe( EndianU32_BtoN(tkhdHead0.reserved), 0, "'tkhd' reserved must be %d not %d" );
	} else if (version == 1) {
		BAILIFERR( GetFileData( aoe, &tkhdHead.creationTime, offset, sizeof(tkhdHead.creationTime), &offset ) );
		tkhdHead.creationTime = EndianU64_BtoN(tkhdHead.creationTime);
		BAILIFERR( GetFileData( aoe, &tkhdHead.modificationTime, offset, sizeof(tkhdHead.modificationTime), &offset ) );
		tkhdHead.modificationTime = EndianU64_BtoN(tkhdHead.modificationTime);
		BAILIFERR( GetFileData( aoe, &tkhdHead.trackID , offset, sizeof(tkhdHead.trackID ), &offset ) );
		tkhdHead.trackID = EndianU32_BtoN(tkhdHead.trackID);
		BAILIFERR( GetFileData( aoe, &tkhdHead.reserved , offset, sizeof(tkhdHead.reserved ), &offset ) );
		tkhdHead.reserved = EndianU32_BtoN(tkhdHead.reserved);
		BAILIFERR( GetFileData( aoe, &tkhdHead.duration, offset, sizeof(tkhdHead.duration), &offset ) );
		tkhdHead.duration = EndianU64_BtoN(tkhdHead.duration);
	} else {
		errprint("Track header is version other than 0 or 1\n");
		err = badAtomErr;
		goto bail;
	}
	BAILIFERR( GetFileData( aoe, &tkhdHeadCommon, offset, sizeof(tkhdHeadCommon), &offset ) );
	tkhdHeadCommon.movieTimeOffset = EndianU32_BtoN(tkhdHeadCommon.movieTimeOffset);
	tkhdHeadCommon.priority = EndianU32_BtoN(tkhdHeadCommon.priority);
	tkhdHeadCommon.layer = EndianS16_BtoN(tkhdHeadCommon.layer);
	tkhdHeadCommon.alternateGroup = EndianS16_BtoN(tkhdHeadCommon.alternateGroup);
	tkhdHeadCommon.volume = EndianS16_BtoN(tkhdHeadCommon.volume);
	tkhdHeadCommon.reserved = EndianS16_BtoN(tkhdHeadCommon.reserved);
	EndianMatrix_BtoN(&tkhdHeadCommon.matrix);
	tkhdHeadCommon.trackWidth = EndianS32_BtoN(tkhdHeadCommon.trackWidth);
	tkhdHeadCommon.trackHeight = EndianS32_BtoN(tkhdHeadCommon.trackHeight);

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("creationTime=\"%s\"\n", int64toxstr(tkhdHead.creationTime));
	atomprint("modificationTime=\"%s\"\n", int64toxstr(tkhdHead.modificationTime));
	atomprint("trackID=\"%ld\"\n", tkhdHead.trackID);
	atomprint("duration=\"%s\"\n", int64todstr(tkhdHead.duration));
	atomprint("volume=\"%s\"\n", fixed16str(tkhdHeadCommon.volume));
	atomprint("width=\"%s\"\n", fixedU32str(tkhdHeadCommon.trackWidth));
	atomprint("height=\"%s\"\n", fixedU32str(tkhdHeadCommon.trackHeight));
	atomprint(">\n");

	// Check required field values
	// else FieldMustBe( flags, 1, "'tkhd' flags must be 1" );
	if ((flags & 7) != flags) errprint("Tkhd flags 0x%X other than 1,2 or 4 set\n", flags);
	if (flags == 0) warnprint( "WARNING: 'tkhd' flags == 0 (OK in a hint track)\n", flags );
	if (tkhdHead.duration == 0 && !vg.dashSegment) warnprint( "WARNING: 'tkhd' duration == 0, track may be considered empty\n", flags );


	FieldMustBe( tkhdHeadCommon.movieTimeOffset, 0, "'tkhd' movieTimeOffset must be %d not %d" );
	FieldMustBe( tkhdHeadCommon.priority, 0, "'tkhd' priority must be %d not %d" );
	FieldMustBe( tkhdHeadCommon.layer, 0, "'tkhd' layer must be %d not %d" );
	FieldMustBe( tkhdHeadCommon.alternateGroup, 0, "'tkhd' alternateGroup must be %d not %d" );
	FieldMustBe( tkhdHeadCommon.reserved, 0, "'tkhd' reserved must be %d not %d" );

	// ���� CHECK for audio/video
	{
		FieldMustBeOneOf2( tkhdHeadCommon.volume, SInt16, "'tkhd' volume must be set to one of ", (0, 0x0100) );
		if( vg.majorBrand == brandtype_mp41 ){
			FieldMustBeOneOf2( tkhdHeadCommon.trackWidth, Fixed, "'tkhd' trackWidth must be set to one of ", (0, (320L << 16)) );
			FieldMustBeOneOf2( tkhdHeadCommon.trackHeight, Fixed, "'tkhd' trackHeight must be set to one of ", (0, (240L << 16)) );
		}
	}

	if(vg.cmaf){
		if(tkhdHead.duration != 0){
			errprint("CMAF check violated: Section 7.5.4. \"The value of the duration field SHALL be set to a value of zero\", found %llu\n",tkhdHead.duration);
		}

		if((tkhdHeadCommon.matrix[0][0] != 0 && tkhdHeadCommon.matrix[1][1] != 0 && tkhdHeadCommon.matrix[2][2] != 0x40000000) || (tkhdHeadCommon.matrix[0][0] != 0x00010000 && tkhdHeadCommon.matrix[1][1] != 0x00010000 && tkhdHeadCommon.matrix[2][2] != 0x40000000)){
			errprint("CMAF check violated: Section 7.5.4. \"The field matrix SHALL be set their default values\", found (0x%lx, 0x%lx, 0x%lx)\n", tkhdHeadCommon.matrix[0][0], tkhdHeadCommon.matrix[1][1], tkhdHeadCommon.matrix[2][2]);
		}

		if(tir->mediaType == 'soun'){
			if(tkhdHeadCommon.trackWidth != 0 && tkhdHeadCommon.trackHeight != 0)
				errprint("CMAF check violated: Section 7.5.4. \"The width and height fields for a non-visual track SHALL be 0\", found width=\"%s\", height=\"%s\"\n", fixedU32str(tkhdHeadCommon.trackWidth), fixedU32str(tkhdHeadCommon.trackHeight));
		}
	}

		// save off some of the fields in the rec
	if (tir != NULL) {
		tir->trackID = tkhdHead.trackID;
		tir->trackWidth = tkhdHeadCommon.trackWidth;
		tir->trackHeight = tkhdHeadCommon.trackHeight;
	}
	else errprint("Internal error -- Track ID %d not recorded\n",tkhdHead.trackID);

		BAILIFERR( CheckMatrixForUnity( tkhdHeadCommon.matrix ) );


	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}


//==========================================================================================

typedef struct MediaHeaderCommonRecord {
	UInt16					language; 		// high-bit = 0, packed ISO-639-2/T language code (int(5)[3])
	UInt16					quality;		// must be 0 for mp4
} MediaHeaderCommonRecord;

typedef struct MediaHeaderVers0Record {
	UInt32					creationTime;
	UInt32					modificationTime;
	UInt32					timescale;
	UInt32					duration;
} MediaHeaderVers0Record;

typedef struct MediaHeaderVers1Record {
	UInt64					creationTime;
	UInt64					modificationTime;
	UInt32					timescale;
	UInt64					duration;
} MediaHeaderVers1Record;


OSErr Validate_mdhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	MediaHeaderVers1Record	mdhdHead;
	MediaHeaderCommonRecord	mdhdHeadCommon;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data based on version
	if (version == 0) {
		MediaHeaderVers0Record	mdhdHead0;
		BAILIFERR( GetFileData( aoe, &mdhdHead0, offset, sizeof(mdhdHead0), &offset ) );
		mdhdHead.creationTime = EndianU32_BtoN(mdhdHead0.creationTime);
		mdhdHead.modificationTime = EndianU32_BtoN(mdhdHead0.modificationTime);
		mdhdHead.timescale = EndianU32_BtoN(mdhdHead0.timescale);
		mdhdHead.duration = EndianU32_BtoN(mdhdHead0.duration);
	} else if (version == 1) {
		BAILIFERR( GetFileData( aoe, &mdhdHead.creationTime, offset, sizeof(mdhdHead.creationTime), &offset ) );
		mdhdHead.creationTime = EndianU64_BtoN(mdhdHead.creationTime);
		BAILIFERR( GetFileData( aoe, &mdhdHead.modificationTime, offset, sizeof(mdhdHead.modificationTime), &offset ) );
		mdhdHead.modificationTime = EndianU64_BtoN(mdhdHead.modificationTime);
		BAILIFERR( GetFileData( aoe, &mdhdHead.timescale, offset, sizeof(mdhdHead.timescale), &offset ) );
		mdhdHead.timescale = EndianU32_BtoN(mdhdHead.timescale);
		BAILIFERR( GetFileData( aoe, &mdhdHead.duration, offset, sizeof(mdhdHead.duration), &offset ) );
		mdhdHead.duration = EndianU64_BtoN(mdhdHead.duration);
	} else {
		errprint("Media header is version other than 0 or 1\n");
		err = badAtomErr;
		goto bail;
	}
	tir->mediaTimeScale = mdhdHead.timescale;
	tir->mediaDuration = mdhdHead.duration;
		vg.mediaHeaderTimescale=mdhdHead.timescale;

	BAILIFERR( GetFileData( aoe, &mdhdHeadCommon, offset, sizeof(mdhdHeadCommon), &offset ) );
	mdhdHeadCommon.language = EndianU16_BtoN(mdhdHeadCommon.language);
	mdhdHeadCommon.quality = EndianU16_BtoN(mdhdHeadCommon.quality);

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("creationTime=\"%s\"\n", int64toxstr(mdhdHead.creationTime));
	atomprint("modificationTime=\"%s\"\n", int64toxstr(mdhdHead.modificationTime));
	atomprint("timescale=\"%s\"\n", int64todstr(mdhdHead.timescale));
	atomprint("duration=\"%s\"\n", int64todstr(mdhdHead.duration));
	atomprint("language=\"%s\"\n", langtodstr(mdhdHeadCommon.language));
	if (mdhdHeadCommon.language==0) warnprint("Warning: Media Header language code of 0 not strictly legit -- 'und' preferred\n");

	atomprint(">\n");

	// Check required field values
	FieldMustBe( flags, 0, "'mdvd' flags must be %d not %d" );
	FieldMustBe( mdhdHeadCommon.quality, 0, "'mdhd' quality (reserved in mp4) must be %d not %d" );
	FieldCheck( (mdhdHead.duration > 0 || vg.dashSegment), "'mdhd' duration must be > 0" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;

}

//==========================================================================================


OSErr Get_mdia_hdlr_mediaType( atomOffsetEntry *aoe, TrackInfoRec *tir )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	HandlerInfoRecord	hdlrInfo;

	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	BAILIFERR( GetFileData( aoe, &hdlrInfo, offset, (UInt64)fieldOffset(HandlerInfoRecord, Name), &offset ) );
	tir->mediaType = EndianU32_BtoN(hdlrInfo.componentSubType);
bail:
	return err;
}

OSErr Validate_mdia_hdlr_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	HandlerInfoRecord	*hdlrInfo = (HandlerInfoRecord *)malloc(sizeof(HandlerInfoRecord));
	char *nameP;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	FieldMustBe( version, 0, "version must be %d not %d" );
	FieldMustBe( flags, 0, "flags must be %d not %d" );

	// Get Handler Info (minus name)
	BAILIFERR( GetFileData( aoe, hdlrInfo, offset, (UInt64)fieldOffset(HandlerInfoRecord, Name), &offset ) );
	hdlrInfo->componentType = EndianU32_BtoN(hdlrInfo->componentType);
	hdlrInfo->componentSubType = EndianU32_BtoN(hdlrInfo->componentSubType);
	hdlrInfo->componentFlags = EndianU32_BtoN(hdlrInfo->componentFlags);
	hdlrInfo->componentFlagsMask = EndianU32_BtoN(hdlrInfo->componentFlagsMask);
	hdlrInfo->componentFlags = EndianU32_BtoN(hdlrInfo->componentFlags);
	hdlrInfo->componentFlags = EndianU32_BtoN(hdlrInfo->componentFlags);

	// Remember info in the refcon
	if (vg.print_atompath) {
		fprintf(stdout,"\t\tHandler subtype = '%s'\n", ostypetostr(hdlrInfo->componentSubType));
	}
	tir->mediaType = hdlrInfo->componentSubType;
	atomprint("handler_type=\"%s\"\n", ostypetostr(hdlrInfo->componentSubType));

	// Get Handler Info Name
	BAILIFERR( GetFileCString( aoe, &nameP, offset, aoe->maxOffset - offset, &offset ) );
	//atomprint("name=\"%s\"\n", nameP);

	// Check required field values
	FieldMustBe( hdlrInfo->componentType, 0, "'hdlr' componentType (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( hdlrInfo->componentManufacturer, 0, "'hdlr' componentManufacturer (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( hdlrInfo->componentFlags, 0, "'hdlr' componentFlags (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( hdlrInfo->componentFlagsMask, 0, "'hdlr' componentFlagsMask (reserved in mp4) must be %d not 0x%lx" );

		FieldMustBeOneOf12( hdlrInfo->componentSubType, OSType,
			"'hdlr' handler type must be be one of ",
			('odsm', 'crsm', 'sdsm', 'vide', 'soun', 'm7sm', 'ocsm', 'ipsm', 'mjsm', 'hint', 'subt', 'text') );

		//Explicit check for ac-4
		if(!strcmp(vg.codecs, "ac-4") && strcmp(ostypetostr(hdlrInfo->componentSubType),"soun"))
			errprint("handler_type is not 'soun', 'soun' is expected for 'ac-4'\n" );

		//Explicit check for ec-3
		if(!strcmp(vg.codecs, "ec-3") && strcmp(ostypetostr(hdlrInfo->componentSubType),"soun"))
			errprint("handler_type is not 'soun', 'soun' is expected for 'ec-3'\n" );

		//Explicit check for ac-3
		if(!strcmp(vg.codecs, "ac-3") && strcmp(ostypetostr(hdlrInfo->componentSubType),"soun"))
			errprint("handler_type is not 'soun', 'soun' is expected for 'ac-3'\n" );

	tir->hdlrInfo = hdlrInfo;
	// All done
	atomprint(">\n");
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_hdlr_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	HandlerInfoRecord	*hdlrInfo = (HandlerInfoRecord *)malloc(sizeof(HandlerInfoRecord));
	char *nameP;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	FieldMustBe( version, 0, "version must be %d not %d" );
	FieldMustBe( flags, 0, "flags must be %d not %d" );

	// Get Handler Info (minus name)
	BAILIFERR( GetFileData( aoe, hdlrInfo, offset, (UInt64)fieldOffset(HandlerInfoRecord, Name), &offset ) );
	hdlrInfo->componentType = EndianU32_BtoN(hdlrInfo->componentType);
	hdlrInfo->componentSubType = EndianU32_BtoN(hdlrInfo->componentSubType);
	hdlrInfo->componentFlags = EndianU32_BtoN(hdlrInfo->componentFlags);
	hdlrInfo->componentFlagsMask = EndianU32_BtoN(hdlrInfo->componentFlagsMask);
	hdlrInfo->componentFlags = EndianU32_BtoN(hdlrInfo->componentFlags);
	hdlrInfo->componentFlags = EndianU32_BtoN(hdlrInfo->componentFlags);

	// Remember info in the refcon
	if (vg.print_atompath) {
		fprintf(stdout,"\t\tHandler subtype = '%s'\n", ostypetostr(hdlrInfo->componentSubType));
	}
	atomprint("handler_type=\"%s\"\n", ostypetostr(hdlrInfo->componentSubType));

	// Get Handler Info Name
	BAILIFERR( GetFileCString( aoe, &nameP, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("name=\"%s\"\n", nameP);

	// Check required field values
	FieldMustBe( hdlrInfo->componentType, 0, "'hdlr' componentType (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( hdlrInfo->componentManufacturer, 0, "'hdlr' componentManufacturer (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( hdlrInfo->componentFlags, 0, "'hdlr' componentFlags (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( hdlrInfo->componentFlagsMask, 0, "'hdlr' componentFlagsMask (reserved in mp4) must be %d not 0x%lx" );

	// All done
	atomprint(">\n");
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_elng_Atom( atomOffsetEntry *aoe, void *refcon )
{
		OSErr err = noErr;
		UInt32 version;
		UInt32 flags;
		UInt64 offset;
		char* extended_languages;

		// Get version/flags
		BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
		atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

		// Get related attributes
		BAILIFERR( GetFileCString( aoe, &extended_languages, offset, aoe->maxOffset - offset, &offset ) );
		atomprint("extended_languages=\"%s\"\n", extended_languages);

		atomprint(">\n");

		// All done
		aoe->aoeflags |= kAtomValidated;

bail:

		return err;
}

//==========================================================================================

typedef struct VideoMediaInfoHeader {
	UInt16	graphicsMode;			   /* for QD - transfer mode */
	UInt16	opColorRed;				 /* opcolor for transfer mode */
	UInt16	opColorGreen;
	UInt16	opColorBlue;
} VideoMediaInfoHeader;

OSErr Validate_vmhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	VideoMediaInfoHeader	vmhdInfo;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileData( aoe, &vmhdInfo, offset, sizeof(vmhdInfo), &offset ) );
	vmhdInfo.graphicsMode = EndianU16_BtoN(vmhdInfo.graphicsMode);
	vmhdInfo.opColorRed = EndianU16_BtoN(vmhdInfo.opColorRed);
	vmhdInfo.opColorGreen = EndianU16_BtoN(vmhdInfo.opColorGreen);
	vmhdInfo.opColorBlue = EndianU16_BtoN(vmhdInfo.opColorBlue);

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint(">\n");

	if(vg.cmaf){
		if(version != 0){
			errprint("CMAF check violated: Section 7.5.6. \"The following field SHALL be set to its default value: version=0\", found %d\n",version);
		}
		if(vmhdInfo.graphicsMode != 0){
			errprint("CMAF check violated: Section 7.5.6. \"The following field SHALL be set to its default value: graphicsmode=0\", found %d\n",vmhdInfo.graphicsMode);
		}
		if(vmhdInfo.opColorRed != 0 && vmhdInfo.opColorGreen != 0 && vmhdInfo.opColorBlue != 0){
			errprint("CMAF check violated: Section 7.5.6. \"The following field SHALL be set to its default value: opcolor={0, 0, 0}\", found {0x%lx, 0x%lx, 0x%lx}\n",vmhdInfo.opColorRed, vmhdInfo.opColorGreen, vmhdInfo.opColorBlue);
		}
	}

	// Check required field values
	FieldMustBe( version, 0, "'vmhd' version must be %d not %d" );
	FieldMustBe( flags, 1, "'vmhd' flags must be %d not 0x%lx" );
	FieldMustBe( vmhdInfo.graphicsMode, 0, "'vmhd' graphicsMode (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( vmhdInfo.opColorRed,   0, "'vmhd' opColorRed   (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( vmhdInfo.opColorGreen, 0, "'vmhd' opColorGreen (reserved in mp4) must be %d not 0x%lx" );
	FieldMustBe( vmhdInfo.opColorBlue,  0, "'vmhd' opColorBlue  (reserved in mp4) must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}


//==========================================================================================

typedef struct SoundMediaInfoHeader {
	UInt16	balance;
	UInt16	rsrvd;
} SoundMediaInfoHeader;

OSErr Validate_smhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	SoundMediaInfoHeader	smhdInfo;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileData( aoe, &smhdInfo, offset, sizeof(smhdInfo), &offset ) );
	smhdInfo.balance = EndianU16_BtoN(smhdInfo.balance);
	smhdInfo.rsrvd = EndianU16_BtoN(smhdInfo.rsrvd);

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint(">\n");

	// Check required field values
	FieldMustBe( flags, 0, "'smhd' flags must be %d not 0x%lx" );
	FieldMustBe( smhdInfo.balance, 0, "'smhd' balance (reserved in mp4) must be %d not %d" );
	FieldMustBe( smhdInfo.rsrvd,   0, "'smhd' rsrvd must be %d not 0x%lx" );

		if(vg.cmaf && smhdInfo.balance !=0)
			errprint("CMAF check violated: Section 7.5.7. \"The balance field in SoundMediaHeaderBox SHALL be set to its default value 0\", found %d\n",smhdInfo.balance);

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}


//==========================================================================================

typedef struct HintMediaInfoHeader {
	UInt16	maxPDUsize;
	UInt16	avgPDUsize;
	UInt32	maxbitrate;
	UInt32	avgbitrate;
	UInt32	slidingavgbitrate;
} HintMediaInfoHeader;

OSErr Validate_hmhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	HintMediaInfoHeader	hmhdInfo;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileData( aoe, &hmhdInfo, offset, sizeof(hmhdInfo), &offset ) );
	hmhdInfo.maxPDUsize = EndianU16_BtoN(hmhdInfo.maxPDUsize);
	hmhdInfo.avgPDUsize = EndianU16_BtoN(hmhdInfo.avgPDUsize);
	hmhdInfo.maxbitrate = EndianU32_BtoN(hmhdInfo.maxbitrate);
	hmhdInfo.avgbitrate = EndianU32_BtoN(hmhdInfo.avgbitrate);
	hmhdInfo.slidingavgbitrate = EndianU32_BtoN(hmhdInfo.slidingavgbitrate);

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("maxPDUsize=\"%ld\"\n", hmhdInfo.maxPDUsize);
	atomprint("avgPDUsize=\"%ld\"\n", hmhdInfo.avgPDUsize);
	atomprint("maxbitrate=\"%ld\"\n", hmhdInfo.maxbitrate);
	atomprint("avgbitrate=\"%ld\"\n", hmhdInfo.avgbitrate);
	atomprint("slidingavgbitrate=\"%ld\"\n", hmhdInfo.slidingavgbitrate);
	atomprint(">\n");

	// Check required field values
	FieldMustBe( flags, 0, "'hmdh' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_sthd_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
		OSErr err = noErr;
		UInt32 version;
		UInt32 flags;
		UInt64 offset;

		// Get version/flags
		BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

		// Print atom contents non-required fields
		atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
		atomprint(">\n");

		// Check required field values
		FieldMustBe( flags, 0, "'sthd' flags must be %d not 0x%lx" );

		// All done
		aoe->aoeflags |= kAtomValidated;

bail:
		return err;
}

//==========================================================================================

OSErr Validate_nmhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// There is no data

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint(">\n");

//����� need to check for underrun

	// Check required field values
	FieldMustBe( flags, 0, "'nmhd' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}


//==========================================================================================

OSErr Validate_mp4s_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// There is no data

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

	atomprint("/>\n");

	// Check required field values
	FieldMustBe( flags, 0, "'mp4s' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_url_Entry( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	char *locationP = nil;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	if (flags & 1) {
		// no more data
	} else {

		if(vg.dashSegment)
			errprint("url pointing to external data found in 'dref', violating ISO/IEC 23009-1:2012(E), 6.3.4.2:  The 'moof' boxes shall use movie-fragment relative addressing for media data that does not use external data references.\n");

		BAILIFERR( GetFileCString( aoe, &locationP, offset, aoe->maxOffset - offset, &offset ) );
	}


	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	if (locationP) {
		atomprint("location=\"%s\"\n", locationP);
	}
	atomprint("/>\n");

	// Check required field values
//���	FieldMustBe( flags, 0, "'mp4s' flags must be 0" );
//���   need to check that the atom has ended.
		if(vg.cmaf && flags != 0x000001){
		errprint("CMAF check violated: Section 7.5.9. \"The Data Reference Box ('dref') SHALL contain a single entry with the entry_flags set to 0x000001 \", found 0x%lx\n", flags); //Single entry has been checked in 'dref' validation.
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_urn_Entry( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	char *nameP = nil;
	char *locationP = nil;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	if ((flags & 1) == 0 && vg.dashSegment)
			errprint("urn entry with pointing to external data found in 'dref', violating ISO/IEC 23009-1:2012(E), 6.3.4.2:  The 'moof' boxes shall use movie-fragment relative addressing for media data that does not use external data references.\n");

	// Get data
	// name is required
	BAILIFERR( GetFileCString( aoe, &nameP, offset, aoe->maxOffset - offset, &offset ) );
	if (offset >= (aoe->offset + aoe->size)) {
		BAILIFERR( GetFileCString( aoe, &locationP, offset, aoe->maxOffset - offset, &offset ) );
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("name=\"%s\"\n", nameP);
	if (locationP) {
		atomprint("location=\"%s\"\n", locationP);
	}
	atomprint("/>\n");

	// Check required field values
//���	FieldMustBe( flags, 0, "'mp4s' flags must be 0" );
//���   need to check that the atom has ended.
		if(vg.cmaf && flags != 0x000001){
		errprint("CMAF check violated: Section 7.5.9. \"The Data Reference Box ('dref') SHALL contain a single entry with the entry_flags set to 0x000001 \", found 0x%lx\n", flags); //Single entry has been checked in 'dref' validation.
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================



OSErr Validate_dref_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );

	if(vg.cmaf && entryCount != 1){
		errprint("CMAF check violated: Section 7.5.9. \"The Data Reference Box ('dref') SHALL contain a single entry \", found %ld\n", entryCount);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n"); //vg.tabcnt++;

	// Check required field values
	FieldMustBe( flags, 0, "'dref' flags must be %d not 0x%lx" );

	//need to validate url urn
	{
		UInt64 minOffset, maxOffset;
		atomOffsetEntry *entry;
		long cnt;
		atomOffsetEntry *list;
		int i;

		minOffset = offset;
		maxOffset = aoe->offset + aoe->size;

		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

		for (i = 0; i < cnt; i++) {
			entry = &list[i];

			atomprint("<%s",ostypetostr(entry->type)); vg.tabcnt++;

			switch( entry->type ) {
				case 'url ':
					Validate_url_Entry( entry, refcon );
					break;

				case 'urn ':
					Validate_urn_Entry( entry, refcon );
					break;

				default:
				// �� should warn
					warnprint("WARNING: In %s unknown/unexpected dref entry '%s'\n",vg.curatompath, ostypetostr(entry->type));
					atomprint("???? />\n");
					break;
			}
			--vg.tabcnt;

		}

	}

//	vg.tabcnt--;

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_stts_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	TimeToSampleNum *listP = NULL;
	UInt32 listSize;
	UInt32 i;
	UInt32 numSamples = 0;
	UInt64 totalDuration = 0;
	Boolean lastSampleDurationIsZero = false;
	char 	tempStr1[32];
	char 	tempStr2[32];

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
		//  adding 1 to entryCount to make this 1 based array
	listSize = entryCount * sizeof(TimeToSampleNum);
	BAILIFNULL( listP = (TimeToSampleNum *)malloc(listSize + sizeof(TimeToSampleNum)), allocFailedErr );
	BAILIFERR( GetFileData( aoe, &listP[1], offset, listSize, &offset ) );
	listP[0].sampleCount = 0; listP[0].sampleDuration = 0;
	for ( i = 1; i <= entryCount; i++ ) {
		listP[i].sampleCount = EndianS32_BtoN(listP[i].sampleCount);
		listP[i].sampleDuration = EndianS32_BtoN(listP[i].sampleDuration);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n");
	vg.tabcnt++;

	if(vg.dashSegment && entryCount != 0)
		errprint("stts atom, entry_count %d, violating\n ISO/IEC 23009-1:2012(E), 6.3.3: The tracks in the \"moov\" box shall contain no samples \n(i.e. the entry_count in the \"stts\", \"stsc\", and \"stco\" boxes shall be set to 0)\n",entryCount);

	if(vg.cmaf && entryCount != 0){
		errprint("CMAF check violated: Section 7.5.12. \"All boxes in SampleTableBox SHALL have or compute a sample count of 0\", found %d\n", entryCount);
	}

		//  changes to i stuff where needed to make it 1 based
	for ( i = 1; i <= entryCount; i++ ) {
		atomprintdetailed("<sttsEntry sampleCount=\"%d\" sampleDelta/duration=\"%d\" />\n", listP[i].sampleCount, listP[i].sampleDuration);
		if (!listP[i].sampleDuration) {
			if (i == (entryCount)) {
				lastSampleDurationIsZero = true;
			} else {
				errprint("You can't have a zero duration other than last in the stts TimeToSample table\n");
			}
		}
		numSamples += listP[i].sampleCount;
		totalDuration += listP[i].sampleCount * listP[i].sampleDuration;
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'stts' flags must be %d not 0x%lx" );

	if (lastSampleDurationIsZero) {
		if ((tir->mediaDuration) && (totalDuration > tir->mediaDuration)) {
			errprint("The last TimeToSample sample duration is zero, but the total duration (%s)"
					 " described by the table is greater than the 'mdhd' mediaDuration (%s)\n",
					 int64todstr_r(totalDuration, tempStr1), int64todstr_r(tir->mediaDuration, tempStr2));
		} else if ((tir->mediaDuration) && (totalDuration == tir->mediaDuration)) {
			warnprint("Warning: The last TimeToSample sample duration is zero, but the total duration (%s)"
					 " described by the table is equal to the 'mdhd' mediaDuration (%s)\n",
					 int64todstr_r(totalDuration, tempStr1), int64todstr_r(tir->mediaDuration, tempStr2));
		}
	}


	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	tir->timeToSample = listP;

	tir->timeToSampleSampleCnt = numSamples;
	tir->timeToSampleDuration = totalDuration;
	tir->timeToSampleEntryCnt = entryCount;

	return err;
}

//==========================================================================================


typedef struct CompositionTimeToSampleNum {
	long			 sampleCount;
	TimeValue		sampleOffset;
} CompositionTimeToSampleNum;

OSErr Validate_ctts_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	UInt32 totalcount;
	UInt32 allzero;
	CompositionTimeToSampleNum *listP;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	listSize = entryCount * sizeof(TimeToSampleNum);
	BAILIFNIL( listP = (CompositionTimeToSampleNum *)malloc(listSize), allocFailedErr );
	BAILIFERR( GetFileData( aoe, listP, offset, listSize, &offset ) );
	for ( i = 0; i < entryCount; i++ ) {
		listP[i].sampleCount = EndianS32_BtoN(listP[i].sampleCount);
		listP[i].sampleOffset = EndianS32_BtoN(listP[i].sampleOffset);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n");
	vg.tabcnt++;

	totalcount = 0;  allzero = 1;

	for ( i = 0; i < entryCount; i++ ) {
		atomprintdetailed("<cttsEntry sampleCount=\"%d\" sampleDelta/duration=\"%d\" />\n", listP[i].sampleCount, listP[i].sampleOffset);
		totalcount += listP[i].sampleCount;
		if (listP[i].sampleOffset != 0) allzero = 0;
	}

	if (totalcount == 0) warnprint("WARNING: CTTS atom has no entries so is un-needed\n");
	if (allzero == 1) warnprint("WARNING: CTTS atom has no entry with a non-zero offset so is un-needed\n");
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'ctts' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================


OSErr Validate_stsz_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	UInt32 sampleSize;
	SampleSizeRecord *listP = NULL;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &sampleSize, offset, &offset ) );
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	if ((sampleSize == 0) && entryCount) {
		listSize = entryCount * sizeof(SampleSizeRecord);
			// 1 based array
		BAILIFNIL( listP = (SampleSizeRecord *)malloc(listSize + sizeof(SampleSizeRecord)), allocFailedErr );
		BAILIFERR( GetFileData( aoe, &listP[1], offset, listSize, &offset ) );
		for ( i = 1; i <= entryCount; i++ ) {
			listP[i].sampleSize = EndianS32_BtoN(listP[i].sampleSize);
		}
	}

	if(vg.cmaf){
		if(entryCount != 0){
			errprint("CMAF check violated: Section 7.5.12. \"All boxes in SampleTableBox SHALL have or compute a sample count of 0\", found %d\n", entryCount);
		}
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("sampleSize=\"%ld\"\n", sampleSize);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n");
	if ((sampleSize == 0) && entryCount) {
		vg.tabcnt++;
		listP[0].sampleSize = 0;
		for ( i = 1; i <= entryCount; i++ ) {
			atomprintdetailed("<stszEntry sampleSize=\"%d\" />\n", listP[i].sampleSize);
			if (listP[i].sampleSize == 0) {
				errprint("You can't have a zero sample size in stsz\n");
			}
		}
		--vg.tabcnt;
	}

	// Check required field values
	FieldMustBe( flags, 0, "'stsz' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;
	tir->sampleSizeEntryCnt = entryCount;
	tir->singleSampleSize = sampleSize;
	tir->sampleSize = listP;

bail:
	return err;
}

OSErr Validate_stz2_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	UInt32 temp;
	UInt8 fieldSize;
	SampleSizeRecord *listP;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileData( aoe, &temp, offset, 3, &offset ) );
	BAILIFERR( GetFileData( aoe, &fieldSize, offset, 1, &offset ) );
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	listSize = entryCount * sizeof(SampleSizeRecord);
		// 1 based array + room for one over for the 4-bit case loop
	BAILIFNIL( listP = (SampleSizeRecord *)malloc(listSize + sizeof(SampleSizeRecord) + sizeof(SampleSizeRecord)), allocFailedErr );

	if(vg.cmaf && entryCount != 0){
		errprint("CMAF check violated: Section 7.5.12. \"All boxes in SampleTableBox SHALL have or compute a sample count of 0\", found %d\n", entryCount);
	}

	if (entryCount) switch (fieldSize) {
		case 4:
			for (i=0; i<((entryCount+1)/2); i++) {
				UInt8 theSize;
				BAILIFERR( GetFileData( aoe, &theSize, offset, 1, &offset ) );
				listP[i*2 + 1].sampleSize = theSize >> 4;
				listP[i*2 + 2].sampleSize = theSize & 0x0F;
			}
			break;
		case 8:
			for (i=1; i<=entryCount; i++) {
				UInt8 theSize;
				BAILIFERR( GetFileData( aoe, &theSize, offset, 1, &offset ) );
				listP[i].sampleSize = theSize;
			}
			break;
		case 16:
			for (i=1; i<=entryCount; i++) {
				UInt16 theSize;
				BAILIFERR( GetFileDataN16( aoe, &theSize, offset, &offset ) );
				listP[i].sampleSize = theSize;
			}
			break;
		default: errprint("You can't have a field size of %d in stz2\n", fieldSize);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("fieldSize=\"%ld\"\n", fieldSize);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint("/>\n");
	if (entryCount) {
		vg.tabcnt++;
		listP[0].sampleSize = 0;
		for ( i = 1; i <= entryCount; i++ ) {
			atomprintdetailed("<stz2Entry sampleSize=\"%d\" />\n", listP[i].sampleSize);
			if (listP[i].sampleSize == 0) {
				errprint("You can't have a zero sample size in stz2\n");
			}
		}
		--vg.tabcnt;
	}

	// Check required field values
	FieldMustBe( flags, 0, "'stz2' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;
	tir->sampleSizeEntryCnt = entryCount;
	tir->singleSampleSize = 0;
	tir->sampleSize = listP;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_stsc_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	SampleToChunk *listP;
	UInt32 listSize;
	UInt32 i;
	UInt32 sampleToChunkSampleSubTotal = 0;		// total accounted for all but last entry

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );

	if(vg.dashSegment && entryCount != 0)
		errprint("stsc atom, entry_count %d, violating\n of ISO/IEC 23009-1:2012(E), 6.3.3: The tracks in the \"moov\" box shall contain no samples \n(i.e. the entry_count in the \"stts\", \"stsc\", and \"stco\" boxes shall be set to 0)\n",entryCount);

	if (!vg.dashSegment && entryCount == 0) warnprint("WARNING: STSC atom has no entries so is un-needed. If this is a DASH file, then 'dash' is missing as a compatible brand and this is a conformance issue, hence the following program execution is not reliable!!!\n");

	listSize = entryCount * sizeof(SampleToChunk);
			// 1 based array
	BAILIFNIL( listP = (SampleToChunk *)malloc(listSize + sizeof(SampleToChunk)), allocFailedErr );
	BAILIFERR( GetFileData( aoe, &listP[1], offset, listSize, &offset ) );
	for ( i = 1; i <= entryCount; i++ ) {
		listP[i].firstChunk = EndianU32_BtoN(listP[i].firstChunk);
		listP[i].samplesPerChunk = EndianU32_BtoN(listP[i].samplesPerChunk);
		listP[i].sampleDescriptionIndex = EndianU32_BtoN(listP[i].sampleDescriptionIndex);
		if (i > 1) {
			sampleToChunkSampleSubTotal +=
				( listP[i].firstChunk - listP[i-1].firstChunk )
					* ( listP[i-1].samplesPerChunk );
		}
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n");
	vg.tabcnt++;

	if(vg.cmaf && entryCount != 0){
		errprint("CMAF check violated: Section 7.5.12. \"All boxes in SampleTableBox SHALL have or compute a sample count of 0\", found %d\n", entryCount);
	}

	listP[0].firstChunk = listP[0].samplesPerChunk = listP[0].sampleDescriptionIndex = 0;
	for ( i = 1; i <= entryCount; i++ ) {
		atomprintdetailed("<stscEntry firstChunk=\"%d\" samplesPerChunk=\"%d\" sampleDescriptionIndex=\"%d\" />\n",
			listP[i].firstChunk, listP[i].samplesPerChunk, listP[i].sampleDescriptionIndex);

	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'stsc' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

	tir->sampleToChunkEntryCnt = entryCount;
	tir->sampleToChunk = listP;
	tir->sampleToChunkSampleSubTotal = sampleToChunkSampleSubTotal;		// total accounted for all but last entry

bail:
	return err;
}

//==========================================================================================

OSErr Validate_stco_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	ChunkOffsetRecord *listP;
	ChunkOffset64Record *list64P;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	listSize = entryCount * sizeof(ChunkOffsetRecord);
			// 1 based array
	BAILIFNIL( listP = (ChunkOffsetRecord *)malloc(listSize + sizeof(ChunkOffsetRecord)), allocFailedErr );
			// 1 based array
	BAILIFNIL( list64P = (ChunkOffset64Record *)malloc((entryCount + 1) * sizeof(ChunkOffset64Record)), allocFailedErr );
	BAILIFERR( GetFileData( aoe, &listP[1], offset, listSize, &offset ) );
	for ( i = 1; i <= entryCount; i++ ) {
		listP[i].chunkOffset = EndianU32_BtoN(listP[i].chunkOffset);
	}

	if(vg.cmaf && entryCount != 0){
		errprint("CMAF check violated: Section 7.5.12. \"All boxes in SampleTableBox SHALL have or compute a sample count of 0\", found %d\n", entryCount);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);

	if(vg.dashSegment && entryCount != 0)
		errprint("stco atom, entry_count %d, violating\n ISO/IEC 23009-1:2012(E), 6.3.3: The tracks in the \"moov\" box shall contain no samples \n(i.e. the entry_count in the \"stts\", \"stsc\", and \"stco\" boxes shall be set to 0)\n",entryCount);

	atomprint(">\n");
	vg.tabcnt++;
	list64P[0].chunkOffset = listP[0].chunkOffset = 0;
	for ( i = 1; i <= entryCount; i++ ) {
		atomprintdetailed("<stcoEntry chunkOffset=\"%ld\" />\n", listP[i].chunkOffset);
		if (listP[i].chunkOffset == 0) {
			errprint("You can't have a zero sample size in stco\n");
		}
		list64P[i].chunkOffset = listP[i].chunkOffset;
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'stco' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;
	tir->chunkOffsetEntryCnt = entryCount;
	tir->chunkOffset = list64P;
	free(listP);

bail:
	return err;
}

//==========================================================================================

OSErr Validate_co64_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	ChunkOffset64Record *listP;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	listSize = entryCount * sizeof(ChunkOffset64Record);
		// 1 based table
	BAILIFNIL( listP = (ChunkOffset64Record *)malloc(listSize + sizeof(ChunkOffset64Record)), allocFailedErr );
	BAILIFERR( GetFileData( aoe, &listP[1], offset, listSize, &offset ) );
	for ( i = 1; i <= entryCount; i++ ) {
		listP[i].chunkOffset = EndianU64_BtoN(listP[i].chunkOffset);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint("/>\n");
	vg.tabcnt++;
	listP[0].chunkOffset = 0;
	for ( i = 1; i <= entryCount; i++ ) {
		atomprintdetailed("<stcoEntry chunkOffset=\"%s\" />\n", int64todstr(listP[i].chunkOffset));
		if (listP[i].chunkOffset == 0) {
			errprint("You can't have a zero sample size in stsz\n");
		}
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'stco' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;
	tir->chunkOffsetEntryCnt = entryCount;
	tir->chunkOffset = listP;

bail:
	return err;
}

//==========================================================================================

// sync samples can't start from zero
typedef struct SyncSampleRecord {
	UInt32	sampleNum;
} SyncSampleRecord;

OSErr Validate_stss_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	SyncSampleRecord *listP;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	listSize = entryCount * sizeof(SyncSampleRecord);
	BAILIFNIL( listP = (SyncSampleRecord *)malloc(listSize), allocFailedErr );
	BAILIFERR( GetFileData( aoe, listP, offset, listSize, &offset ) );
	for ( i = 0; i < entryCount; i++ ) {
		listP[i].sampleNum = EndianU32_BtoN(listP[i].sampleNum);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n");
	vg.tabcnt++;

	if(vg.cmaf && entryCount != 0){
		errprint("CMAF check violated: Section 7.5.12. \"All boxes in SampleTableBox SHALL have or compute a sample count of 0\", found %d\n", entryCount);
	}

	for ( i = 0; i < entryCount; i++ ) {
		atomprintdetailed("<stssEntry sampleNum=\"%d\" />\n", listP[i].sampleNum);
		if (listP[i].sampleNum == 0) {
			errprint("You can't have a zero sample number in stss\n");
		}
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'stss' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

typedef struct ShadowSyncEntry {
	UInt32	shadowSyncNumber;
	UInt32	syncSampleNumber;
} ShadowSyncEntry;

OSErr Validate_stsh_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	ShadowSyncEntry *listP;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	listSize = entryCount * sizeof(ShadowSyncEntry);
	BAILIFNIL( listP = (ShadowSyncEntry *)malloc(listSize), allocFailedErr );
	BAILIFERR( GetFileData( aoe, listP, offset, listSize, &offset ) );
	for ( i = 0; i < entryCount; i++ ) {
		listP[i].shadowSyncNumber = EndianU32_BtoN(listP[i].shadowSyncNumber);
		listP[i].syncSampleNumber = EndianU32_BtoN(listP[i].syncSampleNumber);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint("/>\n");
	vg.tabcnt++;
	for ( i = 0; i < entryCount; i++ ) {
		atomprintdetailed("<stshEntry shadowSyncNumber=\"%d\" syncSampleNumber=\"%d\" />\n",
			listP[i].shadowSyncNumber, listP[i].syncSampleNumber );
//		if (listP[i].sampleOffset < 0) {
//			errprint("You can't have a negative offset in the ctts table\n");
//		}
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'stsh' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

// sync samples can't start from zero
typedef struct DegradationPriority {
	UInt16 priority; // 15-bits - top bit must be zero
} DegradationPriority;

OSErr Validate_stdp_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	DegradationPriority *listP;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	//BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	entryCount = tir->sampleSizeEntryCnt;

	if (entryCount==0) {
		errprint("Cannot validate stdp box as it must follow sample size box to get entry count\n");
		err = badAtomErr;
		goto bail;
	}

	listSize = entryCount * sizeof(DegradationPriority);
	BAILIFNIL( listP = (DegradationPriority *)malloc(listSize), allocFailedErr );
	BAILIFERR( GetFileData( aoe, listP, offset, listSize, &offset ) );
	for ( i = 0; i < entryCount; i++ ) {
		listP[i].priority = EndianU16_BtoN(listP[i].priority);
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint("/>\n");
	vg.tabcnt++;
	for ( i = 0; i < entryCount; i++ ) {
		atomprintdetailed("<stdpEntry priority=\"%d\" />\n", listP[i].priority);
		if (listP[i].priority & (1<<15)) {
			errprint("priority top bit must be zero in stdp\n");
		}
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'stdp' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_sdtp_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	UInt8 *listP;
	UInt32 listSize;
	UInt32 i;

	static char* sample_depends_on[] = {
		(char *)"unk",
		(char *)"dpnds",
		(char *)"not-dpnds",
		(char *)"res" };
	static char * sample_is_depended_on[] = {
		(char *)"unk",
		(char *)"dpdned-on",
		(char *)"not-depnded-on",
		(char *)"res" };
	static char * sample_has_redundancy[] = {
		(char *)"unk",
		(char *)"red-cod",
		(char *)"no-red-cod",
		(char *)"res" };

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	//BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	entryCount = tir->sampleSizeEntryCnt;

	if (entryCount==0) {
		errprint("Cannot validate sdtp box as it must follow sample size box to get entry count\n");
		err = badAtomErr;
		goto bail;
	}

	listSize = entryCount * sizeof(UInt8);
	BAILIFNIL( listP = (UInt8 *)malloc(listSize), allocFailedErr );
	BAILIFERR( GetFileData( aoe, listP, offset, listSize, &offset ) );

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint("/>\n");
	vg.tabcnt++;
	for ( i = 0; i < entryCount; i++ ) {
		UInt8 resvd, depends, dependedon, red;
		resvd =		 ((listP[i]) >> 6) & 3;
		depends =	 ((listP[i]) >> 4) & 3;
		dependedon = ((listP[i]) >> 2) & 3;
		red =		 ((listP[i]) >> 0) & 3;

		atomprintdetailed("<sdtpEntry %d=\"%s,%s,%s\" />\n",
			i,
			sample_depends_on[depends],
			sample_is_depended_on[dependedon],
			sample_has_redundancy[red] );
		if (resvd != 0) {
			errprint("reserved bits %d in sdtp, should be 0\n",resvd);
		}
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'sdtp' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_padb_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	UInt8 *listP;
	UInt32 listSize;
	UInt32 i;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );

	listSize = entryCount * sizeof(UInt8);
		// 1 based array + room for one over for the 4-bit case loop
	BAILIFNIL( listP = (UInt8 *)malloc(listSize + sizeof(UInt8) + sizeof(UInt8)), allocFailedErr );

	for (i=0; i<((entryCount+1)/2); i++) {
		UInt8 thePads;
		BAILIFERR( GetFileData( aoe, &thePads, offset, 1, &offset ) );
		listP[i*2 + 1] = (thePads >> 4) & 0x7;
		listP[i*2 + 2] = thePads & 0x07;
		if ((thePads & 0x84) !=0) errprint("reserved bits must be zero in padding bits entries\n");
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint("/>\n");
	vg.tabcnt++;
	for ( i = 1; i <= entryCount; i++ ) {
		atomprintdetailed("<paddingBitsEntry %d=%d />\n",
			i,
			listP[i] );
	}
	--vg.tabcnt;

	// Check required field values
	FieldMustBe( flags, 0, "'padb' flags must be %d not 0x%lx" );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================


OSErr Validate_elst_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	TrackInfoRec	*tir = (TrackInfoRec*)refcon;
	EditListEntryVers1Record *listP = NULL;
	UInt32 listSize;
	UInt32 i;
 //   Fixed mediaRate_1_0 = EndianU32_BtoN(0x10000);
	Fixed mediaRate_1_0 = 0x10000;
	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );




	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
	if (entryCount == 0) {
		errprint("Edit list has an illegal entryCount of zero\n");
	}
	if (entryCount) {


		listSize = entryCount * sizeof(EditListEntryVers1Record);
		BAILIFNIL( listP = (EditListEntryVers1Record *)malloc(listSize), allocFailedErr );

		if (version == 0) {
			UInt32 list0Size;
			EditListEntryVers0Record *list0P;

			list0Size = entryCount * sizeof(EditListEntryVers0Record);
			BAILIFNIL( list0P = (EditListEntryVers0Record *)malloc(list0Size), allocFailedErr );
			BAILIFERR( GetFileData( aoe, list0P, offset, list0Size, &offset ) );
			for ( i = 0; i < entryCount; i++ ) {
				listP[i].duration = EndianU32_BtoN(list0P[i].duration);
				listP[i].mediaTime = EndianS32_BtoN(list0P[i].mediaTime);
				listP[i].mediaRate = EndianU32_BtoN(list0P[i].mediaRate);

				//Didnt understand why it was repeated here below, this seems to create a bug: we do need conversion as above, but its overwritten here below!
				//listP[i].duration = list0P[i].duration;
				//listP[i].mediaTime = (SInt64)((Int32)list0P[i].mediaTime);
				//listP[i].mediaRate = list0P[i].mediaRate;

			}
		} else if (version == 1) {
			BAILIFERR( GetFileData( aoe, listP, offset, listSize, &offset ) );
			for ( i = 0; i < entryCount; i++ ) {
				listP[i].duration = EndianU64_BtoN(listP[i].duration);
				listP[i].mediaTime = EndianS64_BtoN(listP[i].mediaTime);
				listP[i].mediaRate = EndianU32_BtoN(listP[i].mediaRate);
			}
		} else {
			errprint("Edit list is version other than 0 or 1\n");
			err = badAtomErr;
			goto bail;
		}
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n");
		if(vg.cmaf)
		{
			if(entryCount!=1)
				errprint("CMAF check violated: Section 7.5.13. \"An offset edit list SHALL be a single EditListBox in an EditBox, i.e., entryCount SHALL be 1\", found %ld\n", entryCount);
		}

	vg.tabcnt++;
	for ( i = 0; i < entryCount; i++ ) {
		atomprint("<elstEntry duration=\"%s\"", int64todstr(listP[i].duration));
		atomprintnotab(" mediaTime=\"%s\"", int64todstr(listP[i].mediaTime));
		if ((listP[i].mediaTime < 0) && (listP[i].mediaTime != -1))
			errprint("Edit list:  the only allowed negative value for media time is -1\n");
		atomprintnotab(" mediaRate=\"%s\" />\n", fixed32str(listP[i].mediaRate));

		if(vg.cmaf){
			if(version == 0 || version == 1){
				if(listP[i].duration != 0){
					errprint("CMAF check violated: Section 7.5.13. \"A start offset edit list SHALL be defined as a single Edit List Box with segment-duration = 0\", found %s\n", int64todstr(listP[i].duration));
				}
				//if(listP[i].mediaTime != tir->mediaTimeScale){
				//	errprint("CMAF check violated: Section 7.5.12. \"A start offset edit list SHALL be defined as a single Edit List Box with media-time = offset from the start of the first Fragment measured in the Track timescale\", found %ld\n", listP[i].mediaTime);
				//}
				if(strcmp(fixed32str(listP[i].mediaRate), "1.000000")!=0){
					errprint("CMAF check violated: Section 7.5.13. \"A start offset edit list SHALL be defined as a single Edit List Box with media-rate = 1\", found %s\n", fixed32str(listP[i].mediaRate));
				}

				if( strstr(fixed32str(listP[i].mediaRate), ".000000") == NULL ){
										errprint("CMAF check violated: Section 7.5.13. \"The value of media_rate_fraction field SHALL be set to 0\", media_rate found %s\n", fixed32str(listP[i].mediaRate));
								}
			}
		}

		//switch (vg.filetype) {
		//	default:		// ISO family
				if ((listP[i].mediaRate != mediaRate_1_0) && (listP[i].mediaRate != 0))
					errprint("Edit list: media rate can only be 0 or 1, not 0x%0X\n", listP[i].mediaRate);
				//break;
		//}

//		if (listP[i].mediaRate & 0x0000ffff) {
//			errprint("mpeg4 in their infinite wisdom has declared -1(Fixed) is 0xffff0000 NOT 0xffffffff\n");
//		}
	}
	--vg.tabcnt;

	tir->elstInfo = listP;
	tir->numEdits = entryCount;

	// Check required field values

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;

}


//==========================================================================================

TrackInfoRec * check_track( UInt32 theID );


OSErr Validate_tref_type_Atom( atomOffsetEntry *aoe, void *refcon, OSType trefType, UInt32 *firstRefTrackID )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	UInt32 entryCount;
	UInt32 *listP;
	UInt32 listSize;
	UInt32 i;

	// Get data
	offset = aoe->offset + aoe->atomStartSize;
	listSize = (UInt32)(aoe->size - aoe->atomStartSize);
	entryCount = listSize / sizeof(UInt32);
	BAILIFNIL( listP = (UInt32 *)malloc(listSize), allocFailedErr );
	BAILIFERR( GetFileData( aoe, listP, offset, listSize, &offset ) );
	for ( i = 0; i < entryCount; i++ ) {
		listP[i] = EndianU32_BtoN(listP[i]);
		check_track( listP[i] );
	}

	// Print atom contents non-required fields
	atomprintnotab(">\n");
	//vg.tabcnt++;
	for ( i = 0; i < entryCount; i++ ) {
		atomprint("<tref%sEntry trackID=\"%ld\" />\n", ostypetostr(trefType), listP[i]);
	}
	//--vg.tabcnt;

	if ((entryCount > 0) && (firstRefTrackID != NULL)) {
		*firstRefTrackID = listP[0];
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_tref_hint_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr			err = noErr;
	UInt32			refTrackID = 0;
	TrackInfoRec	*tir = (TrackInfoRec*)refcon;

	err = Validate_tref_type_Atom( aoe, refcon, 'hint', &refTrackID );
	if (err == noErr) {
		tir->hintRefTrackID = refTrackID;
	}
	return err;
}

OSErr Validate_tref_dpnd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	return Validate_tref_type_Atom( aoe, refcon, 'dpnd', NULL );
}

OSErr Validate_tref_ipir_Atom( atomOffsetEntry *aoe, void *refcon )
{
	return Validate_tref_type_Atom( aoe, refcon, 'ipir', NULL );
}

OSErr Validate_tref_mpod_Atom( atomOffsetEntry *aoe, void *refcon )
{
	return Validate_tref_type_Atom( aoe, refcon, 'mpod', NULL);
}

OSErr Validate_tref_sync_Atom( atomOffsetEntry *aoe, void *refcon )
{
	return Validate_tref_type_Atom( aoe, refcon, 'sync', NULL );
}


//==========================================================================================


OSErr Validate_stsd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entryCount;
	SampleDescriptionPtr *sampleDescriptionPtrArray;
	UInt32 *validatedSampleDescriptionRefCons;
	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &entryCount, offset, &offset ) );
		// 1 based table
	BAILIFNULL( sampleDescriptionPtrArray = (SampleDescriptionPtr *)calloc((entryCount + 1), sizeof(SampleDescriptionPtr)), allocFailedErr );
	BAILIFNULL( validatedSampleDescriptionRefCons = (UInt32 *)calloc((entryCount + 1), sizeof(UInt32)), allocFailedErr );

	if(vg.cmaf){
		if(version != 0){
			errprint("CMAF check violated: Section 7.5.10. \"Sample Description Boxes in a CMAF Track SHALL conform to verion 0\", found %d\n", version);
		}

		//if(entryCount != 0){
		//	errprint("CMAF check violated: Section 7.5.11. \"All boxes in SampleTableBox SHALL have or compute a sample count of 0\", found %d\n", entryCount);
		//}
	}

	// Print atom contents non-required fields
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entryCount);
	atomprint(">\n"); //vg.tabcnt++;

	// Check required field values
	FieldMustBe( flags, 0, "'dref' flags must be %d not 0x%lx" );

	//need to validate sample descriptions
	tir->sampleDescriptionCnt = entryCount;
	tir->sampleDescriptions = sampleDescriptionPtrArray;
	tir->validatedSampleDescriptionRefCons = validatedSampleDescriptionRefCons;
	{
		UInt64 minOffset, maxOffset;
		atomOffsetEntry *entry;
		long cnt;
		atomOffsetEntry *list;
		int i;

		minOffset = offset;
		maxOffset = aoe->maxOffset;

		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

		if (cnt != 1) {
			errprint( "MPEG-4 only allows 1 sample description\n" );
			err = badAtomErr;
		}

		for (i = 0; i < cnt; i++) {
			entry = &list[i];


			{  // stash the sample description
				SampleDescriptionPtr sdp;
				sdp = (SampleDescriptionPtr)malloc( (size_t)entry->size );
				err = GetFileData( entry, (void*)sdp, entry->offset, entry->size, nil );
				sampleDescriptionPtrArray[i+1] = sdp;
			}
			atomprint("<%s_sampledescription\n",ostypetostr(tir->mediaType)); vg.tabcnt++;
			tir->currentSampleDescriptionIndex = i+1;

			switch( tir->mediaType ) {
				case 'vide':
					err = Validate_vide_SD_Entry( entry, refcon );
					break;

				case 'soun':
					err = Validate_soun_SD_Entry( entry, refcon );
					break;

				case 'hint':
					err = Validate_hint_SD_Entry( entry, refcon );
					break;

				case 'sdsm':
					err = Validate_mp4_SD_Entry( entry, refcon, Validate_sdsm_ES_Bitstream, (char *)"sdsm_ES" );
					break;

				case 'odsm':
					err = Validate_mp4_SD_Entry( entry, refcon, Validate_odsm_ES_Bitstream, (char *)"odsm_ES" );
					break;

								case 'subt':
										err = Validate_subt_SD_Entry( entry, refcon );
										break;
					
								case 'text':
										err = Validate_text_SD_Entry( entry, refcon );
										break;

				default:
					// why does MP4 say it must be an MpegSampleEntry?
					//   So by default you can't have any other media type!!!
					err = Validate_mp4_SD_Entry( entry, refcon, Validate_mp4s_ES_Bitstream, (char *)"mp4_ES" );
					break;
			}
			--vg.tabcnt; atomprint("</%s_sampledescription>\n",ostypetostr(tir->mediaType));

		}

	}


	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}


//==========================================================================================

OSErr Validate_vide_SD_Entry( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	UInt64 offset;
	SampleDescriptionHead sdh;
	VideoSampleDescriptionInfo vsdi;
	char stringSize;
	OSErr atomerr = noErr;
	offset = aoe->offset;
	// vsdi_name is 1 byte for the size, and 1 to 31 bytes string, so at most 32.
	char vsdi_name[32];
	memset(vsdi_name, 0, sizeof(vsdi_name));

	// Get data
	BAILIFERR( GetFileData( aoe, &sdh, offset, sizeof(sdh), &offset ) );
	EndianSampleDescriptionHead_BtoN( &sdh );

	// Note the sample description for both 'mp4v' and 's263' are the same in all these fields
	BAILIFERR( GetFileData( aoe, &vsdi, offset, (UInt64)fieldOffset(VideoSampleDescriptionInfo,extensions) /* sizeof(vsdi) */, &offset ) );
	vsdi.version = EndianS16_BtoN(vsdi.version);
	vsdi.revisionLevel = EndianS16_BtoN(vsdi.revisionLevel);
	vsdi.vendor = EndianU32_BtoN(vsdi.vendor);
	vsdi.temporalQuality = EndianU32_BtoN(vsdi.temporalQuality);
	vsdi.spatialQuality = EndianU32_BtoN(vsdi.spatialQuality);
	vsdi.width = EndianS16_BtoN(vsdi.width);
	vsdi.height = EndianS16_BtoN(vsdi.height);
	
	
	tir->sampleDescWidth = vsdi.width; tir->sampleDescHeight = vsdi.height;
	/*if ((tir->trackWidth>>16) != vsdi.width) {
		warnprint("WARNING: Sample description width %d not the same as track width %s\n",vsdi.width,fixedU32str(tir->trackWidth));
	}
	if ((tir->trackHeight>>16) != vsdi.height) {
		warnprint("WARNING: Sample description height %d not the same as track height %s\n",vsdi.height,fixedU32str(tir->trackHeight));
	}*/
	if ((vsdi.width==0) || (vsdi.height==0)) {
		errprint("Visual Sample description height (%d) or width (%d) zero\n",vsdi.height,vsdi.width);
	}

	if(vg.width != 0 && vg.height != 0){
			float mpd_ratio = ((float)(vg.width * vg.sarx))/((float)(vg.height * vg.sary));
			float tkhd_ratio = ((float)(tir->trackWidth))/((float)(tir->trackHeight));
			if(mpd_ratio != tkhd_ratio){
				errprint("Track header box width:height %f:%f is not matching the MPD width:height %d:%d on a grid determined by the @sar attribute %d:%d.\n",((float)(tir->trackWidth>>16)), ((float)(tir->trackHeight>>16)), vg.width, vg.height, vg.sarx, vg.sary);
			}
		}

	vsdi.hRes = EndianU32_BtoN(vsdi.hRes);
	vsdi.vRes = EndianU32_BtoN(vsdi.vRes);
	vsdi.dataSize = EndianU32_BtoN(vsdi.dataSize);
	vsdi.frameCount = EndianS16_BtoN(vsdi.frameCount);
	strcpy(vsdi_name, vsdi.name);
	vsdi.depth = EndianS16_BtoN(vsdi.depth);
	vsdi.clutID = EndianS16_BtoN(vsdi.clutID);
	// Print atom contents non-required fields
	atomprint("sdType=\"%s\"\n", ostypetostr(sdh.sdType));
	atomprint("dataRefIndex=\"%ld\"\n", sdh.dataRefIndex);
	// atomprint(">\n"); //vg.tabcnt++;

	if(vsdi_name[0] == '\v' || vsdi_name[0]== '\017')	//to make the vsdi.name be acceptable by xml
	  vsdi_name[0] = '\\';
	// Check required field values
	atomprint("version=\"%hd\"\n", vsdi.version);
	atomprint("revisionLevel=\"%hd\"\n", vsdi.revisionLevel);
	atomprint("vendor=\"%s\"\n", ostypetostr(vsdi.vendor));
	atomprint("temporalQuality=\"%ld\"\n", vsdi.temporalQuality);
	atomprint("spatialQuality=\"%ld\"\n", vsdi.spatialQuality);
	atomprint("width=\"%hd\"\n", vsdi.width);
	atomprint("height=\"%hd\"\n", vsdi.height);
	atomprint("hRes=\"%s\"\n", fixedU32str(vsdi.hRes));
	atomprint("vRes=\"%s\"\n", fixedU32str(vsdi.vRes));
	atomprint("dataSize=\"%ld\"\n", vsdi.dataSize);
	atomprint("frameCount=\"%hd\"\n", vsdi.frameCount);
	//atomprint("name=\"%s\"\n", vsdi_name);//This creates problems in xml printing and crashes Rep processing.
	atomprint("depth=\"%hd\"\n", vsdi.depth);
	atomprint("clutID=\"%hd\"\n", vsdi.clutID);
	atomprint(">\n");
		FieldMustBeOneOf8( sdh.sdType, OSType, "SampleDescription sdType must be 'mp4v', 'avc1', 'avc3', 'avc4', 'encv', 'hev1','hvc1', or 'vp09'", ('mp4v', 'avc1', 'avc3', 'avc4', 'encv', 'hev1','hvc1','vp09') );

	FieldMustBe( sdh.resvd1, 0, "SampleDescription resvd1 must be %d not %d" );
	FieldMustBe( sdh.resvdA, 0, "SampleDescription resvd1 must be %d not %d" );


	FieldMustBe( vsdi.version, 0, "ImageDescription version must be %d not %d" );
	FieldMustBe( vsdi.revisionLevel, 0, "ImageDescription revisionLevel must be %d not %d" );
	FieldMustBe( vsdi.vendor, 0, "ImageDescription vendor must be %d not 0x%lx" );
	FieldMustBe( vsdi.temporalQuality, 0, "ImageDescription temporalQuality must be %d not %d" );
	FieldMustBe( vsdi.spatialQuality, 0, "ImageDescription spatialQuality must be %d not %d" );

	FieldMustBe( vsdi.hRes, 72L<<16, "ImageDescription hRes must be 72.0 (0x%lx) not 0x%lx" );
	FieldMustBe( vsdi.vRes, 72L<<16, "ImageDescription vRes must be 72.0 (0x%lx) not 0x%lx" );
	FieldMustBe( vsdi.dataSize, 0, "ImageDescription dataSize must be %d not %d" );
	FieldMustBe( vsdi.frameCount, 1, "ImageDescription frameCount must be %d not %d" );

	stringSize = vsdi.name[0]; 
	// should check the whole string
	FieldCheck(stringSize >= 0, "Compressorname size must be >= 0");
	FieldCheck(stringSize <= 31, "Compressorname size must be <= 31");
	FieldMustBe(strlen(&vsdi.name[1]), (size_t)stringSize, "Compressorname size must be %d not %d" );
	//FieldMustBe( vsdi.name[0], 0, "ImageDescription name must be '%d' not '%d'" );
	FieldMustBe( vsdi.depth, 24, "ImageDescription depth must be %d not %d" );
	FieldMustBe( vsdi.clutID, -1, "ImageDescription clutID must be %d not %d" );

		// Check whether height and width are matching with those from MPD
		if((unsigned)vsdi.width != vg.width)
		{
			errprint("Width in video sample description (%d) is not matching with the width in the MPD (%d) \n",vsdi.width, vg.width);
		}
		if((unsigned)vsdi.height != vg.height)
		{
			errprint("Height in video sample description (%d) is not matching with othe width in the MPD (%d) \n",vsdi.height, vg.height);
		}

		// Now we have the Sample Extensions
		{
			UInt64 minOffset, maxOffset;
			atomOffsetEntry *entry;
			long cnt;
			atomOffsetEntry *list;
			int i;
			int is_protected = 0;
			int sinfFound =0;

			minOffset = offset;
			maxOffset = aoe->offset + aoe->size;

			is_protected = ( sdh.sdType == 'drmi' ) || (( (sdh.sdType & 0xFFFFFF00) | ' ') == 'enc ' );

			BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
			if ((cnt != 1) && (sdh.sdType == 'mp4v')) {
				errprint( "MPEG-4 only allows 1 sample description extension\n" );
				err = badAtomErr;
			}

			for (i = 0; i < cnt; i++) {
				entry = &list[i];

				if (entry->type == 'esds') {
					BAILIFERR( Validate_ESDAtom( entry, refcon, Validate_vide_ES_Bitstream, (char *)"vide_ES" ) );
				}
				else if ( entry->type == 'uuid' )
				{
					// Process 'uuid' atoms
					atomprint("<uuid"); vg.tabcnt++;
					BAILIFERR( Validate_uuid_Atom( entry, refcon ) );
					--vg.tabcnt; atomprint("</uuid>\n");
				}
				else if ( entry->type == 'sinf' )
				{
					// Process 'sinf' atoms
										sinfFound=1;
					atomprint("<sinf"); vg.tabcnt++;
					BAILIFERR( Validate_sinf_Atom( entry, refcon, kTypeAtomFlagMustHaveOne ) );
					--vg.tabcnt; atomprint("</sinf>\n");
				}
				else if ( entry->type == 'colr' )
				{
					// Process 'colr' atoms
					atomprint("<colr"); vg.tabcnt++;
					BAILIFERR( Validate_colr_Atom( entry, refcon ) );
					--vg.tabcnt; atomprint("</colr>\n");
				}

				else if ((sdh.sdType == 'avc1' || sdh.sdType == 'avc3') || (is_protected && entry->type != 'hvcC'))
				{
					if (entry->type == 'avcC') {
						atomerr= Validate_avcC_Atom( entry, refcon, (char *)"avcC" );
												if (!err) err = atomerr;
					}
					else if (entry->type == 'svcC') {
						BAILIFERR( Validate_avcC_Atom( entry, refcon, (char *)"svcC" ) );
					}
					else if (entry->type == 'mvcC') {
						BAILIFERR( Validate_avcC_Atom( entry, refcon, (char *)"mvcC" ) );
					}
					else if ( entry->type == 'btrt'){
						BAILIFERR( Validate_btrt_Atom( entry, refcon, (char *)"btrt" ) );
					}
					else if ( entry->type == 'm4ds' ){
						BAILIFERR( Validate_m4ds_Atom( entry, refcon, (char *)"m4ds" ) );
					}
					else if (entry->type == 'pasp')
										{
											BAILIFERR( Validate_pasp_Atom( entry, refcon, (char *)"pasp" ) );
										}
					else {
						err = badAtomErr;
						warnprint("Warning: In %s - unknown atom found \"%s\": video sample descriptions would not normally contain this\n",vg.curatompath, ostypetostr(entry->type));
						//goto bail;
					}
				}
				else if (((( sdh.sdType == 'hev1' ) || ( sdh.sdType == 'hvc1' )) || is_protected) && (vg.cmaf || vg.dvb || vg.hbbtv || vg.ctawave || vg.dash264base))
				{
					if (entry->type == 'hvcC') {
						atomerr= Validate_hvcC_Atom( entry, refcon, (char *)"hvcC" );
												if (!err) err = atomerr;
					}
					else {
						err = badAtomErr;
						warnprint("Warning: In %s - unknown atom found \"%s\": video sample descriptions would not normally contain this\n",vg.curatompath, ostypetostr(entry->type));
						//goto bail;
					}
				}
				else {
					err = badAtomErr;
					warnprint("Warning: %s - unknown atom found \"%s\": video sample descriptions would not normally contain this\n",vg.curatompath, ostypetostr(entry->type));
					// goto bail;
				}

			}
			if(vg.cmaf && is_protected && sinfFound!=1)
							errprint("CMAF check violated: Section 7.5.10. \"Sample Entries for encrypted tracks SHALL encapsulate the existing sample entry with a Protection Scheme Information Box ('sinf')\", but 'sinf' not found. \n");
			if(vg.cmaf && vg.dash264enc && sinfFound!=1)
							errprint("CMAF check violated: Section 7.5.11. \"An encrypted CMAF Track SHALL include at least one Protection Scheme Information Box ('sinf') \", found %d\n", 0);
		}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}


//==========================================================================================


OSErr Validate_trex_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 track_ID;
	TrackInfoRec *tir;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &track_ID, offset, &offset ) );

	tir = check_track(track_ID);

	if(tir == 0)
		return badAtomErr;

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &tir->default_sample_description_index, offset, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &tir->default_sample_duration, offset, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &tir->default_sample_size, offset, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &tir->default_sample_flags, offset, &offset ) );

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("trackID=\"%ld\"\n", track_ID);
	atomprint("sampleDescriptionIndex=\"%ld\"\n", tir->default_sample_description_index);
	atomprint("sampleDuration=\"%ld\"\n", tir->default_sample_duration);
	atomprint("sampleSize=\"%ld\"\n", EndianU32_BtoN(tir->default_sample_size));
	atomprint("sampleFlags=\"%ld\"\n", EndianU32_BtoN(tir->default_sample_flags));
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}


//==========================================================================================


OSErr Validate_mehd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 temp;
	MovieInfoRec	*mir = (MovieInfoRec	*)refcon;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	if(version == 1)
		BAILIFERR( GetFileDataN64( aoe, &mir->fragment_duration, offset, &offset ) );
	else
	{
		BAILIFERR( GetFileDataN32( aoe, &temp, offset, &offset ) );
		mir->fragment_duration = temp;
	}

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("fragmentDuration=\"%lld\"\n", (mir->fragment_duration));
	atomprint(">\n");

		if(vg.cmaf && mir->fragment_duration <=0)
			errprint("CMAF checks violated: Section 7.3.2.1. \"If 'mehd' is present, SHALL provide the overall duration of a fragmented movie. If duration \
			is unknown, this box SHALL be omitted.\", but duration found as %d",mir->fragment_duration);
		if(vg.dashifll && mir->fragment_duration <=0)
			errprint("CMAF checks violated: Section 7.3.2.1. \"If 'mehd' is present, SHALL provide the overall duration of a fragmented movie. If duration \
			is unknown, this box SHALL be omitted.\", but duration found as %d",mir->fragment_duration);

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}


//==========================================================================================


OSErr Validate_trep_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
		UInt32 track_id;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
		BAILIFERR( GetFileDataN32( aoe, &track_id, offset, &offset ) );

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("track_id=\"%lld\"\n", track_id);
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}


//==========================================================================================

OSErr Validate_mfhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	MoofInfoRec *moofInfo = (MoofInfoRec *)refcon;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &moofInfo->sequence_number, offset, &offset ) );

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("sequenceNumber=\"%ld\"\n", moofInfo->sequence_number);
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}


//==========================================================================================

OSErr Validate_tfhd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 tf_flags;
	UInt64 offset;
	TrafInfoRec *trafInfo = (TrafInfoRec *)refcon;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &tf_flags, &offset ) );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, tf_flags);

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &trafInfo->track_ID, offset, &offset ) );
	atomprint("trackID=\"%ld\"\n", trafInfo->track_ID);

	TrackInfoRec *tir;
	tir = check_track(trafInfo->track_ID);

	if(tir == 0)
		return badAtomErr;

	trafInfo->base_data_offset_present =  ((tf_flags & 0x000001) != 0);
	trafInfo->sample_description_index_present =  ((tf_flags & 0x000002) != 0);
	trafInfo->default_sample_duration_present =  ((tf_flags & 0x000008) != 0);
	trafInfo->default_sample_size_present =  ((tf_flags & 0x000010) != 0);
	trafInfo->default_sample_flags_present =  ((tf_flags & 0x000020) != 0);
	trafInfo->duration_is_empty =  ((tf_flags & 0x010000) != 0);
	trafInfo->default_base_is_moof =  ((tf_flags & 0x020000) != 0);

	if(vg.dashSegment && !trafInfo->default_base_is_moof)
		errprint("default-base-is-moof is not set, violating ISO/IEC 23009-1:2012(E), 6.3.4.2: ... the flag 'default-base-is-moof' shall be set\n");

	if(vg.dashSegment && trafInfo->base_data_offset_present)
		errprint("base-data-offset-present is set, violating ISO/IEC 23009-1:2012(E), 6.3.4.2: ... base-data-offset-present shall not be used\n");

	if(trafInfo->base_data_offset_present)
		BAILIFERR( GetFileDataN64( aoe, &trafInfo->base_data_offset, offset, &offset ) );

	if(trafInfo->sample_description_index_present)
		BAILIFERR( GetFileDataN32( aoe, &trafInfo->sample_description_index, offset, &offset ) );
	else{
		if(vg.cmaf)
			errprint("CMAF check 'cmf2' violated: Section 7.7.3. \"Default values or per sample values SHALL be stored in each CMAF chunk's TrackFragmentBoxHeader and/or TrackRunBox\", 'sample_description_index' not found.\n");
	}

	if(trafInfo->default_sample_duration_present)
		BAILIFERR( GetFileDataN32( aoe, &trafInfo->default_sample_duration, offset, &offset ) );
	else
		trafInfo->default_sample_duration = tir->default_sample_duration;   //"Effective" default in that case

	if(trafInfo->default_sample_size_present)
		BAILIFERR( GetFileDataN32( aoe, &trafInfo->default_sample_size, offset, &offset ) );
	else
		trafInfo->default_sample_size = tir->default_sample_size;   //"Effective" default in that case

	if(trafInfo->default_sample_flags_present)
		BAILIFERR( GetFileDataN32( aoe, &trafInfo->default_sample_flags, offset, &offset ) );
	else
			trafInfo->default_sample_flags = tir->default_sample_flags;

	if(vg.cmaf){
 	if(trafInfo->track_ID != tir->trackID){
 		errprint("CMAF check violated: Section 7.5.16. \"The track_ID field SHALL contain the same value as the track_ID in the matching CMAF Header\", found %ld\n", trafInfo->track_ID);
 	}
	if(trafInfo->base_data_offset_present != 0){
		errprint("CMAF check violated: Section 7.5.16. \"The base-data-offset-present flag SHALL be set to zero\", found %d\n", trafInfo->base_data_offset_present);
	}
	if(trafInfo->default_base_is_moof != 1){
		errprint("CMAF check violated: Section 7.5.16. \"The default-base-is-moof flag SHALL be set to one\", found %d\n", trafInfo->default_base_is_moof);
	}
	}

	atomprint("baseDataOffset=\"%ld\"\n", EndianU64_BtoN(trafInfo->base_data_offset));
	atomprint("sampleDescriptionIndex=\"%ld\"\n", trafInfo->sample_description_index);
	atomprint("defaultSampleDuration=\"%ld\"\n", trafInfo->default_sample_duration);
	atomprint("defaultSampleSize=\"%ld\"\n", EndianU32_BtoN(trafInfo->default_sample_size));
	atomprint("defaultSampleFlags=\"%ld\"\n", EndianU32_BtoN(trafInfo->default_sample_flags));
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;

}

//==========================================================================================

OSErr Validate_trun_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 tr_flags;
	UInt64 offset;
	UInt32 i;
	UInt64 prevTrunCummulatedSampleDuration = 0;
	TrafInfoRec *trafInfo = (TrafInfoRec *) refcon;
	UInt64 sampleSizesTotal = 0;
	MoofInfoRec *moofInfo = (MoofInfoRec*)trafInfo->moofInfo;
	TrunInfoRec *trunInfo = &trafInfo->trunInfo[trafInfo->processedTrun];
	char TOC [8192];

	trunInfo->cummulatedSampleDuration = 0;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &trunInfo->version, &tr_flags, &offset ) );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", trunInfo->version, tr_flags);

	trunInfo->data_offset_present = (tr_flags & 0x000001)!=0;
	trunInfo->first_sample_flags_present = (tr_flags & 0x000004)!=0;
	trunInfo->sample_duration_present = (tr_flags & 0x000100)!=0;
	trunInfo->sample_size_present = (tr_flags & 0x000200)!=0;
	trunInfo->sample_flags_present = (tr_flags & 0x000400)!=0;
	trunInfo->sample_composition_time_offsets_present = (tr_flags & 0x000800)!=0;

	BAILIFERR( GetFileDataN32( aoe, &trunInfo->sample_count, offset, &offset ) );
	atomprint("sampleCount=\"%ld\"\n", trunInfo->sample_count);

	if(trunInfo->data_offset_present)
		BAILIFERR( GetFileDataN32( aoe, &trunInfo->data_offset, offset, &offset ) );

	if(trunInfo->first_sample_flags_present)
		BAILIFERR( GetFileDataN32( aoe, &trunInfo->first_sample_flags, offset, &offset ) );

	if(trunInfo->sample_count > 0)
	{
		//if(trunInfo->sample_duration_present)
			trunInfo->sample_duration = (UInt32 *)malloc(trunInfo->sample_count*sizeof(UInt32));
		//else  //use defaults then
		//	trunInfo->sample_duration = NULL;

		//if(trunInfo->sample_size_present)
			trunInfo->sample_size = (UInt32 *)malloc(trunInfo->sample_count*sizeof(UInt32));
		//else  //use defaults then
		//	trunInfo->sample_size = NULL;

		//if(trunInfo->sample_flags_present)
			trunInfo->sample_flags = (UInt32 *)malloc(trunInfo->sample_count*sizeof(UInt32));
		//else  //use defaults then
		//	trunInfo->sample_flags = NULL;

		//if(trunInfo->sample_composition_time_offsets_present)
		{
			trunInfo->sample_composition_time_offset = (UInt32 *)malloc(trunInfo->sample_count*sizeof(UInt32));
			trunInfo->samplePresentationTime = (long double *)malloc(trunInfo->sample_count*sizeof(long double));   //For later use after applying edits
			trunInfo->sampleToBePresented = (Boolean *)malloc(trunInfo->sample_count*sizeof(Boolean));   //For later use after applying edits
			trunInfo->sap3 = (Boolean *)malloc(trunInfo->sample_count*sizeof(Boolean));
			trunInfo->sap4 = (Boolean *)malloc(trunInfo->sample_count*sizeof(Boolean));

			for(i = 0 ; i < trunInfo->sample_count ; i++)
			{
				trunInfo->samplePresentationTime[i] = 0.0;
				trunInfo->sampleToBePresented[i] = true;	//By default true, unless edit lists decide elsewise
				trunInfo->sap3[i] = false;
				trunInfo->sap4[i] = false;
			}
		}
		//else
		//	trunInfo->sample_composition_time_offset = NULL;
	}

	for(i = 0 ; i < trafInfo->processedTrun ; i++)
		prevTrunCummulatedSampleDuration += trafInfo->trunInfo[i].cummulatedSampleDuration;

	for(i = 0 ;  i < trunInfo->sample_count ; i++)
	{
		 UInt64 savedCummulatedSampleDuration = trunInfo->cummulatedSampleDuration;
		 UInt32 currentSampleDecodeDelta;

		if(trunInfo->sample_duration_present)
		{
			BAILIFERR( GetFileDataN32( aoe, &trunInfo->sample_duration[i], offset, &offset ) );
			currentSampleDecodeDelta = trunInfo->sample_duration[i];
		}
		else
		{
			trunInfo->sample_duration[i] = trafInfo->default_sample_duration;
			currentSampleDecodeDelta = trafInfo->default_sample_duration;
			if(vg.cmaf && !trafInfo->default_sample_duration_present)
				errprint("CMAF check 'cmf2' violated: Section 7.7.3. \"Default values or per sample values SHALL be stored in each CMAF chunk's TrackFragmentBoxHeader and/or TrackRunBox\", 'duration' not found in any of them. \n");
		}

		trunInfo->cummulatedSampleDuration += currentSampleDecodeDelta;

		if(trunInfo->sample_size_present)
			BAILIFERR( GetFileDataN32( aoe, &trunInfo->sample_size[i], offset, &offset ) );
		else{
			trunInfo->sample_size[i] = trafInfo->default_sample_size;
						if(vg.cmaf && !trafInfo->default_sample_size_present)
							errprint("CMAF check 'cmf2' violated: Section 7.7.3. \"Default values or per sample values SHALL be stored in each CMAF chunk's TrackFragmentBoxHeader and/or TrackRunBox\", 'size' not found in any of them. \n");
				}

		if(trunInfo->sample_flags_present)
			BAILIFERR( GetFileDataN32( aoe, &trunInfo->sample_flags[i], offset, &offset ) );
		else
		{
			if(trunInfo->first_sample_flags_present && (i == 0))
				trunInfo->sample_flags[0] = trunInfo->first_sample_flags;
				else{
				trunInfo->sample_flags[i] = trafInfo->default_sample_flags;
							if(vg.cmaf && !trafInfo->default_sample_flags_present)
								errprint("CMAF check 'cmf2' violated: Section 7.7.3. \"default_sample_flags, sample_flags and first_sample_flags SHALL be set in the TrackFragmentBoxHeader and/or TrackRunBox to provide sample dependency information within each CMAF chunk and CMAF fragment\", not found in any of them.\n");
					}
				}

		//Use it as a signed int when version is non-zero
		if(trunInfo->sample_composition_time_offsets_present)
		{
			BAILIFERR( GetFileDataN32( aoe, &trunInfo->sample_composition_time_offset[i], offset, &offset ) );
		}
		else
			trunInfo->sample_composition_time_offset[i] = 0;	// Will be checked later; it must be that CTTS is missing ==> composition time == decode times (Section 8.6.1.1.)

		UInt64 compositionTimeInTrackFragment = savedCummulatedSampleDuration + prevTrunCummulatedSampleDuration + (trunInfo->version != 0 ? (Int32)trunInfo->sample_composition_time_offset[i] : (UInt32)trunInfo->sample_composition_time_offset[i]);

		if(compositionTimeInTrackFragment < trafInfo->earliestCompositionTimeInTrackFragment)
			trafInfo->earliestCompositionTimeInTrackFragment = compositionTimeInTrackFragment;

		if((compositionTimeInTrackFragment + currentSampleDecodeDelta) > trafInfo->compositionEndTimeInTrackFragment)
			trafInfo->compositionEndTimeInTrackFragment = compositionTimeInTrackFragment + currentSampleDecodeDelta;

		if(compositionTimeInTrackFragment > trafInfo->latestCompositionTimeInTrackFragment)
			trafInfo->latestCompositionTimeInTrackFragment = compositionTimeInTrackFragment;

	}

	if(vg.cmaf){
			if(trunInfo->data_offset_present != true)
		errprint("CMAF check violated: Section 7.5.17. \"The data-offset-present flag SHALL be set to true\", found %d\n", trunInfo->data_offset_present);
			if(trunInfo->version != 0 && trunInfo->version != 1)
				errprint("CMAF check violated: Section 7.5.17. \"The version field SHALL be set to either '0' or '1'\", found %d\n", trunInfo->version);
	}
	if(vg.hbbtv && trunInfo->version ==0)
			errprint("### HbbTV check violated: Section E.3.1.1. \"The track run box (trun) shall allow negative composition offsets in order to maintain audio visual presentation synchronization\", but unsigned offsets found \n");

	vg.tabcnt++;
	toggleprintsample( 1 );

	sampleprint("trafInfo->default_base_is_moof %d\n",  trafInfo->default_base_is_moof );
	sampleprint("trunInfo->data_offset_present %d\n",  trunInfo->data_offset_present );
	sampleprint("trunInfo->data_offset %08lluX\n",  trunInfo->data_offset );
	sampleprint("moofInfo->offset %lld  %08lluX\n",   moofInfo->offset, moofInfo->offset );
	fprintf(stdout, "moofInfo->offset %lld  %08lluX\n",   moofInfo->offset, moofInfo->offset );

	if (trafInfo->default_base_is_moof && trunInfo->data_offset_present)
	{
		if (!PeekFileData( aoe, TOC,  moofInfo->offset, 32 ))
		{
			fprintf(stdout, "MOOF DATA\n");
			sampleprinthexandasciidata(TOC, 32);
		}
		else
		{
			fprintf(stdout, "FAILED TO READ MOOF\n");
		}
		if (!PeekFileData( aoe, TOC,  moofInfo->offset + trunInfo->data_offset, 32 ))
		{
			fprintf(stdout, "TOC DATA\n");
			sampleprinthexandasciidata(TOC, 32);
		}
		else
		{
			fprintf(stdout, "FAILED TO READ TOC\n");
		}
	}


	for(UInt32 i=0; i<trunInfo->sample_count; i++){
		if(vg.cmaf){
			sampleSizesTotal += trunInfo->sample_size[i];
		}

		sampleprint("<sampleInfo sampleDuration=\"%ld\"", trunInfo->sample_duration[i]);
		sampleprintnotab(" sampleSize=\"%ld\"", trunInfo->sample_size[i]);
		sampleprintnotab(" sampleFlags=\"%ld\"", EndianU32_BtoN(trunInfo->sample_flags[i]));
		sampleprintnotab(" sampleCompositionTimeOffset=\"%ld\"/>\n", trunInfo->sample_composition_time_offset[i]);
	}
	toggleprintsample( 0 );
	--vg.tabcnt;

	trafInfo->processedTrun++;

	atomprint("cummulatedSampleDuration=\"%lld\"\n", trunInfo->cummulatedSampleDuration);
	atomprint("earliestCompositionTime=\"%ld\"\n", trafInfo->earliestCompositionTimeInTrackFragment);
	atomprint("data_offset=\"%ld\"\n", trunInfo->data_offset);
	if(vg.cmaf) {
		atomprint("sampleSizeTotal=\"%ld\"\n", sampleSizesTotal);
	}
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}

//==========================================================================================

OSErr Validate_sbgp_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 flags;
	UInt64 offset;

	TrafInfoRec *trafInfo = (TrafInfoRec *) refcon;

	SbgpInfoRec *sbgpInfo = &trafInfo->sbgpInfo[trafInfo->processedSbgp];

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &sbgpInfo->version, &flags, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &sbgpInfo->grouping_type, offset, &offset ));

	if(sbgpInfo->version == 1)
		BAILIFERR( GetFileDataN32( aoe, &sbgpInfo->grouping_type_parameter, offset, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &sbgpInfo->entry_count, offset, &offset ));

	sbgpInfo->sample_count = (UInt32 *)malloc(sbgpInfo->entry_count*sizeof(UInt32));
	sbgpInfo->group_description_index = (UInt32 *)malloc(sbgpInfo->entry_count*sizeof(UInt32));

	for(UInt32 i = 0 ;  i < sbgpInfo->entry_count ; i++)
	{
		BAILIFERR( GetFileDataN32( aoe, &sbgpInfo->sample_count[i], offset, &offset ));
		BAILIFERR( GetFileDataN32( aoe, &sbgpInfo->group_description_index[i], offset, &offset ));
	}

	trafInfo->processedSbgp++;

	// Print data
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", sbgpInfo->version, flags);
	atomprint("groupingType=\"%ld\"\n", EndianU32_BtoN(sbgpInfo->grouping_type));
	atomprint("groupingTypeParameter=\"%ld\"\n", EndianU32_BtoN(sbgpInfo->grouping_type_parameter));
	atomprint("entryCount=\"%ld\"\n", sbgpInfo->entry_count);
	atomprint(">\n");
	vg.tabcnt++;

	for ( UInt32 i = 0; i < sbgpInfo->entry_count; i++ ) {
	sampleprint("<sampleInfo sampleCount=\"%ld\"", EndianU32_BtoN(sbgpInfo->sample_count[i]));
	sampleprintnotab(" group_description_index=\"%ld\"/>\n", EndianU32_BtoN(sbgpInfo->group_description_index[i]));
	}

	--vg.tabcnt;

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}


//==========================================================================================

OSErr Validate_sgpd_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 flags;
	UInt64 offset;

	TrafInfoRec *trafInfo = (TrafInfoRec *) refcon;

	SgpdInfoRec *sgpdInfo = &trafInfo->sgpdInfo[trafInfo->processedSgpd];

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &sgpdInfo->version, &flags, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &sgpdInfo->grouping_type, offset, &offset ));

	if(sgpdInfo->version == 1)
		BAILIFERR( GetFileDataN32( aoe, &sgpdInfo->default_length, offset, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &sgpdInfo->entry_count, offset, &offset ));

	sgpdInfo->description_length = (UInt32 *)malloc(sgpdInfo->entry_count*sizeof(UInt32));
	sgpdInfo->SampleGroupDescriptionEntry = (UInt32 **)malloc(sgpdInfo->entry_count*sizeof(UInt32 *));

	for(UInt32 i = 0 ;  i < sgpdInfo->entry_count ; i++)
	{
		if(sgpdInfo->version == 1)
		{
			if(sgpdInfo->default_length == 0)
				BAILIFERR( GetFileDataN32( aoe, &sgpdInfo->description_length[i], offset, &offset ));

			sgpdInfo->SampleGroupDescriptionEntry[i] = (UInt32 *)malloc((sgpdInfo->default_length == 0 ? sgpdInfo->description_length[i] : sgpdInfo->default_length)*sizeof(UInt32));
		}

		BAILIFERR(GetFileData(aoe,sgpdInfo->SampleGroupDescriptionEntry[i],offset,sgpdInfo->description_length[i],&offset));
	}

	trafInfo->processedSgpd++;

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", sgpdInfo->version, flags);
	atomprint("groupingType=\"%ld\"\n", EndianU32_BtoN(sgpdInfo->grouping_type));
	atomprint("entryCount=\"%ld\"\n", sgpdInfo->entry_count);
	atomprint(">\n");
	vg.tabcnt++;

	for(UInt32 i=0; i<sgpdInfo->entry_count; i++){
	sampleprint("<sgpdEntry descriptionLength=\"%ld\"", EndianU32_BtoN(sgpdInfo->description_length[i]));
	sampleprintnotab(" sampleGroupDescriptionEntry=\"%ld\"/>\n", EndianU32_BtoN(sgpdInfo->SampleGroupDescriptionEntry[i]));
	}

	--vg.tabcnt;

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}

//==========================================================================================

OSErr Validate_subs_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	UInt32 entry_count;
	UInt32 i, j;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &entry_count, offset, &offset ));

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("entryCount=\"%ld\"\n", entry_count);
	for(i = 0; i < entry_count; i++){
		UInt32 sample_delta;
		UInt16 subsample_count;
		BAILIFERR( GetFileDataN32( aoe, &sample_delta, offset, &offset ));
		BAILIFERR( GetFileDataN16( aoe, &subsample_count, offset, &offset ));

		atomprint("sample_delta_n=\"%d%ld\"\n", i, sample_delta);
		atomprint("subsample_count_n=\"%d%ld\"\n", i, subsample_count);

		bool eac3 = ((strstr(vg.codecs, "ac-3")) || (strstr(vg.codecs, "ec-3")));
		if (eac3 && (sample_delta != 1536))
		{
			errprint("ETSI TS 102 366 v1.4.1 Annex F Line 12806 : sample delta should be 1536 not %d", sample_delta);
		}

		for(j=0; j < subsample_count; j++){
			if(version == 1){
				UInt32 subsample_size;
				BAILIFERR( GetFileDataN32( aoe, &subsample_size, offset, &offset ));
			}
			else if(version == 0){
				UInt16 subsample_size;
				BAILIFERR( GetFileDataN16( aoe, &subsample_size, offset, &offset ));
			}

			UInt16 subsample_priority_and_discardable;
			UInt32 codec_specific_parameters;
			BAILIFERR( GetFileDataN16( aoe, &subsample_priority_and_discardable, offset, &offset ));
			BAILIFERR( GetFileDataN32( aoe, &codec_specific_parameters, offset, &offset ));
		}
	}

	//CMAF check
	if(vg.cmaf && entry_count != 1){
		errprint("CMAF check violated: Section 7.5.20. \"The field entry_count in 'subs' box SHALL equal 1.\", instead found %d", entry_count);
	}

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}

//==========================================================================================

OSErr Validate_emsg_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	char *scheme_id_uri = nil;
	char *value = nil;
	UInt32  timescale;
	UInt32  presentation_time_delta;
	UInt32  event_duration;
	UInt32  id;
	UInt8  *message_data;
	//TrafInfoRec *trafInfo = (TrafInfoRec *)refcon;
	
	UInt64 emsg_offset = aoe->offset;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	if(version != 0)
		errprint("version = 0 for emsg box according to ISO/IEC 23009-1:2013(E), 5.10.3.3.3\n");

	if(flags != 0)
		errprint("flags = 0 for emsg box according to ISO/IEC 23009-1:2013(E), 5.10.3.3.3\n");

	BAILIFERR( GetFileCString( aoe, &scheme_id_uri, offset, aoe->maxOffset - offset, &offset ) );

	BAILIFERR( GetFileCString( aoe, &value, offset, aoe->maxOffset - offset, &offset ) );

	// Get data
	BAILIFERR( GetFileDataN32( aoe, &timescale, offset, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &presentation_time_delta, offset, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &event_duration, offset, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &id, offset, &offset ) );

	message_data = new UInt8[(size_t)(aoe->maxOffset - offset)];
	BAILIFERR( GetFileData( aoe,message_data, offset, aoe->maxOffset - offset , &offset ) );

	 atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	 atomprint("offset=\"%ld\"\n", emsg_offset);
	 atomprint("timeScale=\"%ld\"\n", timescale);
	 atomprint("presentationTimeDelta=\"%ld\"\n", presentation_time_delta);
	 atomprint("eventDuration=\"%ld\"\n", event_duration);
	 atomprint("id=\"%ld\"\n", id);
	 atomprint(">\n");

	 if(vg.cmaf){
		 if(timescale != vg.mediaHeaderTimescale)
			 errprint("CMAF check violated: Section 7.4.5. \"The DASHEventMessageBox in a CMAF Track SHALL contain its timescale field value equal to the timescale in the MediaHeaderBox of CMAF Track that contains it. \", found timescale as %ld instead of %ld \n",timescale, vg.mediaHeaderTimescale);
	}
	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;

}

//==========================================================================================


OSErr Validate_tfdt_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags, temp;
	UInt64 offset;
	TrafInfoRec *trafInfo = (TrafInfoRec *)refcon;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	if(version == 1)
		BAILIFERR( GetFileDataN64( aoe, &trafInfo->baseMediaDecodeTime, offset, &offset ) );
	else
	{
		BAILIFERR( GetFileDataN32( aoe, &temp, offset, &offset ) );
		trafInfo->baseMediaDecodeTime = temp;
	}

	trafInfo->tfdtFound = true;

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("baseMediaDecodeTime=\"%lld\"\n", (trafInfo->baseMediaDecodeTime));//EndianU64_BtoN
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}

OSErr Validate_pssh_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	char* pssh_contents = nullptr;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	// Get data
	UInt8	SystemID[16];
	BAILIFERR( GetFileData( aoe,SystemID, offset, 16 , &offset ) );

	UInt32 DataSize;
	BAILIFERR( GetFileDataN32( aoe, &DataSize, offset, &offset ) );

	UInt8 *Data;

	Data = (UInt8 *)malloc(DataSize*sizeof(UInt8));
	BAILIFERR( GetFileData( aoe,Data, offset, DataSize , &offset ) );


	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	//Adjust SystemID before printing, ascii to integer.
	char print_SysID[50], SysID_char[20];
	print_SysID[0]={'\0'};
	SysID_char[0]={'\0'};
	for(int z=0;z<16;z++)
	{
		sprintf(SysID_char,"%d",SystemID[z]);
		strcat(print_SysID, SysID_char);
	}

	atomprint("systemID=\"%s\"\n", print_SysID);
	atomprint("dataSize=\"%ld\"\n", EndianU32_BtoN(DataSize));
	atomprint(">\n");
	//Compare pssh box contents with the cenc:pssh element of MPD
	pssh_contents = new char[DataSize*2];
	if(vg.pssh_count > 0)
	{
	  sprintf(pssh_contents, "%lu %lu %s %lu %s",version, flags, SystemID, DataSize, Data);

	  //Get pssh mentioned in MPD from a saved file
	  char *pssh_file_contents;
	  long pssh_file_size;

	  for(int i=0; i<vg.pssh_count; i++)
	  {
	char pssh_file_name[300];
	strcpy(pssh_file_name,vg.psshfile[i]);

	if( strlen( pssh_file_name ) > 0){
	FILE *pssh_file = fopen(vg.psshfile[i], "rb");

	  fseek(pssh_file, 0, SEEK_END);
	  pssh_file_size = ftell(pssh_file);
	  rewind(pssh_file);
	  pssh_file_contents = (char *)malloc(pssh_file_size * (sizeof(char)));
	  fread(pssh_file_contents, sizeof(char), pssh_file_size, pssh_file);

	  fclose(pssh_file);

	  int bufferlen = 128;
	  char encodedoutput[] = "";

	  if(strcmp(encodedoutput, pssh_file_contents)!=0)
	  {
		if(i<(vg.pssh_count-1))
		  continue;
		else
		  errprint("pssh box including header is not equivalent to a cenc:pssh element of MPD");
	  }
	   }

	  }
	}
	delete pssh_contents;
	free(Data);

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;


}


OSErr Validate_sidx_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	int i;

	UInt32 version;
	UInt32 flags, temp;
	UInt64 offset;

	MovieInfoRec *mir = (MovieInfoRec *)refcon;

	SidxInfoRec *sidxInfo = &mir->sidxInfo[mir->processedSdixs];

	sidxInfo->offset = aoe->offset;
	sidxInfo->size = aoe->size;

	/*for the index range, verify that
  sidxInfo->offset > starting of index range &&
  sidxInfo->offset + sidxInfo->size - 1 < ending of index range */

	int offs = (int)sidxInfo->offset;	   //convert to int value and store it in a variable
	int siz = (int)sidxInfo->size;

	if (vg.isoondemand) //only check for ondemand profile
	{
		if (vg.lowerindexRange!=-1 && vg.higherindexRange!=-1)
		{
		  if (offs < vg.lowerindexRange || (offs + siz - 1 ) > vg.higherindexRange)
			//fprintf(stdout,"%d  %d\n",vg.lowerindexRange,vg.higherindexRange);
			errprint("sidx offset %d is less than starting of @indexRange %d, OR sum of sidx offset %d and sidx size %d minus 1 is greater than ending of @indexRange %d\n",offs,vg.lowerindexRange,offs,siz,vg.higherindexRange);

		}else
		{   //indexRange missing, check if it's a IOP test vector without @RepresentationIndex
			if (vg.dash264base)
			  errprint("DASH-IF IOP 4.2 check violated - Section 3.2.1. \" For on-demand profiles the Indexed Media Segment as defined in ISO/IEC 23009-1, clause 6.3.4.4 shall be used. In this case the @indexRange attribute shall be present.\"; however, sidx present without @indexRange.\n");
		}
	}

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );

	BAILIFERR( GetFileDataN32( aoe, &sidxInfo->reference_ID, offset, &offset ) );

	TrackInfoRec *tir;

	tir = check_track(sidxInfo->reference_ID);

	if(tir == 0){
		atomprint(">\n");
		return badAtomErr;
	}


	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint("referenceID=\"%ld\"\n", sidxInfo->reference_ID);


	BAILIFERR( GetFileDataN32( aoe, &sidxInfo->timescale, offset, &offset ) );
	atomprint("timeScale=\"%ld\"\n", sidxInfo->timescale);


	if(tir->mediaTimeScale != sidxInfo->timescale)
		warnprint("Warning: sidx timescale %d != track timescale %d for track ID %d, Section 8.16.3.3 of ISO/IEC 14496-12 4th edition: it is recommended that this match the timescale of the reference stream or track\n",sidxInfo->timescale,tir->mediaTimeScale,sidxInfo->reference_ID);

	// Get data
	if(version == 0)
	{
		BAILIFERR( GetFileDataN32( aoe, &temp, offset, &offset ) );
		sidxInfo->earliest_presentation_time = temp;

		BAILIFERR( GetFileDataN32( aoe, &temp, offset, &offset ) );
		sidxInfo->first_offset = temp;
	}

	else
	{
		BAILIFERR( GetFileDataN64( aoe, &sidxInfo->earliest_presentation_time, offset, &offset ) );
		BAILIFERR( GetFileDataN64( aoe, &sidxInfo->first_offset, offset, &offset ) );
	}

	atomprint("earliestPresentationTime=\"%lld\"\n",sidxInfo->earliest_presentation_time); //int64todstr(EndianU64_BtoN(sidxInfo->earliest_presentation_time)));
	atomprint("firstOffset=\"%lld\"\n", (EndianU64_BtoN(sidxInfo->first_offset)));

	BAILIFERR( GetFileDataN32( aoe, &temp, offset, &offset ) );
	sidxInfo->reference_count = (UInt16)(temp & 0xFFFF);

	atomprint("referenceCount=\"%ld\"\n", sidxInfo->reference_count);

	sidxInfo->references = (Reference *)malloc(((UInt32)sidxInfo->reference_count)*sizeof(Reference));

	sidxInfo->cumulatedDuration = 0;

	for(i=0; i < sidxInfo->reference_count; i++)
	{
		BAILIFERR( GetFileDataN32( aoe, &temp, offset, &offset ) );
		sidxInfo->references[i].reference_type = (UInt8)(temp >> 31);

		atomprint("reference_type_%d=\"%d\"\n", i+1, sidxInfo->references[i].reference_type);
		if(sidxInfo->references[i].reference_type == 0)
			tir->numLeafs++;

		sidxInfo->references[i].referenced_size = temp & 0x7FFFFFFF;

		BAILIFERR( GetFileDataN32( aoe, &sidxInfo->references[i].subsegment_duration, offset, &offset ) );

		sidxInfo->cumulatedDuration+=((long double)sidxInfo->references[i].subsegment_duration/(long double)sidxInfo->timescale);

		BAILIFERR( GetFileDataN32( aoe, &temp, offset, &offset ) );
		sidxInfo->references[i].starts_with_SAP = (UInt8)(temp >> 31);
		sidxInfo->references[i].SAP_type = (UInt8)((temp & 0x70000000) >> 28);
		sidxInfo->references[i].SAP_delta_time = temp & 0xFFFFFFF;

	}

	mir->processedSdixs++;

	atomprint("cumulatedDuration=\"%Lf\"\n", sidxInfo->cumulatedDuration);
	if(vg.dashifll) { atomprint(">\n"); }
	vg.tabcnt++;
	for ( i = 0; i < sidxInfo->reference_count; i++ ) {
		if(vg.dashifll) {
			atomprint("<subsegment subsegment_duration=\"%ld\"", sidxInfo->references[i].subsegment_duration);
			atomprintnotab(" starts_with_SAP=\"%d\"", sidxInfo->references[i].starts_with_SAP);
			atomprintnotab(" SAP_type=\"%d\"", sidxInfo->references[i].SAP_type);
			atomprintnotab(" SAP_delta_time=\"%ld\" />\n", sidxInfo->references[i].SAP_delta_time);
		}
		else {
			sampleprint("<subsegment subsegment_duration=\"%ld\"", sidxInfo->references[i].subsegment_duration);
			sampleprintnotab(" starts_with_SAP=\"%d\"", sidxInfo->references[i].starts_with_SAP);
			sampleprintnotab(" SAP_type=\"%d\"", sidxInfo->references[i].SAP_type);
			sampleprintnotab(" SAP_delta_time=\"%ld\" />\n", sidxInfo->references[i].SAP_delta_time);
		}
	}
	--vg.tabcnt;

	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
		atomprint(">\n");
	return err;


}


//==========================================================================================

typedef struct SoundSampleDescriptionInfo {
	SInt16		version;					/* which version is this data */
	SInt16		revisionLevel;			  /* what version of that codec did this */
	UInt32		vendor;					 /* whose  codec compressed this data */
	SInt16		numChannels;				/* number of channels of sound */
	SInt16		sampleSize;				 /* number of bits per sample */
	SInt16		compressionID;			  /* unused. set to zero. */
	SInt16		packetSize;				 /* unused. set to zero. */
	UInt32		sampleRate;					/*��� UnsignedFixed ���*/ /* sample rate sound is captured at */
} SoundSampleDescriptionInfo;

OSErr Validate_soun_SD_Entry( atomOffsetEntry *aoe, void *refcon )
{
	TrackInfoRec *tir = (TrackInfoRec *)refcon;
	OSErr err = noErr;
	Boolean fileTypeKnown = false;
	UInt64 offset;
	SampleDescriptionHead sdh;
	SoundSampleDescriptionInfo ssdi;
	UInt16 sampleratelo, sampleratehi;
	bool eac3;

	offset = aoe->offset;

	// Get data
	BAILIFERR( GetFileData( aoe, &sdh, offset, sizeof(sdh), &offset ) );
	EndianSampleDescriptionHead_BtoN( &sdh );
	BAILIFERR( GetFileData( aoe, &ssdi, offset, sizeof(ssdi), &offset ) );
	ssdi.version = EndianS16_BtoN(ssdi.version);
	ssdi.revisionLevel = EndianS16_BtoN(ssdi.revisionLevel);
	ssdi.vendor = EndianU32_BtoN(ssdi.vendor);
	ssdi.numChannels = EndianS16_BtoN(ssdi.numChannels);
	ssdi.sampleSize = EndianS16_BtoN(ssdi.sampleSize);
	ssdi.compressionID = EndianS16_BtoN(ssdi.compressionID);
	ssdi.packetSize = EndianS16_BtoN(ssdi.packetSize);
	ssdi.sampleRate = EndianU32_BtoN(ssdi.sampleRate);

	// Print atom contents non-required fields
	atomprint("sdType=\"%s\"\n", ostypetostr(sdh.sdType));
	atomprint("dataRefIndex=\"%ld\"\n", sdh.dataRefIndex);
	atomprint("channelCount=\"%d\"\n", ssdi.numChannels);
	atomprint("sampleSize=\"%d\"\n", ssdi.sampleSize);
	atomprint("sampleRate=\"%s\"\n", fixedU32str(ssdi.sampleRate));

	if ((strstr(vg.codecs, "ac-4")) && (ssdi.sampleSize != 16))
	{
		errprint("ETSI TS 103 190-2 v1.2.1 Annex E Line  00013976: sampleSize shall be set to 16 but is %d\n", ssdi.sampleSize );
	}

	sampleratelo = (ssdi.sampleRate) & 0xFFFF;
	sampleratehi = (ssdi.sampleRate >> 16) & 0xFFFF;

	 eac3 = ((strstr(vg.codecs, "ac-3")) || (strstr(vg.codecs, "ec-3")));
	if (eac3 && (sampleratehi != tir->mediaTimeScale))
	{
		errprint("ETSI_TS_102_366_V1.4.1 Annex F Line  12729: Track timescale %d not equal to the (integer part of) the Sample entry sample rate %d.%d\n",
				tir->mediaTimeScale, sampleratehi, sampleratelo);
	}
	atomprint(">\n"); //vg.tabcnt++;

	// Check required field values
	FieldMustBeOneOf11( sdh.sdType, OSType, "SampleDescription sdType must be 'mp4a' or 'enca' or 'ac-4' or 'mha1' or 'mha2' or 'ac-3' or 'ec-3' or 'dtsc' or 'dtsh', 'dtse', 'dtsl' ", ( 'mp4a', 'enca','ac-4', 'mha1','mha2','ac-3','ec-3','dtsc','dtsh','dtse','dtsl' ) );

	if( (sdh.sdType != 'mp4a') && (sdh.sdType != 'enca') && (sdh.sdType != 'ac-4') && (sdh.sdType != 'mha1') && (sdh.sdType != 'mha2') && (sdh.sdType != 'ac-3') && (sdh.sdType != 'ec-3') && (sdh.sdType != 'dtsc') && (sdh.sdType != 'dtsh') && (sdh.sdType != 'dtse') && (sdh.sdType != 'dtsl') && !fileTypeKnown ){
			warnprint("WARNING: Don't know about this sound descriptor type \"%s\"\n",
				ostypetostr(sdh.sdType));
			// goto bail;
	}

	// Explicit check that EAC-3 streams do not contain mp4a sample type
	if( (sdh.sdType == 'mp4a') && (!strcmp(vg.codecs, "ec-3")) )
	{
		errprint("<<- ETSI_TS_102_366_V1.4 F.1 [12733] ->> Violated\n" );
	}

	// Explicit check that AC-4 should contain ac-4 box
	// codec string may contain additional characters like version, so search for substring 'ac-4'
	if( (strstr(vg.codecs, "ac-4")) && (sdh.sdType != 'ac-4') )
	{
		errprint("<<- ETSI_TS_102_366_V1.4 E.4.1 [13862] ->> Violated\n" );
	}

	FieldMustBe( sdh.resvd1, 0, "SampleDescription resvd1 must be %d not 0x%lx" );
	FieldMustBe( sdh.resvdA, 0, "SampleDescription resvd1 must be %d not 0x%lx" );
	FieldMustBe( ssdi.version, 0, "SoundDescription version must be %d not %d" );
	FieldMustBe( ssdi.revisionLevel, 0, "SoundDescription revisionLevel must be %d not %d" );
	FieldMustBe( ssdi.vendor, 0, "SoundDescription vendor must be %d not 0x%lx" );
	FieldCheck( ssdi.numChannels > 0, "SoundDescription numChannels must be superior to 0" );
	FieldMustBe( ssdi.sampleSize, 16, "SoundDescription sampleSize must be %d not %d" );
	FieldMustBe( ssdi.compressionID, 0, "SoundDescription compressionID must be %d not %d" );
	FieldMustBe( ssdi.packetSize, 0, "SoundDescription packetSize must be %d not %d" );
	// sample rate must be time-scale of track << 16 for mp4


	FieldMustBe( ssdi.sampleRate & 0x0000ffff, 0, "SoundDescription sampleRate's low long must be %d not 0x%lx" );

	// Now we have the Sample Extensions

	{
		UInt64 minOffset;
		UInt64 maxOffset;
		atomOffsetEntry *entry;
		long cnt;
		atomOffsetEntry *list;
		int i;
		int sinfFound=0;

		minOffset = offset;
		maxOffset = aoe->offset + aoe->size;

		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

		if ((cnt != 1) && (sdh.sdType == 'mp4v')) {
			errprint( "MPEG-4 only allows 1 sample description extension\n" );
			err = badAtomErr;
		}

		for (i = 0; i < cnt; i++) {
			entry = &list[i];

			if (entry->type == 'esds') {
				BAILIFERR( Validate_ESDAtom( entry, refcon, Validate_soun_ES_Bitstream, (char *)"soun_ES" ) );
			}

			else if ( entry->type == 'sinf' )
			{
				// Process 'sinf' atoms
								sinfFound=1;
				atomprint("<sinf"); vg.tabcnt++;
				BAILIFERR( Validate_sinf_Atom( entry, refcon, kTypeAtomFlagMustHaveOne ) );
				--vg.tabcnt; atomprint("</sinf>\n");
			}
			else if (entry->type == 'mhaC' ){
					BAILIFERR( Validate_mhaC_Atom( entry, refcon));
			}
			else if (entry->type == 'dac3' ){
					BAILIFERR( Validate_dac3_Atom( entry, refcon));
			}
			else if (entry->type == 'dec3' ){
					BAILIFERR( Validate_dec3_Atom( entry, refcon));
			}
			else if (entry->type == 'dac4' ){
					BAILIFERR( Validate_dac4_Atom( entry, refcon));
			}
			else if (entry->type == 'lac4' ){
					BAILIFERR( Validate_lac4_Atom( entry, refcon));
			}
			else {
				warnprint("Warning: In %s - unknown atom found \"%s\": audio sample descriptions would not normally contain this\n",vg.curatompath, ostypetostr(entry->type));
			}

			//
			//Explicit check for ec-3
			if(!strcmp(vg.codecs, "ec-3") && (entry->type != 'dec3'))
				errprint("sample type not 'dec3' as expected for ec-3\n" );

			//Explicit check for ec-3 with mp4a
			if(!strcmp(vg.codecs, "mp4a") && (entry->type == 'dec3'))
				errprint("sample type 'dec3' not allowed for m4a\n" );

			//Explicit check for ac-3 with mp4a
			if(!strcmp(vg.codecs, "mp4a") && (entry->type == 'dac3'))
				errprint("sample type 'dac3' not allowed for m4a\n" );

			//Explicit check for ec-3 having dec3 box
			if(!strcmp(vg.codecs, "ec-3") && (entry->type != 'dec3'))
				errprint("sample type not 'dec3' as it should be for codec 'ec-3'\n" );

			//Explicit check for ac-3 having dac3 box
			if(!strcmp(vg.codecs, "ac-3") && (entry->type != 'dac3'))
				errprint("sample type not 'dac3' as it should be for codec 'ac-3'\n" );

			//Explicit check for ac-4 having dac4 box
			if(!strcmp(vg.codecs, "ac-4") && (entry->type != 'dac4'))
				errprint("sample type not 'dac4' as it should be for codec 'ac-4'\n" );
		}

		if(vg.cmaf && ((sdh.sdType == 'drmi' ) || (( (sdh.sdType & 0xFFFFFF00) | ' ') == 'enc ' )) && sinfFound!=1)
					errprint("CMAF check violated: Section 7.5.10. \"Sample Entries for encrypted tracks SHALL encapsulate the existing sample entry with a Protection Scheme Information Box ('sinf')\", but 'sinf' not found. \n");
		if(vg.cmaf && vg.dash264enc && sinfFound!=1)
					errprint("CMAF check violated: Section 7.5.11. \"An encrypted CMAF Track SHALL include at least one Protection Scheme Information Box ('sinf') \", found %d\n", 0);
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}


//==========================================================================================

OSErr Validate_hint_SD_Entry( atomOffsetEntry *aoe, void *refcon )
{

	OSErr err = noErr;
	UInt64 offset;
	SampleDescriptionHead sdh;

	offset = aoe->offset;

	// Get data
	BAILIFERR( GetFileData( aoe, &sdh, offset, sizeof(sdh), &offset ) );
	EndianSampleDescriptionHead_BtoN( &sdh );
//����how to cope with hint data

	// Print atom contents non-required fields
	atomprint("sdType=\"%s\"\n", ostypetostr(sdh.sdType));
	atomprint("dataRefIndex=\"%ld\"\n", sdh.dataRefIndex);
	atomprint(">\n"); //vg.tabcnt++;

	if(vg.cmaf && ((sdh.sdType == 'drmi' ) || (( (sdh.sdType & 0xFFFFFF00) | ' ') == 'enc ' )) && aoe->type != 'sinf'){
		char entry_type_name[5] = {};
		entry_type_name[0] = (aoe->type >> 24) & 0xff;
		entry_type_name[1] = (aoe->type >> 16) & 0xff;
		entry_type_name[2] = (aoe->type >>  8) & 0xff;
		entry_type_name[3] = (aoe->type >>  0) & 0xff;
		errprint("CMAF check violated: Section 7.5.10. \"Sample Entries for encrypted tracks SHALL encapsulate the existing sample entry with a Protection Scheme Information Box ('sinf')\", found %s\n", entry_type_name);
	}
	// Check required field values
	FieldMustBe( sdh.resvd1, 0, "SampleDescription resvd1 must be %d not 0x%lx" );
	FieldMustBe( sdh.resvdA, 0, "SampleDescription resvd1 must be %d not 0x%lx" );

//��� hint data

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

OSErr Validate_subt_SD_Entry( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt64 offset;
	SampleDescriptionHead sdh;

	offset = aoe->offset;

	// Get data
	BAILIFERR( GetFileData( aoe, &sdh, offset, sizeof(sdh), &offset ) );
	EndianSampleDescriptionHead_BtoN( &sdh );

	atomprint("sdType=\"%s\"\n", ostypetostr(sdh.sdType));
	atomprint("dataRefIndex=\"%ld\"\n", sdh.dataRefIndex);
	atomprint(">\n"); //vg.tabcnt++;

	{
		UInt64 minOffset, maxOffset;
		atomOffsetEntry *entry;
		atomOffsetEntry *list;
		long cnt;
		int i;

		minOffset = offset;
		maxOffset = aoe->offset + aoe->size;

		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

		for(i=0; i<cnt; i++)
		{
			entry = &list[i];

			if(sdh.sdType == 'stpp')
			{
				BAILIFERR( Validate_stpp_Atom( entry, refcon, (char *)"stpp" ) );
			}
		}
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;

}

//==========================================================================================

//==========================================================================================

OSErr Validate_text_SD_Entry( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt64 offset;
	SampleDescriptionHead sdh;
	
	offset = aoe->offset;
	
	// Get data 
	BAILIFERR( GetFileData( aoe, &sdh, offset, sizeof(sdh), &offset ) );
	EndianSampleDescriptionHead_BtoN( &sdh );
	
	atomprint("sdType=\"%s\"\n", ostypetostr(sdh.sdType));
	atomprint("dataRefIndex=\"%ld\"\n", sdh.dataRefIndex);
	atomprint(">\n"); //vg.tabcnt++; 
	
	{
		UInt64 minOffset, maxOffset;
		atomOffsetEntry *entry;
		atomOffsetEntry *list;
		long cnt;
		int i;
		
		minOffset = offset;
		maxOffset = aoe->offset + aoe->size;
		
		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
		
		for(i=0; i<cnt; i++)
		{
			entry = &list[i];
			
			if(sdh.sdType == 'wvtt')
			{
				BAILIFERR( Validate_wvtt_Atom( entry, refcon, (char *)"wvtt" ) );
			}
		}
	}
	
	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
	
}

//==========================================================================================

OSErr Validate_mp4_SD_Entry( atomOffsetEntry *aoe, void *refcon, ValidateBitstreamProcPtr validateBitstreamProc, char *esname )
{
	OSErr err = noErr;
	UInt64 offset;
	SampleDescriptionHead sdh;

	offset = aoe->offset;

	// Get data
	BAILIFERR( GetFileData( aoe, &sdh, offset, sizeof(sdh), &offset ) );
	EndianSampleDescriptionHead_BtoN( &sdh );
//����how to cope with hint data

	// Print atom contents non-required fields
	atomprint("sdType=\"%s\"\n", ostypetostr(sdh.sdType));
	atomprint("dataRefIndex=\"%ld\"\n", sdh.dataRefIndex);
	atomprint(">\n"); //vg.tabcnt++;



	FieldMustBe( sdh.sdType, 'mp4s', "SampleDescription sdType must be 'mp4s'" );
	FieldMustBe( sdh.resvd1, 0, "SampleDescription resvd1 must be %d not 0x%lx" );
	FieldMustBe( sdh.resvdA, 0, "SampleDescription resvd1 must be %d not 0x%lx" );

	// Now we have the Sample Extensions
	{
		UInt64 minOffset, maxOffset;
		atomOffsetEntry *entry;
		long cnt;
		atomOffsetEntry *list;
		int i;

		minOffset = offset;
		maxOffset = aoe->offset + aoe->size;

		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

		if (cnt != 1) {

				errprint( "MPEG-4 only allows 1 ESD\n" );
			err = badAtomErr;
		}

		for (i = 0; i < cnt; i++) {
			entry = &list[i];

			if(vg.cmaf && ((sdh.sdType == 'drmi' ) || (( (sdh.sdType & 0xFFFFFF00) | ' ') == 'enc ' )) && entry->type != 'sinf'){
				char entry_type_name[5] = {};
				entry_type_name[0] = (entry->type >> 24) & 0xff;
				entry_type_name[1] = (entry->type >> 16) & 0xff;
				entry_type_name[2] = (entry->type >>  8) & 0xff;
				entry_type_name[3] = (entry->type >>  0) & 0xff;
				errprint("CMAF check violated: Section 7.5.10. \"Sample Entries for encrypted tracks SHALL encapsulate the existing sample entry with a Protection Scheme Information Box ('sinf')\", found %s\n", entry_type_name);
			}

			BAILIFERR( Validate_ESDAtom( entry, refcon, validateBitstreamProc, esname) );

		}
	}


	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

//==========================================================================================

typedef struct MHADecoderConfigurationRecord {
	  UInt8	configurationVersion;
	  UInt8	mpegh3daProfileLevelIndication;
	  UInt8	referenceChannelLayout;
	  UInt16	mpegh3daConfigLength;
	  UInt32	mpegh3daConfig;
 }MHADecoderConfigurationRecord;

OSErr Validate_mhaC_Atom( atomOffsetEntry *aoe, void *refcon)
{
	OSErr err = noErr;
	UInt64 offset;

	offset = aoe->offset + aoe->atomStartSize;
	MHADecoderConfigurationRecord mhaDecoderConfigurationRecord;
		//errprint( "offset= %d\n",offset );

	atomprint("<mhaC\n");
	vg.tabcnt++;

	BAILIFERR( GetFileData( aoe, &mhaDecoderConfigurationRecord.configurationVersion , offset, sizeof(mhaDecoderConfigurationRecord.configurationVersion), &offset ) );
	BAILIFERR( GetFileData( aoe, &mhaDecoderConfigurationRecord.mpegh3daProfileLevelIndication , offset, sizeof(mhaDecoderConfigurationRecord.mpegh3daProfileLevelIndication), &offset ) );
	BAILIFERR( GetFileData( aoe, &mhaDecoderConfigurationRecord.referenceChannelLayout , offset, sizeof(mhaDecoderConfigurationRecord.referenceChannelLayout) , &offset ) );
	BAILIFERR( GetFileData( aoe, &mhaDecoderConfigurationRecord.mpegh3daConfigLength, offset, sizeof(mhaDecoderConfigurationRecord.mpegh3daConfigLength), &offset ) );
		BAILIFERR( GetFileData( aoe, &mhaDecoderConfigurationRecord.mpegh3daConfig, offset, sizeof(mhaDecoderConfigurationRecord.mpegh3daConfigLength*8), &offset ) );

	atomprint("configurationVersion=\"%d\"\n", mhaDecoderConfigurationRecord.configurationVersion);
	atomprint("mpegh3daProfileLevelIndication=\"%d\"\n", mhaDecoderConfigurationRecord.mpegh3daProfileLevelIndication);
	atomprint("referenceChannelLayout=\"%d\"\n", mhaDecoderConfigurationRecord.referenceChannelLayout);
	atomprint("mpegh3daConfigLength=\"%ld\"\n", EndianU16_BtoN(mhaDecoderConfigurationRecord.mpegh3daConfigLength));
	atomprint("mpegh3daConfig=\"%ld\"\n", EndianU32_BtoN(mhaDecoderConfigurationRecord.mpegh3daConfig));
	atomprint(">\n");

		FieldMustBe( mhaDecoderConfigurationRecord.configurationVersion , 1, "ConfigurationVersion must be %d not %d" );
		if(vg.dash264base){
			if(vg.audioChValue != mhaDecoderConfigurationRecord.referenceChannelLayout){
				errprint( "DASH-IF IOP 4.2 check violated - Section 9.2.5.2. \" The referenceChannelLayout field shall be equivalent to what is signalled in ChannelConfiguration\", referenceChannelLayout is not matching with out of box AudioChannelConfiguration value\n" );
			}
			if(mhaDecoderConfigurationRecord.mpegh3daProfileLevelIndication != 11
				&& mhaDecoderConfigurationRecord.mpegh3daProfileLevelIndication != 12
				&& mhaDecoderConfigurationRecord.mpegh3daProfileLevelIndication != 13
			)
				errprint( "DASH-IF IOP 4.2 check violated - Section 9.2.5.2. \"The mpegh3daProfileLevelIndication shall be set to 0x0B, 0x0C or 0x0D\", found %d.\n", mhaDecoderConfigurationRecord.mpegh3daProfileLevelIndication);
		}

	--vg.tabcnt;
	atomprint("</mhaC>\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;

}


//==========================================================================================

OSErr Validate_ESDAtom( atomOffsetEntry *aoe, void *refcon, ValidateBitstreamProcPtr validateBitstreamProc, char *esname )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	Ptr esDataP = nil;
	unsigned long esSize;
	BitBuffer bb;

	atomprint("<ESD"); vg.tabcnt++;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );


		FieldMustBe( flags, 0, "'ESDAtom' flags must be %d not 0x%lx" );
		FieldMustBe( version, 0, "ESDAtom version must be %d not 0x%2x" );

	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
	atomprint(">\n");

	// Get the ObjectDescriptor
	//atomprint("<%s>", esname); vg.tabcnt++;
	BAILIFERR( GetFileBitStreamDataToEndOfAtom( aoe, &esDataP, &esSize, offset, &offset ) );

	BitBuffer_Init(&bb, (UInt8 *)esDataP, esSize);

	BAILIFERR( CallValidateBitstreamProc( validateBitstreamProc, &bb, refcon ) );

	if (NumBytesLeft(&bb) > 1) {
		err = tooMuchDataErr;
	}

	//--vg.tabcnt; atomprint("</%s>\n", esname);

	// All done
	aoe->aoeflags |= kAtomValidated;


bail:
		--vg.tabcnt; atomprint("</ESD>\n");
	if (esDataP)
		free(esDataP);

	return err;
}



OSErr Validate_uuid_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	UInt32 residual;
	//char	tempStr[100];

	// atomprint("<uuid "); vg.tabcnt++;
	residual = (UInt32)(aoe->size - sizeof( AtomSizeType ) - sizeof( uuidType ));

	atomprintnotab("\tuuid=\"%02x%02x%02x%02x-%02x%02x-%02x%02x-%02x%02x-%02x%02x%02x%02x%02x%02x\" more_data_length=\"%d\" %s\n",
		aoe->uuid[0],  aoe->uuid[1],  aoe->uuid[2],  aoe->uuid[3],
		aoe->uuid[4],  aoe->uuid[5],  aoe->uuid[6],  aoe->uuid[7],
		aoe->uuid[8],  aoe->uuid[9],  aoe->uuid[10], aoe->uuid[11],
		aoe->uuid[12], aoe->uuid[13], aoe->uuid[14], aoe->uuid[15],
		residual, ">" ); // (residual > 0 ? ">" : "/>")

	offset = aoe->offset + 8 + 16;

	vg.tabcnt++;
	vg.printsample = true;

	while (residual>0) {
		UInt32 to_read;
		char buff[16];

		to_read = (residual > 16 ? 16 : residual);
		BAILIFERR( GetFileData( aoe, &(buff[0]), offset, to_read, &offset ) );
		sampleprinthexandasciidata( &(buff[0]), to_read );
		residual -= to_read;
	}
	--vg.tabcnt; vg.printsample = false;
	residual = (UInt32)(aoe->size - sizeof( AtomSizeType ) - sizeof( uuidType ));
	// if (residual > 0) atomprint("</uuid>\n");  --vg.tabcnt;

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_colr_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	ColrInfo colrHeader;
	static char* primaries[] = {
		(char *)"Reserved", (char *)"BT.709", (char *)"Unspecified", (char *)"Reserved",(char *)"BT.470-2 System M",
		(char *)"EBU Tech. 3213 (was BT.470-2 System B,G)", (char *)"SMPTE 170M", (char *)"SMPTE 240M", (char *)"Linear/Film",
		(char *)"Log 100:1", (char *)"Log 316.22777:1"};
	static char* matrices[] = {
		(char *)"Reserved", (char *)"BT.709", (char *)"Unspecified", (char *)"Reserved",(char *)"FCC",
		(char *)"BT.470-2 System B,G", (char *)"ITU-R BT.601-4 (SMPTE 170M)", (char *)"SMPTE 240M" };
	char* prim;
	char* func;
	char* matr;

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &colrHeader.start.atomSize, offset, sizeof( AtomSizeType ), &offset ) );
	colrHeader.start.atomSize = EndianU32_BtoN( colrHeader.start.atomSize );
	colrHeader.start.atomType = EndianU32_BtoN( colrHeader.start.atomType );

	BAILIFERR( GetFileData( aoe, &colrHeader.colrtype, offset, colrHeader.start.atomSize - sizeof( AtomSizeType ), &offset ) );
	colrHeader.colrtype = EndianU32_BtoN( colrHeader.colrtype );
		atomprint("colrtype=\"%s\"\n", ostypetostr(colrHeader.colrtype));

	if ((colrHeader.colrtype == 'nclc') && ( 18 == colrHeader.start.atomSize )) {
		colrHeader.primaries = EndianU16_BtoN( colrHeader.primaries );
		if (colrHeader.primaries < 11) prim = primaries[colrHeader.primaries]; else prim = (char *)"unknown";

		colrHeader.function  = EndianU16_BtoN( colrHeader.function );
		if (colrHeader.function < 11) func = primaries[colrHeader.function]; else func = (char *)"unknown";

		colrHeader.matrix	= EndianU16_BtoN( colrHeader.matrix );
		if (colrHeader.matrix < 8) matr = matrices[colrHeader.matrix]; else matr = (char *)"unknown";
		atomprintnotab("\tavg(Colr/nclc)Primaries=\"%d\" (%s), function=\"%d\" (%s), matrix=\"%d\" (%s)\n",
			colrHeader.primaries, prim,
			colrHeader.function, func,
			colrHeader.matrix, matr);

		atomprint(">\n");
	}
	else if(colrHeader.colrtype == 'nclx'){ //errprint( "colr atom size or type not as expected; size %d, should be %d; or type %s not nclc\n",
		 	//colrHeader.start.atomSize, 18, ostypetostr(colrHeader.colrtype) );
			atomprint(">\n");
		   warnprint("colr atom of type nclx found, the software does not handle colr atoms of this type. \n");
		}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_avcC_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AvcConfigInfo avcHeader;
	void* bsDataP;
	BitBuffer bb;

	atomprint("<%s", esname); vg.tabcnt++;

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &avcHeader.start.atomSize, offset, sizeof( AtomSizeType ), &offset ) );
	avcHeader.start.atomSize = EndianU32_BtoN( avcHeader.start.atomSize );
	avcHeader.start.atomType = EndianU32_BtoN( avcHeader.start.atomType );


	BAILIFNIL( bsDataP = calloc(avcHeader.start.atomSize - 8 + bitParsingSlop, 1), allocFailedErr );
	BAILIFERR( GetFileData( aoe, bsDataP, offset, avcHeader.start.atomSize - 8, &offset ) );
	BitBuffer_Init(&bb, (UInt8 *)bsDataP, avcHeader.start.atomSize - 8);

	BAILIFERR( Validate_AVCConfigRecord( &bb, refcon ) );
	//--vg.tabcnt; atomprint("</%s>\n", esname);


	free( bsDataP );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	--vg.tabcnt; atomprint("</%s>\n", esname);
	return err;
}

OSErr Validate_btrt_Atom( atomOffsetEntry *aoe, void *refcon, char *esName )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AvcBtrtInfo bitrHeader;
	//char	tempStr[100];

	atomprint("<btrt"); vg.tabcnt++;


	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &bitrHeader.start.atomSize, offset, sizeof( AtomSizeType ), &offset ) );
	bitrHeader.start.atomSize = EndianU32_BtoN( bitrHeader.start.atomSize );
	bitrHeader.start.atomType = EndianU32_BtoN( bitrHeader.start.atomType );

	BAILIFERR( GetFileData( aoe, &bitrHeader.buffersizeDB, offset, bitrHeader.start.atomSize - sizeof( AtomSizeType ), &offset ) );
	bitrHeader.buffersizeDB = EndianU32_BtoN( bitrHeader.buffersizeDB );
	bitrHeader.maxBitrate   = EndianU32_BtoN( bitrHeader.maxBitrate );
	bitrHeader.avgBitrate   = EndianU32_BtoN( bitrHeader.avgBitrate );

	atomprintnotab("\tBuffzersizeDB=\"%d\"  maxBitrate=\"%d\" avgBitrate=\"%d\"\n",
		bitrHeader.buffersizeDB, bitrHeader.maxBitrate, bitrHeader.avgBitrate );

	if( sizeof( AvcBtrtInfo ) != bitrHeader.start.atomSize ){
		err = badAtomSize;
		errprint( "atom size for 'btrt' atom (%d) != sizeof( AvcBtrtInfo )(%d) \n", bitrHeader.start.atomSize, sizeof( AvcBtrtInfo ) );
		goto bail;
	}

	--vg.tabcnt; atomprint("/>\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_m4ds_Atom( atomOffsetEntry *aoe, void *refcon, char *esName )
{
	OSErr err = noErr;
	UInt64 offset;
	Ptr esDataP = nil;
	unsigned long esSize;
	BitBuffer bb;

	atomprint("<m4ds>\n"); vg.tabcnt++;
	offset = aoe->offset + aoe->atomStartSize;

	// Get the Descriptors
	BAILIFERR( GetFileBitStreamDataToEndOfAtom( aoe, &esDataP, &esSize, offset, &offset ) );

	BitBuffer_Init(&bb, (UInt8 *)esDataP, esSize);

	while (NumBytesLeft(&bb) >= 1) {
		BAILIFERR( Validate_Random_Descriptor(  &bb, (char *)"Descriptor" ) );
	}

	// All done
	aoe->aoeflags |= kAtomValidated;
	--vg.tabcnt; atomprint("</m4ds>\n");

bail:
	if (esDataP)
		free(esDataP);

	return err;
}

//==========================================================================================

OSErr Validate_stpp_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
	OSErr err = noErr;
	UInt64 offset;
	char *name_space;
	char *schema_location;
	char *auxiliary_mime_types;
	UInt64 minOffset, maxOffset;
	atomOffsetEntry *entry;
	long cnt;
	atomOffsetEntry *list;
	int i;

	atomprint("<%s", esname); vg.tabcnt++;

	offset = aoe->offset;

	// Get data
	BAILIFERR( GetFileCString( aoe, &name_space, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("namespace=\"%s\"\n", name_space);
	BAILIFERR( GetFileCString( aoe, &schema_location, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("schema_location=\"%s\"\n", schema_location);
	BAILIFERR( GetFileCString( aoe, &auxiliary_mime_types, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("auxiliary_mime_types=\"%s\"\n", auxiliary_mime_types);

	minOffset = offset;
	maxOffset = aoe->offset + aoe->size;
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	for (i = 0; i < cnt; i++) {
			entry = &list[i];

			atomprint("<%s",ostypetostr(entry->type)); vg.tabcnt++;

			switch( entry->type ) {
					case 'mime':
							Validate_mime_Atom( entry, refcon, (char *)"mime" );
							break;

					default:
							break;
			}
			--vg.tabcnt;

	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
		atomprint("/>\n"); vg.tabcnt--;
	return err;
}

OSErr Validate_mime_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
	OSErr err = noErr;
	UInt64 offset;
	char *contenttype;

	atomprint("<%s", esname); vg.tabcnt++;

	offset = aoe->offset;

	// Get data
	BAILIFERR( GetFileCString( aoe, &contenttype, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("content_type=\"%s\"\n", contenttype);

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
		atomprint("/>\n"); vg.tabcnt--;
	return err;
}

//==========================================================================================

OSErr Validate_wvtt_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
	OSErr err = noErr;
	UInt64 offset;
	UInt64 minOffset, maxOffset;
	atomOffsetEntry *entry;
	atomOffsetEntry *list;
	long cnt;
	int i;
	
	atomprint("<%s", esname); vg.tabcnt++;
	
	// Get data
	offset = aoe->offset;
	minOffset = offset;
	maxOffset = aoe->offset + aoe->size;
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	for (i = 0; i < cnt; i++) {
		entry = &list[i];
		
		atomprint("<%s",ostypetostr(entry->type)); vg.tabcnt++;
		switch( entry->type ) {
			case 'vttC':
				Validate_vttC_Atom( entry, refcon, (char *)"vttC" );
				break;
				
			case 'vlab':
				Validate_vlab_Atom( entry, refcon, (char *)"vlab" );
				break;
				
			case 'btrt':
				Validate_btrt_Atom( entry, refcon, (char *)"btrt" );
				break;
				
			default:
				break;
		}
		--vg.tabcnt; 
	}
	
	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	atomprint("/>\n"); vg.tabcnt--;
	return err;
}

OSErr Validate_vttC_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
	OSErr err = noErr;
	UInt64 offset;
	char *config;
	
	atomprint("<%s", esname); vg.tabcnt++;
	
	offset = aoe->offset;
	
	// Get data
	BAILIFERR( GetFileCString( aoe, &config, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("config=\"%s\"\n", config);
	
	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	atomprint("/>\n"); vg.tabcnt--;
	return err;
}

OSErr Validate_vlab_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
	OSErr err = noErr;
	UInt64 offset;
	char *source_label;
	
	atomprint("<%s", esname); vg.tabcnt++;
	
	offset = aoe->offset;
	
	// Get data
	BAILIFERR( GetFileCString( aoe, &source_label, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("source_label=\"%s\"\n", source_label);
	
	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	atomprint("/>\n"); vg.tabcnt--;
	return err;
}

//==========================================================================================

OSErr Validate_cprt_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	Ptr esDataP = nil;
	UInt16 language;
	char *noticeP = nil;
	UInt16 stringSize;
	int textIsUTF16 = false;		// otherwise, UTF-8
	int utf16TextIsLittleEndian = false;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	FieldMustBe( version, 0, "cprt version must be %d not %d" );
	FieldMustBe( flags, 0, "cprt flags must be %d not 0x%lx" );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

	// Get data
	BAILIFERR( GetFileDataN16( aoe, &language, offset, &offset ) );
	FieldMustBe( (language & 0x8000), 0, "cprt language's high bit must be 0" );
	atomprint("language=\"%s\"\n", langtodstr(language));
	if (language==0) warnprint("WARNING: Copyright language code of 0 not strictly legit -- 'und' preferred\n");


#if 1
	// This may be a UTF-16 string so we can't just grab a C string

	stringSize = (UInt16)(aoe->maxOffset - offset);
	noticeP = (char*) calloc(stringSize, 1);

	BAILIFERR( GetFileData( aoe, noticeP, offset, stringSize, &offset ) );

	// check string type
	if (stringSize > 2) {
		UInt16 possibleBOM = EndianU16_BtoN(*(UInt16*)noticeP);

		if (possibleBOM == 0x0feff) {			// big endian
			textIsUTF16 = true;
		}
		else if (possibleBOM == 0x0fffe) {		// little endian
			textIsUTF16 = true;
			utf16TextIsLittleEndian = true;
		}
	}

	// if text is UTF-16, we will generate ASCII text for output
	if (textIsUTF16) {
		char * utf8noticeP = nil;
		char * pASCII = nil;
		UInt16 * pUTF16 = nil;
		int numChars = (stringSize - 2)/2;

		if (numChars == 0) { // no actual text
			errprint("UTF-16 text has BOM but no terminator\n");
		}
		else {
			int ix;

			// �� clf -- The right solution is probably to generate "\uNNNN" for Unicode characters not in the range 0-0x7f. That
			// will require the array be 5 times as large in the worst case.
			utf8noticeP = (char *)calloc(numChars, 1);
			pASCII= utf8noticeP;

			pUTF16 = (UInt16*) (noticeP + 2);

			for (ix=0; ix < numChars-1; ix++, pUTF16++) {
				UInt16 utf16Char = utf16TextIsLittleEndian ? EndianU16_LtoN(*pUTF16) : EndianU16_BtoN(*pUTF16);

				*pASCII	= (utf16Char & 0xff80) ? ((char) '\?') : (char)(utf16Char & 0x7f);

				pASCII++;
			}

			free(noticeP);
			noticeP = utf8noticeP;
		}
	}
#else
	BAILIFERR( GetFileCString( aoe, &noticeP, offset, aoe->maxOffset - offset, &offset ) );
#endif
	atomprint("notice=\"%s\"\n", noticeP);

	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	if (esDataP)
		free(esDataP);

	return err;
}

//==========================================================================================

OSErr Validate_kind_Atom( atomOffsetEntry *aoe, void *refcon )
{
		OSErr err = noErr;
		UInt32 version;
		UInt32 flags;
		UInt64 offset;
		char *schemeURI;	// null terminated C string
		char *value;		// null terminated C string


		// Get version/flags
		BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
		atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

		// Get related attributes
		BAILIFERR( GetFileCString( aoe, &schemeURI, offset, aoe->maxOffset - offset, &offset ) );
		atomprint("schemeURI=\"%s\"\n", schemeURI);
		BAILIFERR( GetFileCString( aoe, &value, offset, aoe->maxOffset - offset, &offset ) );
		atomprint("value=\"%s\"\n", value);

		atomprint(">\n");

		// All done
		aoe->aoeflags |= kAtomValidated;

bail:

		return err;
}

//==========================================================================================

OSErr Validate_loci_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;
	Ptr esDataP = nil;
	UInt16 language;
	char *noticeP = nil;
	UInt16 stringSize;
	SInt32 lngi, lati, alti;
	UInt8 role;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	FieldMustBe( version, 0, "loci version must be %d not %d" );
	FieldMustBe( flags, 0, "loci flags must be %d not 0x%lx" );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

	// Get data
	BAILIFERR( GetFileDataN16( aoe, &language, offset, &offset ) );
	FieldMustBe( (language & 0x8000), 0, "loci language's high bit must be 0" );
	atomprint("language=\"%s\"\n", langtodstr(language));
	if (language==0) warnprint("WARNING: Location language code of 0 not strictly legit -- 'und' preferred\n");

	stringSize = (UInt16)(aoe->maxOffset - offset);
	noticeP = (char*) calloc(stringSize, 1);

	BAILIFERR( GetFileUTFString( aoe, &noticeP, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("Name=\"%s\"\n", noticeP);

	BAILIFERR( GetFileData( aoe, &role,  offset, 1, &offset ) );
	BAILIFERR( GetFileData( aoe, &lngi, offset, 4, &offset ) ); lngi = EndianS32_BtoN(lngi);
	BAILIFERR( GetFileData( aoe, &lati, offset, 4, &offset ) ); lati = EndianS32_BtoN(lati);
	BAILIFERR( GetFileData( aoe, &alti, offset, 4, &offset ) ); alti = EndianS32_BtoN(alti);

	atomprint("role=\"%d\"\n", role );

	atomprint("longitude=\"%d.%d\"\n", lngi >> 16, ((UInt32) lngi) && 0xFFFF );
	atomprint("latitude=\"%d.%d\"\n",  lati >> 16, ((UInt32) lati) && 0xFFFF );
	atomprint("altitude=\"%d.%d\"\n",  alti >> 16, ((UInt32) alti) && 0xFFFF );


	BAILIFERR( GetFileUTFString( aoe, &noticeP, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("Body=\"%s\"\n", noticeP);

	BAILIFERR( GetFileUTFString( aoe, &noticeP, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("Notes=\"%s\"\n", noticeP);

	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	if (esDataP)
		free(esDataP);

	return err;
}

//==========================================================================================

//==========================================================================================

OSErr Validate_frma_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt32 format;
	//char	tempStr[100];

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomSizeType ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );


	BAILIFERR( GetFileData( aoe, &format, offset, sizeof( UInt32 ), &offset ) );
	format = EndianU32_BtoN( format );

	atomprintnotab("\toriginal_format=\"%s\"\n", ostypetostr(format) );
	atomprint(">\n");

	if( ahdr.atomSize != (sizeof(UInt32) + sizeof(AtomSizeType)) ){
		err = badAtomSize;
		errprint( "wrong atom size for 'frma' atom (%d) should be %d \n", ahdr.atomSize, (sizeof(UInt32) + sizeof(AtomSizeType)) );
		goto bail;
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_schm_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt32 scheme, s_version, vers, flags;
	//char	tempStr[100];
	char *locationP = nil;

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomStartRecord ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );

	BAILIFERR( GetFullAtomVersionFlags( aoe, &vers, &flags, &offset));

	BAILIFERR( GetFileData( aoe, &scheme,	offset, sizeof( UInt32 ), &offset ) );
	BAILIFERR( GetFileData( aoe, &s_version, offset, sizeof( UInt32 ), &offset ) );
	scheme	= EndianU32_BtoN( scheme );
	s_version = EndianU32_BtoN( s_version );

	atomprintnotab("\tscheme=\"%s\" version=\"%d\"\n", ostypetostr(scheme), s_version );
	// Get data
	if (flags & 1) {
		BAILIFERR( GetFileCString( aoe, &locationP, offset, aoe->maxOffset - offset, &offset ) );
		atomprint("location=\"%s\"\n", locationP);
	}

	atomprint(">\n");

	if(vg.cmaf){
		 if( scheme!= 'cenc' && scheme!='cbc1' && scheme!='cens' && scheme!='cbcs')
			errprint("CMAF check violated: Section 7.5.11. \"CMAF SHALL use Common Encryption for Tracks containing encrypted Segments.\",Scheme type 'cenc/cbc1/cens/cbcs' expected, but found %s\n",ostypetostr(scheme));

		 if(s_version !=0x00010000)
			 errprint("CMAF check violated: Section 7.5.11. \"CMAF SHALL use Common Encryption for Tracks containing encrypted Segments, scheme version SHALL be set to 0x00010000 \", but found %d\n",s_version);
		}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_schi_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	long cnt;
	atomOffsetEntry *list;
	long i;
	OSErr atomerr = noErr;
	atomOffsetEntry *entry;
	UInt64 minOffset, maxOffset;

	atomprintnotab(">\n");

	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;

	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );
	atomprint(" comment=\"%d contained atoms\" >\n",cnt);

	// Process 'tenc' atoms
		if(vg.cmaf){
			atomerr = ValidateAtomOfType( 'tenc', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne,
		Validate_tenc_Atom, cnt, list, nil );
			if (!err) err = atomerr;
		}
		else{
			atomerr = ValidateAtomOfType( 'tenc', kTypeAtomFlagCanHaveAtMostOne,
					Validate_tenc_Atom, cnt, list, nil );
			if (!err) err = atomerr;
		}

	bool schiFound;

	schiFound = false;

	for (i = 0; i < cnt; i++) {
		entry = &list[i];

		if (entry->aoeflags & kAtomValidated) continue;

		switch (entry->type) {

			case 'schi':
				schiFound = true;
			break;

			default:
				warnprint("WARNING: In %s - unknown schi atom '%s' length %ld\n",vg.curatompath, ostypetostr(entry->type), entry->size);
				break;
		}

		if(vg.dash264enc && schiFound == false)
			errprint("No 'tenc' atom found within 'schi' content when checks involed for encrypted content, violating SEction 8.2.1 of ISO-IEC_23001-7\n");

		if (!err) err = atomerr;
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_tenc_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

	// Get data
	UInt8	temp1[3];
	BAILIFERR( GetFileData( aoe,temp1, offset, 3 , &offset ) );

	UInt32 default_IsEncrypted;
	default_IsEncrypted = (temp1[2] << 16) + (temp1[1] << 8) + temp1[0];

	//For now, no use of it, just play around to suppress compiler warnings.
	default_IsEncrypted = default_IsEncrypted;

	UInt8   default_IV_size;
	BAILIFERR( GetFileData( aoe,&default_IV_size, offset, 1 , &offset ) );

	UInt8	default_KID[16];
	BAILIFERR( GetFileData( aoe,default_KID, offset, 16 , &offset ) );

	atomprint("default_IsEncrypted=\"%d\"\n", default_IsEncrypted);
	atomprint("default_IV_size=\"%d\"\n", default_IV_size);
	//Adjust KID before printing, ascii to integer.
	char tenc_KID[50], KID_char[20];
	tenc_KID[0]={'\0'};
	KID_char[0]={'\0'};
	for(int z=0;z<16;z++)
	{
		sprintf(KID_char,"%d",default_KID[z]);
		strcat(tenc_KID, KID_char);
	}
	atomprint("default_KID=\"%s\"\n", tenc_KID);
	atomprint(">\n");

	vg.tencInInit=true;// As the 'tenc' box is present in moov box (initialization segment).

	if((vg.ctawave || vg.cmaf) && default_IsEncrypted!=1){
		errprint("CMAF Check violated : Section 8.2.3.2. \"In an encrypted Track, the isProtected flag in the TrackEncryptionBox SHALL be set to 1.\",found %ld \n",default_IsEncrypted);
	}

	//Check the default_KID is matching with the one mentioned in the MPD

	 char *st;
	 //st[0]={'\0'};
	 char mpd_kid[50],buf;
	 mpd_kid[0]={'\0'};
	 st= vg.default_KID;
	 if(st[0]!= '\0'){
	remove_all_chars(st, '-'); //
	int length,i;
	length= strlen(st);

	//j=0;
	buf= 0;
	for(i = 0; i < length; i++){
		if(i % 2 != 0){
					sprintf(KID_char,"%d",hex_to_ascii(buf, st[i]));
					strcat(mpd_kid, KID_char);
		}else{
			buf = st[i];
		}
	}

	//mpd_kid[j]='\0';

	//sprintf(tenc_kid,"%s",default_KID);

	if(strcmp(tenc_KID, mpd_kid)!=0)
		errprint("default_KID in 'tenc' is not matching with cenc:default_KID attribute of MPD\n");
	 }
	// All done
	aoe->aoeflags |= kAtomValidated;
bail:
	return err;
}


//==========================================================================================

OSErr Validate_xml_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt32 vers, flags;
	//char	tempStr[100];
	char *xmlP = nil;

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomStartRecord ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );

	BAILIFERR( GetFullAtomVersionFlags( aoe, &vers, &flags, &offset));

	// Get data
	if (ahdr.atomType == 'xml ') {
		BAILIFERR( GetFileCString( aoe, &xmlP, offset, aoe->maxOffset - offset, &offset ) );
		atomprint("XML=\"%s\"\n", xmlP);
	}
	else atomprintnotab("\t..contains %d bytes\n", ahdr.atomSize - 8 );

	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_iloc_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt32 i, temp, vers, flags, offset_size, length_size, base_offset_size;
	UInt16 item_count;
	UInt8 temp8;
	//char	tempStr[100];

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomStartRecord ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );

	BAILIFERR( GetFullAtomVersionFlags( aoe, &vers, &flags, &offset));

	BAILIFERR( GetFileData( aoe, &temp8,	offset, 1, &offset ) );
	offset_size = (temp8 >> 4) & 0x0F;
	length_size = temp8 & 0x0F;
	BAILIFERR( GetFileData( aoe, &temp8,	offset, 1, &offset ) );
	base_offset_size = (temp8 >> 4) & 0x0F;

	BAILIFERR( GetFileData( aoe, &item_count, offset, 2, &offset ) );
	item_count	= EndianU16_BtoN( item_count );

	atomprintnotab("\toffset_size=\"%d\" length_size=\"%d\" base_offset_size=\"%d\" item_count=\"%d\">\n",
			offset_size, length_size, base_offset_size, item_count);

	vg.tabcnt++;
	for (i=0; i<item_count; i++) {
		UInt16 item_id, dref_idx, ext_count;
		UInt64 base_offset;
		UInt32 j;
		BAILIFERR( GetFileData( aoe, &item_id, offset, 2, &offset ) );
		item_id	= EndianU16_BtoN( item_id );
		BAILIFERR( GetFileData( aoe, &dref_idx, offset, 2, &offset ) );
		dref_idx	= EndianU16_BtoN( dref_idx );
		switch (base_offset_size) {
			case 0: base_offset = 0; break;
			case 4:
				BAILIFERR( GetFileData( aoe, &temp, offset, 4, &offset ) );
				base_offset	= EndianU32_BtoN( temp );
				break;
			case 8:
				BAILIFERR( GetFileData( aoe, &base_offset, offset, 8, &offset ) );
				base_offset	= EndianU64_BtoN( base_offset );
				break;
			default:
				errprint("You can't have a base offset size of %d in iloc\n", base_offset_size);
		}
		BAILIFERR( GetFileData( aoe, &ext_count, offset, 2, &offset ) );
		ext_count	= EndianU16_BtoN( ext_count );
		atomprint("<item item_id=\"%d\" dref_idx=\"%d\" base_offset=\"%s\" ext_count=\"%d\">\n",
			item_id, dref_idx, int64todstr(base_offset), ext_count);
		vg.tabcnt++;
		for (j=0; j<ext_count; j++) {
			UInt64 e_offset, e_length;
			char temp1[100];
			char temp2[100];
			switch (offset_size) {
				case 0: e_offset = 0; break;
				case 4:
					BAILIFERR( GetFileData( aoe, &temp, offset, 4, &offset ) );
					e_offset	= EndianU32_BtoN( temp );
					break;
				case 8:
					BAILIFERR( GetFileData( aoe, &e_offset, offset, 8, &offset ) );
					e_offset	= EndianU64_BtoN( e_offset );
					break;
				default:
					errprint("You can't have an offset size of %d in iloc\n", offset_size);
			}
			switch (length_size) {
				case 0: e_length = 0; break;
				case 4:
					BAILIFERR( GetFileData( aoe, &temp, offset, 4, &offset ) );
					e_length	= EndianU32_BtoN( temp );
					break;
				case 8:
					BAILIFERR( GetFileData( aoe, &e_length, offset, 8, &offset ) );
					e_length	= EndianU64_BtoN( e_length );
					break;
				default:
					errprint("You can't have a length size of %d in iloc\n", length_size);
			}
			atomprint("<extent extent_offset=\"%s\" extent_length=\"%s\"\\>\n", int64todstr_r(e_offset, temp1), int64todstr_r(e_length,temp2));
		}
		--vg.tabcnt;
		atomprint("<\\item>\n");
	}
	--vg.tabcnt;

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_pitm_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt16 item_id;
	UInt32 vers, flags;
	//char	tempStr[100];

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomSizeType ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );

	BAILIFERR( GetFullAtomVersionFlags( aoe, &vers, &flags, &offset));

	BAILIFERR( GetFileData( aoe, &item_id, offset, sizeof( UInt16 ), &offset ) );
	item_id = EndianU16_BtoN( item_id );

	atomprintnotab("\tprimary_item_ID=\"%d\"\n", item_id );
	atomprint(">\n");

	if( ahdr.atomSize != (6 + sizeof(AtomSizeType)) ){
		err = badAtomSize;
		errprint( "wrong atom size for 'pitm' atom (%d) should be %d \n", ahdr.atomSize, (6 + sizeof(AtomSizeType)) );
		goto bail;
	}

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_ipro_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt16 prot_count;
	UInt32 vers, flags;
	//char	tempStr[100];

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomSizeType ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );

	BAILIFERR( GetFullAtomVersionFlags( aoe, &vers, &flags, &offset));

	BAILIFERR( GetFileData( aoe, &prot_count, offset, sizeof( UInt16 ), &offset ) );
	prot_count = EndianU16_BtoN( prot_count );

	atomprintnotab("\tprot_count=\"%d\"\n", prot_count );
	vg.tabcnt++;
	{
		UInt64 minOffset;
		UInt64 maxOffset;
		atomOffsetEntry *entry;
		long cnt;
		atomOffsetEntry *list;
		int i;

		minOffset = offset;
		maxOffset = aoe->offset + aoe->size;

		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

		if (cnt != prot_count) errprint("Found %d atoms but expected %d\n", cnt, prot_count);

		for (i = 0; i < cnt; i++) {
			entry = &list[i];

			if ( entry->type == 'sinf' )
			{
				// Process 'sinf' atoms
				atomprint("<sinf"); vg.tabcnt++;
				BAILIFERR( Validate_sinf_Atom( entry, refcon, 0 ) );
				--vg.tabcnt; atomprint("</sinf>\n");
			}

			else warnprint("Warning: In %s - unknown atom found \"%s\": ipro atoms would not normally contain this\n",vg.curatompath, ostypetostr(entry->type));

		}
	}
	--vg.tabcnt;
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_infe_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt32 vers, flags;
	//char	tempStr[100];
	char *nameP = nil;
	char *typeP = nil;
	char *encodP = nil;
	UInt16 item_id, prot_idx;

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomStartRecord ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );

	BAILIFERR( GetFullAtomVersionFlags( aoe, &vers, &flags, &offset));

	BAILIFERR( GetFileData( aoe, &item_id,	offset, sizeof( UInt16 ), &offset ) );
	BAILIFERR( GetFileData( aoe, &prot_idx, offset, sizeof( UInt16 ), &offset ) );
	item_id  = EndianU16_BtoN( item_id );
	prot_idx = EndianU16_BtoN( prot_idx );

	atomprintnotab("\t item_id=\"%d\" protection_index=\"%d\"\n", item_id, prot_idx );
	BAILIFERR( GetFileCString( aoe, &nameP, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("item_name=\"%s\"\n", nameP);
	BAILIFERR( GetFileCString( aoe, &typeP, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("content_type=\"%s\"\n", typeP);
	BAILIFERR( GetFileCString( aoe, &encodP, offset, aoe->maxOffset - offset, &offset ) );
	atomprint("content_encoding=\"%s\"\n", encodP);

	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_iinf_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	AtomSizeType ahdr;
	UInt16 inf_count;
	UInt32 vers, flags;
	//char	tempStr[100];

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &ahdr, offset, sizeof( AtomSizeType ), &offset ) );
	ahdr.atomSize = EndianU32_BtoN( ahdr.atomSize );
	ahdr.atomType = EndianU32_BtoN( ahdr.atomType );

	BAILIFERR( GetFullAtomVersionFlags( aoe, &vers, &flags, &offset));

	BAILIFERR( GetFileData( aoe, &inf_count, offset, sizeof( UInt16 ), &offset ) );
	inf_count = EndianU16_BtoN( inf_count );

	atomprintnotab("\tinf_count=\"%d\"\n", inf_count );
	vg.tabcnt++;
	{
		UInt64 minOffset;
		UInt64 maxOffset;
		atomOffsetEntry *entry;
		long cnt;
		atomOffsetEntry *list;
		int i;

		minOffset = offset;
		maxOffset = aoe->offset + aoe->size;

		BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

		if (cnt != inf_count) errprint("Found %d atoms but expected %d\n", cnt, inf_count);

		for (i = 0; i < cnt; i++) {
			entry = &list[i];

			if ( entry->type == 'infe' )
			{
				// Process 'infe' atoms
				atomprint("<infe"); vg.tabcnt++;
				BAILIFERR( Validate_infe_Atom( entry, refcon ) );
				--vg.tabcnt; atomprint("</infe>\n");
			}

			else warnprint("Warning: In %s - unknown atom found \"%s\": iinf atoms would not normally contain this\n",vg.curatompath, ostypetostr(entry->type));

		}
	}
	--vg.tabcnt;
	atomprint(">\n");

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_senc_Atom( atomOffsetEntry *aoe, void *refcon )
{
		OSErr err = noErr;
		UInt32 version;
		UInt32 flags;
		UInt64 offset;

		// Get version/flags
		BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
		atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);
		atomprint("offset=\"%ld\"\n", aoe->offset);

		UInt32   sample_count;
		BAILIFERR( GetFileData( aoe,&sample_count, offset, 4 , &offset ) );
		sample_count=EndianU32_BtoN(sample_count);

		//TODO Allocate resources to above members according to sample and subsample counts.

		atomprint("sample_count=\"%ld\"\n", sample_count);
		atomprint(">\n");

		vg.sencFound= true;
		// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}

OSErr Validate_saio_Atom( atomOffsetEntry *aoe, void *refcon )
{
		OSErr err = noErr;
		UInt32 version;
		UInt32 flags;
		UInt64 offset,temp1;

		// Get version/flags
		BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
		atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

		UInt32 aux_info_typ;
		UInt32 aux_info_type_parameter;
		UInt32 entry_count, temp;

		if(flags & 1){
			BAILIFERR( GetFileData( aoe, &aux_info_typ,  offset, sizeof( UInt32 ), &offset ) );
		aux_info_typ= EndianU32_BtoN(aux_info_typ);
			BAILIFERR( GetFileData( aoe, &aux_info_type_parameter,  offset, sizeof( UInt32 ), &offset ) );
			if(vg.cmaf && aux_info_typ!='cenc')
				errprint("CMAF check violated: Section 8.2.2.1: \"For encrypted Fragments that contain Sample Auxiliary Informantion, 'saio' SHALL be present with aux_info_type value of 'cenc'\", but found %s\n",ostypetostr(aux_info_typ));
		}

		BAILIFERR( GetFileData( aoe, &entry_count,  offset, sizeof( UInt32 ), &offset ) );
		entry_count = EndianU32_BtoN(entry_count);
		atomprint("entry_count=\"%ld\"\n", entry_count);
		//atomprint("aux_info_typ=\"%s\"\n", ostypetostr(aux_info_typ));

		//TODO Allocate saio_offset based on entry_count.
		if(version ==0)
		{
			UInt32 saio_offset[entry_count];
			for(UInt32 i=0;i<entry_count;i++)
			{
				BAILIFERR( GetFileData( aoe, &temp,  offset, sizeof( UInt32 ), &offset ) );
				saio_offset[i] = EndianU32_BtoN(temp);
				atomprint("saio_offset_%d=\"%ld\"\n", i, saio_offset[i]);
			}
		}
		else
		{
			UInt64 saio_offset[entry_count];
			for(UInt32 i=0;i<entry_count;i++)
			{
				BAILIFERR( GetFileData( aoe, &temp1,  offset, sizeof( UInt64 ), &offset ) );
				saio_offset[i] = EndianU64_BtoN(temp1);
				atomprint("saio_offset_%d=\"%ld\"\n", i, saio_offset[i]);
			}
		}


		atomprint(">\n");

		if(vg.cmaf && entry_count!=1)
			errprint("CMAF check violated: Section 8.2.2.1: \"The entry_count field of the SampleAuxiliaryInformationOffsetsBox SHALL equal 1\", but found %ld\n",entry_count);

		// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	return err;
}
// Validate function for HEVC atom and ConfigRecord.
OSErr Validate_hvcC_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
#pragma unused(refcon)
	OSErr err = noErr;
	UInt64 offset;
	HevcConfigInfo hevcHeader;
	void* bsDataP;
	BitBuffer bb;

	atomprint("<%s", esname); vg.tabcnt++;

	// Get version/flags
	offset = aoe->offset;
	BAILIFERR( GetFileData( aoe, &hevcHeader.start.atomSize, offset, sizeof( AtomSizeType ), &offset ) );
	hevcHeader.start.atomSize = EndianU32_BtoN( hevcHeader.start.atomSize );
	hevcHeader.start.atomType = EndianU32_BtoN( hevcHeader.start.atomType );


	BAILIFNIL( bsDataP = calloc(hevcHeader.start.atomSize - 8 + bitParsingSlop, 1), allocFailedErr );
	BAILIFERR( GetFileData( aoe, bsDataP, offset, hevcHeader.start.atomSize - 8, &offset ) );
	BitBuffer_Init(&bb, (UInt8 *)bsDataP, hevcHeader.start.atomSize - 8);

	BAILIFERR( Validate_HEVCConfigRecord( &bb, refcon ) );
	//--vg.tabcnt; atomprint("</%s>\n", esname);


	free( bsDataP );

	// All done
	aoe->aoeflags |= kAtomValidated;

bail:
	--vg.tabcnt; atomprint("</%s>\n", esname);
	return err;
}

OSErr Validate_pasp_Atom( atomOffsetEntry *aoe, void *refcon, char *esname )
{
	OSErr err = noErr;
	UInt32 version;
	UInt32 flags;
	UInt64 offset;

	atomprint("<pasp"); vg.tabcnt++;

	// Get version/flags
	BAILIFERR( GetFullAtomVersionFlags( aoe, &version, &flags, &offset ) );
	atomprintnotab("\tversion=\"%d\" flags=\"%d\"\n", version, flags);

	// Get data
	UInt32 hSpacing;
	UInt32 vSpacing;
	BAILIFERR( GetFileData( aoe,&hSpacing, offset, 4 , &offset ) );
	BAILIFERR( GetFileData( aoe,&vSpacing, offset, 4 , &offset ) );

	--vg.tabcnt; atomprint("/>\n");

	// All done
	aoe->aoeflags |= kAtomValidated;


bail:
	return err;
}
