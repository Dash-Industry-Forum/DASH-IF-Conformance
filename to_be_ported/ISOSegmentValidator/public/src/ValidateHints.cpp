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


/*
	To Do:
	
	- validate user data atom 'hinf'
		
	- validate sample description
	
	- print sample time of each sample
		- validate sampletimes of audio based on number of au's in packets
	
	- if any packet > stated max packet size, warning
	
	- warn if there's no track ref but we need it
	
	- sdp
		- other generic sdp validation (o= etc)
	
		- validate esd in track matches what's in iod
		- validate esd in sdp matches original track's esd

		- audio
			- get params based on what's in sdp
			- match params to what's in the spec - warning if no match
			
		- print sdp with nice wrapping

	- log to a file? window overflows in standalone mode
	
	
*/

#include "ValidateMP4.h"

// ---------------------------------------------------------------------------
//		D E F I N I T I O N S
// ---------------------------------------------------------------------------

#define kHintDataTableEntrySize		16

#define kSelfTrackRefIndex			0xFF

#define kBitsPerByte				8

#define kSpaceChar					' '


#define kMaxSDPPayloadNameLength	255
#define kMaxSDPModeNameLength		31

typedef OSErr (*ValidatePayloadTypeProcPtr)( char *inPayload, UInt32 inLength, void *refcon );
#define CallValidatePayloadTypeProc(userRoutine, inPayload, inLength, inRefCon)		\
		(*(userRoutine))((inPayload),(inLength),(inRefCon))


typedef struct {
	UInt8		payloadNum;
	char		payloadName[kMaxSDPPayloadNameLength+1];
	char		modeName[kMaxSDPModeNameLength+1];
	ValidatePayloadTypeProcPtr	payloadValidateProc;
	
	
} SDPInfoRec;


typedef struct {
	atomOffsetEntry	*aoe;
	TrackInfoRec	*tir;
	
	TrackInfoRec	*originalMediaTIR;
	
	// ----- settings
	UInt32			validateLevel;
	Boolean			validatePayload;
		
	UInt32			verboseLevel;
	Boolean			printSamples;
	Boolean			printPayloadContents;
	
	UInt32			startingSampleNum;
	UInt32			numSamplesToValidate;

	SDPInfoRec		sdpInfo;
	
	
	// -----
	UInt32			hintSampleNum;	
	Ptr				hintSampleData;
	UInt32			hintSampleLength;

	Boolean			constructPacket;
	Boolean			packetConstructedOK;
	Ptr				packetData;
	char			*packetDataCurrent;
	UInt32			packetDataMaxLength;
	
	// ----- params for audio payload
	Boolean			genericPayloadParamsOK;
	SInt32			genericPayloadMode;
	UInt16			numLengthBits;
	UInt16			numIndexBits;
	UInt16			numIndexDeltaBits;
	UInt16			bytesPerHeader;
	UInt16			indexMask;
	UInt16			maxFrameLength;
	UInt16			constantSize;

} HintInfoRec;

// ---------------------------------------------------------------------------
//		P R O T O T Y P E S
// ---------------------------------------------------------------------------

#define H_ATOM_PRINT(_x)		{ if (doPrinting) {atomprint _x ;}}
#define H_ATOM_PRINT_INCR(_x)	{ if (doPrinting) {atomprint _x ;  vg.tabcnt++;}}
#define H_ATOM_PRINT_DECR(_x)	{ if (doPrinting) {vg.tabcnt--; atomprint _x ;}}
#define H_ATOM_PRINT_HEXDATA(_x, _l)	{ if (doPrinting) {atomprinthexdata((_x), (_l)) ;}}


static OSErr Validate_Hint_Sample( HintInfoRec *hir, char *inSampleData, UInt32 inLength );
static OSErr Validate_Packet_Entry( HintInfoRec *hir, char *inPacketEntry, UInt32 inMaxLength, char **outNextEntryPtr );
static OSErr Validate_Data_Entry( HintInfoRec *hir, char *inEntry );

static OSErr Validate_rfc3016_Payload( char *inPayload, UInt32 inLength, void *refcon );
static OSErr Validate_Generic_MPEG4_Audio_Payload( char *inPayload, UInt32 inLength, void *refcon );
static OSErr Validate_H264_Payload( char *inPayload, UInt32 inLength, void *refcon );

static OSErr Validate_hint_udta_Atom( atomOffsetEntry *aoe, void *refcon );
static OSErr Validate_hinf_Atom( atomOffsetEntry *aoe, void *refcon );
static OSErr Validate_hnti_Atom( atomOffsetEntry *aoe, void *refcon );

static OSErr Validate_Track_SDP( HintInfoRec *hir, char *inSDP );
static OSErr Validate_SDP_Media_Line( HintInfoRec *hir, char *inLine );
static OSErr Validate_SDP_Attribute_Line( HintInfoRec *hir, char *inLine );

static OSErr Validate_fmtp_attribute( HintInfoRec *hir, char *inValue);
static OSErr Validate_rtpmap_attribute( HintInfoRec *hir, char *inValue);
static OSErr Validate_iod_attribute( HintInfoRec *hir, char *inValue);
static OSErr Validate_isma_attribute( HintInfoRec *hir, char *inValue);


static OSErr SDP_Get_Tag( char **ioCurrent, char *outTag );
static char *SDP_Find_Line_End( char *inCurrent );
static void Validate_SDP_Line_Ending( HintInfoRec *hir, char *inEndOfLine );
static char *SDP_Skip_Line_Ending_Chars( char *inLineEnd );

static SInt32 Chars_To_Num( char *inCharsStart, char *inCharsEnd, Boolean *outFoundNum );
static SInt32 Chars_To_hexNum( char *inCharsStart, char *inCharsEnd, Boolean *outFoundNum );
static Boolean is_num( char *inCharsStart, char *inCharsEnd);
static Boolean is_hexnum( char *inCharsStart, char *inCharsEnd);
static Boolean is_in_range( SInt32 inNum, SInt32 inMin, SInt32 inMax);
static Boolean compare_nocase(const char *s1, const char *s2);
static Boolean get_next_fmtp_param(char **inLine, char **outTagString, char **outParamValue);

static OSErr get_original_track_info(UInt32 inRefTrackID, TrackInfoRec **outTIR);
static OSErr get_track_sample(TrackInfoRec *tir, UInt32 inSampleNum, Ptr *dataOut, UInt32 *sizeOut, UInt32 *sampleDescriptionIndexOut);


// use hex equivalents instead of '\r' and '\n' since some compilers (MPW) are different
#define	CR		0x0d
#define	LF		0x0a


//==========================================================================================

#define kMPEG43016_PayloadName			"mp4v-es"


//==========================================================================================

#define kMPEG4Generic_PayloadName		"mpeg4-generic"
	

#define kMPEG4GenericParam_Mode					"mode"
#define kMPEG4GenericParam_ConstantSize			"constantsize"
#define kMPEG4GenericParam_SizeLength			"sizelength"
#define kMPEG4GenericParam_IndexLength			"indexlength"
#define kMPEG4GenericParam_IndexDeltaLength		"indexdeltalength"


#define kMPEG4GenericModeName_CELPCBR			"CELP-cbr"
	#define kMPEGGeneric_CELPCBR_OverheadBytesPerFrame		0


#define kMPEG4GenericModeName_CELPVBR			"CELP-vbr"
	#define kMPEG4Generic_CELPVBR_SizeLengthDefault			6
	#define kMPEG4Generic_CELPVBR_IndexLengthDefault		2
	#define kMPEG4Generic_CELPVBR_IndexDeltaLengthDefault	2
	#define kMPEGGeneric_CELPVBR_OverheadBytesPerFrame		1
	#define kMPEGGeneric_CELPVBR_IndexMask					0x03			
	
	#define kMPEG4Generic_CELPVBR_MaxFrameLength			63


#define kMPEG4GenericModeName_AACLowBitRate		"AAC-lbr"
	#define kMPEG4Generic_AACLBR_SizeLengthDefault			6
	#define kMPEG4Generic_AACLBR_IndexLengthDefault			2
	#define kMPEG4Generic_AACLBR_IndexDeltaLengthDefault	2
	
	#define kMPEGGeneric_AACLBR_OverheadBytesPerFrame		1
	#define kMPEGGeneric_AACLBR_IndexMask					0x03			

	#define kMPEG4Generic_AACLBR_MaxFrameLength			63


#define kMPEG4GenericModeName_AACHighBitRate	"AAC-hbr"
	#define kMPEG4Generic_AACHBR_SizeLengthDefault			13
	#define kMPEG4Generic_AACHBR_IndexLengthDefault			3
	#define kMPEG4Generic_AACHBR_IndexDeltaLengthDefault	3
	#define kMPEGGeneric_AACHBR_OverheadBytesPerFrame		2

	#define kMPEGGeneric_AACHBR_IndexMask					0x0007			

	#define kMPEG4Generic_AACHBR_MaxFrameLength			8191


// these numbers aren't in the spec - we just made them up so we can
// keep track of what mode we're using
enum  {

	kMPEG4GenericMode_CELPCBR	= 1,
	kMPEG4GenericMode_CELPVBR = 2,
	kMPEG4GenericMode_AACLowBitRate = 3,
	kMPEG4GenericMode_AACHighBitRate = 4
};


//==========================================================================================

#define kH264_PayloadName			"H264"
#define kNAL_STAP_A		24
#define kNAL_STAP_B		25
#define kNAL_MTAP16		26
#define kNAL_MTAP24		27
#define kNAL_FU_A		  28
#define kNAL_FU_B		  29


//==========================================================================================

OSErr Validate_Hint_Track( atomOffsetEntry *aoe, TrackInfoRec *tir )
{
	OSErr		err = noErr;
	UInt64		sampleOffset;
	UInt32		sampleSize;
	UInt32		sampleDescriptionIndex;
	Ptr			dataP = nil;
	UInt32		i;
	UInt32		startSampleNum;
	UInt32		endSampleNum;
	Boolean		doPrinting = false;
	HintInfoRec	hir = {};
	
	UInt64 minOffset, maxOffset;
	SInt32 cnt;
	atomOffsetEntry *list;
	OSErr		tempErr;

	// -------------------------------------------------------
		hir.aoe = aoe;
		hir.tir = tir;
		hir.hintSampleNum = 0;
		hir.hintSampleData = NULL;
		
		hir.constructPacket = true;
		hir.validatePayload = true;

		if (vg.checklevel >= checklevel_samples) {
			hir.printSamples = true;
		}
		if (vg.checklevel >= checklevel_payload) {
			hir.printPayloadContents = true;
		}
		
		//hir.numSamplesToValidate = 1;

		//vg.printatom = true;
		
	// -------------------------------------------------------
		

	if (hir.printSamples) {
		doPrinting = true;
	}
	
	if (hir.constructPacket) {
		hir.packetDataMaxLength = 10*1024;
		BAILIFNIL( hir.packetData = (Ptr)malloc(hir.packetDataMaxLength), allocFailedErr );
		hir.packetDataCurrent = 0;
	}

	if (tir->hintRefTrackID != 0) {
		get_original_track_info(tir->hintRefTrackID, &(hir.originalMediaTIR));
	}
	
	// Process 'udta' atoms
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

	tempErr = ValidateAtomOfType( 'udta', kTypeAtomFlagMustHaveOne | kTypeAtomFlagCanHaveAtMostOne, 
		Validate_hint_udta_Atom, cnt, list, &hir );
	if (!err) err = tempErr;

	H_ATOM_PRINT(("<payloadnum=\"%d\" payloadname=\"%s\">\n", hir.sdpInfo.payloadNum, hir.sdpInfo.payloadName));
	if (hir.originalMediaTIR != NULL) {
		H_ATOM_PRINT(("<streammediatype=\"%.4s\">\n", &hir.originalMediaTIR->mediaType));
		//@@@ remove this restriction for other file type
		if (hir.originalMediaTIR->mediaType == 'soun') {
			if (!compare_nocase(hir.sdpInfo.payloadName, kMPEG4Generic_PayloadName)) {
				errprint("audio payload name should be '%s'", kMPEG4Generic_PayloadName);
			}
			if (!hir.genericPayloadParamsOK) {
				errprint("wrong or missing params - cannot validate payload\n");
			} else {
				H_ATOM_PRINT(("<genericmode=\"%s\">", hir.sdpInfo.modeName));
			}
		} else if (hir.originalMediaTIR->mediaType == 'vide') {
			if ((!compare_nocase(hir.sdpInfo.payloadName, kMPEG43016_PayloadName)) &&
				(!compare_nocase(hir.sdpInfo.payloadName, kH264_PayloadName)) ) {
				errprint("video payload name should be '%s' or '%s'", kMPEG43016_PayloadName, kH264_PayloadName);
			}
		}
	}


	// TODO: vaidate the payload matches the original track type (e.g. video payload
	// for a hinted video track)
	if (hir.startingSampleNum > 0) {
		startSampleNum = hir.startingSampleNum;
	} else {
		startSampleNum = 1;
	}
	if (hir.numSamplesToValidate!= 0) {
		endSampleNum = startSampleNum + hir.numSamplesToValidate-1;
	} else {
		endSampleNum = tir->sampleSizeEntryCnt;
	}

	H_ATOM_PRINT_INCR(("<hint_SAMPLE_DATA>\n"));
	if(!vg.dashSegment)
		for (i = startSampleNum; i <= endSampleNum; i++) {
			if ((vg.samplenumber==0) || (vg.samplenumber==(SInt32)i)) {
				err = GetSampleOffsetSize( tir, i, &sampleOffset, &sampleSize, &sampleDescriptionIndex );
				if (err != noErr) {
					errprint("couldn't GetSampleOffsetSize for sample %ld (err %ld)\n", i, err);
					continue;
				}
				H_ATOM_PRINT_INCR(( "<sample num=\"%d\" offset=\"%s\" size=\"%d\"\n",i,int64toxstr(sampleOffset),sampleSize));
					BAILIFNIL( dataP = (Ptr)malloc(sampleSize), allocFailedErr );
					err = GetFileData( vg.fileaoe, dataP, sampleOffset, sampleSize, nil );
					if (err != noErr) {
						errprint("couldn't GetFileData for sample %ld (err %ld)\n", i, err);
						continue;
					}
									
					hir.hintSampleNum = i;
					hir.hintSampleData = dataP;
					hir.hintSampleLength = sampleSize;
					Validate_Hint_Sample(&hir, dataP, sampleSize);

					free( dataP );
					hir.hintSampleData = NULL;
				H_ATOM_PRINT_DECR(("</sample>\n"))
			}
		}
	H_ATOM_PRINT_DECR(("</hint_SAMPLE_DATA>\n"));

bail:
	if (hir.packetData != NULL) {
		free(hir.packetData);
	}
	return err;
}

//==========================================================================================
static OSErr Validate_Hint_Sample( HintInfoRec *hir, char *inSampleData, UInt32 inLength )
{
	OSErr		err = noErr;
	UInt16		numPackets;
	UInt16		i;
	char		*packetEntryPtr;
	UInt16		temp16;
	char		*next;
	Boolean		doPrinting = hir->printSamples;

	/*
		2	entry count
		2	reserved (0)
		var	packet entry table
		var	additional data
	*/	
	
	numPackets = EndianU16_BtoN(*((UInt16*)inSampleData));
	temp16 = EndianU16_BtoN(*((UInt16*)(inSampleData+2)));
	if ((temp16 != 0) && (temp16 != 65535)) {
		// alas the spec forgets to say what the reserved value is, and mpeg people always think
		//  -1, whereas RTP people think 0 [dws]
		warnprint("WARNING - reserved in sample data %ld should be 0 or -1\n", temp16);
	}
	H_ATOM_PRINT(("numPackets=\"%ld\"\n", numPackets));
	H_ATOM_PRINT(("reserved=\"%ld\"\n", temp16));

	if (numPackets == 0) {
		warnprint("WARNING - numPackets = 0\n");
	}

	packetEntryPtr = inSampleData + sizeof(UInt16) + sizeof(UInt16);
	for (i=0; i<numPackets; ++i) {
		H_ATOM_PRINT_INCR(("<packet=\"%ld\">\n", i))
			BAILIFERR(Validate_Packet_Entry(hir, packetEntryPtr, inLength - (UInt16)(packetEntryPtr-inSampleData), &next ));
			packetEntryPtr = next;
		H_ATOM_PRINT_DECR(("</packet>\n"))
		if (packetEntryPtr > (inSampleData + inLength)) {
			errprint("ERROR - hint sample packet entries %ld overflowed\n", i);
			err = outOfDataErr;
			goto bail;
		}
	}

bail:
	return err;
}

//==========================================================================================
static OSErr Validate_Packet_Entry( HintInfoRec *hir, char *inPacketEntry, UInt32 inMaxLength, char **outNextEntryPtr )
{
	OSErr		err = noErr;
	UInt32		temp32;
	UInt16		temp16;
	char		*current = inPacketEntry;
	Boolean		hasExtraInfoTLVs = false;
	UInt16		entryCount;
	UInt16		i;
	Boolean		doPrinting = hir->printSamples;

	/*
		4			relative packet transmission
		2			rtp header info
		2			rtp sequence number
		2			flags
		2			entry count
		0 or var	extra info TLVs
		var			data table	
	*/
	
#define kRTPHeader_PBit			0x2000
#define kRTPHeader_MBit			0x0080
#define kRTPHeader_PayloadMask	0x007F

	temp32 = EndianU32_BtoN(*((UInt32*)current));
	current += sizeof(temp32);
	H_ATOM_PRINT(("relativeTransmissionTime=\"%ld\"\n", temp32));
	temp16 = EndianU16_BtoN(*((UInt16*)current));
	current += sizeof(temp16);
	H_ATOM_PRINT_INCR(("<rtpHeader>\n"));
//@@@ check reserved fields of rtp header
		H_ATOM_PRINT(("P=\"%d\"\n", ((temp16 & kRTPHeader_PBit) != 0)));
		H_ATOM_PRINT(("M=\"%d\"\n", ((temp16 & kRTPHeader_MBit) != 0)));
		H_ATOM_PRINT(("payloadType=\"%d\"\n", temp16 & kRTPHeader_PayloadMask));
	H_ATOM_PRINT_DECR(("</rtpheader>\n"));

	temp16 = EndianU16_BtoN(*((UInt16*)current));
	current += sizeof(temp16);
	H_ATOM_PRINT(("sequenceNumber=\"%ld\"\n", temp16))

#define kPacketEntry_XBit		0x0004
#define kPacketEntry_BBit		0x0002
#define kPacketEntry_RBit		0x0001


	temp16 = EndianU16_BtoN(*((UInt16*)current));
	current += sizeof(temp16);
	H_ATOM_PRINT(("flags=\"%.4x\"\n", temp16));
//@@@ check reserved fields of flags
	hasExtraInfoTLVs = ((temp16 & kPacketEntry_XBit) != 0);
	H_ATOM_PRINT(("BFrame=\"%d\"\n", ((temp16 & kPacketEntry_BBit) != 0)));
	H_ATOM_PRINT(("repeatPacket=\"%d\"\n", ((temp16 & kPacketEntry_RBit) != 0)));

	entryCount = EndianU16_BtoN(*((UInt16*)current));
	current += sizeof(temp16);
	H_ATOM_PRINT(("numTableEntries=\"%ld\"\n", entryCount));

	if (hasExtraInfoTLVs) {
		char *tlv;
		temp32 = EndianU32_BtoN(*((UInt32*)current));
		tlv = current + sizeof(temp32);
		current += temp32;
		while (tlv < current) {
			UInt32 boxlen, boxtype;
			char *tlvdata;
			tlvdata = tlv;
			boxlen = EndianU32_BtoN(*((UInt32*)tlvdata)); tlvdata += sizeof(temp32);
			boxtype = EndianU32_BtoN(*((UInt32*)tlvdata)); tlvdata += sizeof(temp32);
			if (boxtype == 'rtpo') {
				temp32 = EndianU32_BtoN(*((UInt32*)tlvdata));
				H_ATOM_PRINT(("RTP timestamp offset=\"%ld\"\n", temp32));
			}
			else warnprint("Warning: Unknown packet extra info TLV %s\n",ostypetostr(boxtype));
			tlv += (boxlen + 3) & (0xFFFFFFFc);		// rounded up to a 4-byte boundary
		}
	}


	if (current + (entryCount * kHintDataTableEntrySize) > inPacketEntry + inMaxLength) {
		errprint("entrycount %ld is too big for data size\n", entryCount);
		err = outOfDataErr;
		goto bail;
	}

	hir->packetConstructedOK = true;
	hir->packetDataCurrent = hir->packetData;
	for (i=0; i<entryCount; ++i) {
		H_ATOM_PRINT_INCR(("<dataEntry=\"%ld\">\n", i));
			Validate_Data_Entry(hir, current);
			current += kHintDataTableEntrySize;
		H_ATOM_PRINT_DECR(("</dataEntry>\n"));
	}
	*outNextEntryPtr = current;
	
	
	if (hir->constructPacket && hir->validatePayload && hir->packetData && hir->packetConstructedOK) {
		if (hir->sdpInfo.payloadValidateProc != NULL) {
			CallValidatePayloadTypeProc(hir->sdpInfo.payloadValidateProc, 
					(char*)hir->packetData, (UInt32)(hir->packetDataCurrent - hir->packetData),(void*)hir);
		}
	}


bail:
	return err;
}

//==========================================================================================
static OSErr Validate_Data_Entry( HintInfoRec *hir, char *inEntry )
{
	OSErr			err = noErr;
	char			dataSource;
	SInt8			trackRefIndex;
	UInt32			sampleNum;
	UInt32			offset;
	UInt16			length;
	UInt16			temp16;
	UInt32			temp32;
	char			*current;
	Ptr				sampleData = NULL;
	UInt32			sampleDataLength = 0;
	Boolean			doPrinting = hir->printSamples;

	/*
		1	data source
		15	data (depends on value in dataSource)
	*/


	enum {
		kHintTrackDataSource_None	= 0,
		kHintTrackDataSource_Immediate	= 1,
		kHintTrackDataSource_Sample	= 2,
		kHintTrackDataSource_SampleDescription	= 3
	};	
	
	dataSource = inEntry[0];
	current = inEntry + 1;
	switch (dataSource) {
		case kHintTrackDataSource_None:
			H_ATOM_PRINT(("dataSource=none\n"));
			break;
		case kHintTrackDataSource_Immediate:
			H_ATOM_PRINT(("dataSource=immediate\n"));
			H_ATOM_PRINT(("length=\"%d\"\n", inEntry[1]));
			H_ATOM_PRINT_HEXDATA((char*)inEntry+2, kHintDataTableEntrySize-2);
			if (inEntry[1] > kHintDataTableEntrySize-2) {
				errprint("data entry immediate length (%d) > 14", inEntry[1]);
				err = paramErr;
				goto bail;
			}
			if (hir->constructPacket) {
				if (hir->packetDataCurrent-hir->packetData + (Ptr)(inEntry[1]) > (Ptr)hir->packetDataMaxLength) {
					errprint("data entry - immediate data length too big %ld", inEntry[1]);
					err = paramErr;
					goto bail;
				}
				memcpy(hir->packetDataCurrent, inEntry+2, inEntry[1]);
				hir->packetDataCurrent += inEntry[1];
			}
			break;

		case kHintTrackDataSource_Sample:
			H_ATOM_PRINT(("dataSource=sample\n"));
			trackRefIndex = (SInt8)inEntry[1];
			H_ATOM_PRINT(("trackRefIndex=\"%d\"\n", trackRefIndex));
			length = EndianU16_BtoN(*((UInt16*)(inEntry+2)));
			H_ATOM_PRINT(("length=\"%d\"\n", length));
			sampleNum = EndianU32_BtoN(*((UInt32*)(inEntry+4)));
			H_ATOM_PRINT(("sampleNum=\"%ld\"\n", sampleNum));
			offset = EndianU32_BtoN(*((UInt32*)(inEntry+8)));
			H_ATOM_PRINT(("offset=\"%ld\"\n", offset));
			temp16 = EndianU16_BtoN(*((UInt16*)(inEntry+12)));
			H_ATOM_PRINT(("blockSize=\"%d\"\n", temp16));			
			//@@@ don't check this for .mov files
			if ((temp16 != 0) && (temp16 != 1)) {
				warnprint("Warning: data entry - blocksize should be 0 or 1");
			}
			temp16 = EndianU16_BtoN(*((UInt16*)(inEntry+14)));
			H_ATOM_PRINT(("blockSamples=\"%d\"\n", temp16));			
			//@@@ don't check this for .mov files
			if ((temp16 != 0) && (temp16 != 1)) {
				warnprint("Warning: data entry - blockSamples should be 0 or 1");
			}
			
			//@@@ handle blocksize, blocksamples != 0
			if ((trackRefIndex == (SInt8)kSelfTrackRefIndex) && (sampleNum == hir->hintSampleNum)) {
				if (offset+length > hir->hintSampleLength) {
					errprint("[1] data entry - offset(%d) + length(%d) > samplelength (%d)\n", offset, length, hir->hintSampleLength);
					err = paramErr;
					goto bail;	
				}

				if (hir->constructPacket) {
					if (hir->packetDataCurrent-hir->packetData + (Ptr)length > (Ptr)hir->packetDataMaxLength) {
						errprint("data entry - packet data too big %ld\n", hir->packetDataCurrent-hir->packetData + length);
						err = paramErr;
						goto bail;
					}
					memcpy(hir->packetDataCurrent, hir->hintSampleData + offset, length);
					hir->packetDataCurrent += length;
				}
			} else {
				TrackInfoRec		*thisTIR = ( (trackRefIndex == (SInt8)kSelfTrackRefIndex) ? hir->tir : hir->originalMediaTIR );
			
				if (trackRefIndex != (SInt8)kSelfTrackRefIndex) {
				 	if (trackRefIndex != 0) {
						errprint("data entry - trackRefIndex (%ld) should be 0", trackRefIndex);
						err = paramErr;
						goto bail;
					
					}
					//@@@ warning here about missing tir?
				}

				if (thisTIR == NULL) {
					errprint("data entry -can't find trackinfo for referenced trackid %ld\n", hir->tir->hintRefTrackID);
					err = paramErr;
					goto bail;
				}

				if(!vg.dashSegment)
				{
					BAILIFERR( err = get_track_sample(thisTIR, sampleNum, &sampleData, &sampleDataLength, NULL) );
					if (offset+length >sampleDataLength) {
						errprint("[2] data entry - offset(%d) + length(%d) > samplelength (%d)\n", offset, length, sampleDataLength);
						err = paramErr;
						goto bail;	
					}
				}
				
				if (hir->constructPacket) {
					if (hir->packetDataCurrent-hir->packetData + (Ptr)length > (Ptr)hir->packetDataMaxLength) {
						errprint("data entry - packet data too big %ld\n", hir->packetDataCurrent-hir->packetData + length);
						err = paramErr;
						goto bail;
					}
					memcpy(hir->packetDataCurrent, sampleData + offset, length);
					hir->packetDataCurrent += length;
				}
			}
			break;

		case kHintTrackDataSource_SampleDescription:
			H_ATOM_PRINT(("dataSource=sampleDescription\n"));
			trackRefIndex = (SInt8)inEntry[1];
			H_ATOM_PRINT(("trackRefIndex=\"%d\"\n", trackRefIndex));
			temp16 = EndianU16_BtoN(*((UInt16*)(inEntry+2)));
			H_ATOM_PRINT(("length=\"%d\"\n", temp16));
			temp32 = EndianU32_BtoN(*((UInt32*)(inEntry+4)));
			H_ATOM_PRINT(("sampleDescriptionIndex=\"%ld\"\n", temp32));
			temp32 = EndianU32_BtoN(*((UInt32*)(inEntry+8)));
			H_ATOM_PRINT(("offset=\"%ld\"\n", temp32));
			temp32 = EndianU32_BtoN(*((UInt32*)(inEntry+12)));
			H_ATOM_PRINT(("reserved=\"%ld\"\n", temp32));
			if (temp32 != 0) {
				warnprint("Warning: reserved in sample desc data entry %ld != 0\n", temp32);
			}
			if (hir->constructPacket) {
				errprint("can't get sample descriptions yet\n");
				err = paramErr;
				goto bail;
//@@@ retreive sample description and print it
			}
			break;
		default:
			H_ATOM_PRINT(("dataSource=unknown=\"%ld\"\n", dataSource));
			H_ATOM_PRINT_HEXDATA((char*)inEntry+1, kHintDataTableEntrySize-1);
			warnprint("Warning: unknown datasource in data entry\n");
			break;
	
	}

bail:
	if (sampleData != NULL) {
		free( sampleData );
	}
	if (err != noErr) {
		hir->packetConstructedOK = false;
	}
	return err;
}

#pragma mark -

//==========================================================================================
static OSErr Validate_rfc3016_Payload( char *inPayload, UInt32 inLength, void *inRefCon )
{
	OSErr			err = noErr;
	HintInfoRec		*hir = (HintInfoRec*)inRefCon;
	unsigned char	startCodeTag;
	Boolean			doPrinting = (hir->printSamples && hir->printPayloadContents);
	BitBuffer		bb;

	BitBuffer_Init(&bb, (UInt8*)inPayload, inLength);
	if (BitBuffer_IsVideoStartCode(&bb)) {
		BAILIFERR( err = BitBuffer_GetVideoStartCode(&bb, &startCodeTag) );
		switch (startCodeTag) {
			case kMPEG4StartCode_VOS:
			case kMPEG4StartCode_GOV:
			case kMPEG4StartCode_VOP:
			//case kMPEG4StartCode_VP:
				// these are valid starting headers for the payload
				break;
				
			default:
				errprint("UnknownStartCode=%ld\n", startCodeTag);
				break;
		}
		if (hir->printPayloadContents) {
			H_ATOM_PRINT_INCR(("<payload>\n"));
			switch (startCodeTag) {
				case kMPEG4StartCode_VOS:
					H_ATOM_PRINT(("VisualObjectSequenceHeader\n"));
					break;
				case kMPEG4StartCode_GOV:
					H_ATOM_PRINT(("GroupOfVOPHeader\n"));
					break;
				case kMPEG4StartCode_VOP:
					H_ATOM_PRINT(("VideoObjectPlaneHeader\n"));
					break;
				//case kMPEG4StartCode_VP:
					// these are valid starting headers for the payload
					break;
			}

			
			atomprinthexdata(inPayload+4, inLength-4);

			H_ATOM_PRINT_DECR(("</payload>\n"));
		
		}
	
	} else {
		if (hir->printPayloadContents) {
			H_ATOM_PRINT_INCR(("<payload>\n"));
				H_ATOM_PRINT_HEXDATA(inPayload, inLength);
			H_ATOM_PRINT_DECR(("</payload>\n"));
		}
	}

//@@@ check sdp params
	
bail:
	return err;
}


//==========================================================================================
static OSErr Validate_Generic_MPEG4_Audio_Payload( char *inPayload, UInt32 inLength, void *inRefCon )
{
	OSErr		err = noErr;
	HintInfoRec		*hir = (HintInfoRec*)inRefCon;
	UInt32		temp32;
	UInt32		numHeaders;
	UInt16		auLength = 0;
	char		*headerCurrent;
	char		*auCurrent;
	char		*auMax;
	UInt16		i;
	UInt16		index;
	Boolean		doPrinting = (hir->printSamples && hir->printPayloadContents);
	

	if (!hir->genericPayloadParamsOK) {
		goto bail;
	}


	auMax = inPayload + inLength;
	headerCurrent = inPayload;
	temp32 = EndianU16_BtoN(*((UInt16*)headerCurrent));
	numHeaders = temp32 / hir->bytesPerHeader;

	if ((numHeaders % kBitsPerByte) != 0) {
		errprint("audio payload-au header length not a multiple of 8: %ld\n", temp32);
		err = noCanDoErr;
		goto bail;
	}

	numHeaders /= kBitsPerByte;
	headerCurrent += sizeof(UInt16);
	
	auCurrent = headerCurrent + (hir->bytesPerHeader * numHeaders);
	if (auCurrent > inPayload + inLength) {
		errprint("num au headers %ld is too long for pkt length\n", temp32);
		err = outOfDataErr;
		goto bail;
	}
	
	if ((hir->bytesPerHeader < 1) || (hir->bytesPerHeader > 2)) {
		errprint("bytesPerHeader=%ld unsupported\n", hir->bytesPerHeader);
		err = noCanDoErr;
		goto bail;
	}

	if (hir->printPayloadContents) {
		H_ATOM_PRINT_INCR(("<payload>\n"));
			H_ATOM_PRINT(("numHeaders=\"%ld\"\n", numHeaders));
	}

	for (i=0; i<numHeaders; ++i) {
		if (hir->bytesPerHeader == 1) {
			auLength = (UInt8) headerCurrent[0];
			++headerCurrent;
		} else if (hir->bytesPerHeader == 2) {
			auLength = EndianU16_BtoN(*((UInt16*)headerCurrent));
			headerCurrent += sizeof(UInt16);
		}
		index = auLength & hir->indexMask;
		auLength = auLength >> hir->numIndexBits;

		if ((auCurrent + auLength > auMax) && (numHeaders > 1)) {
			errprint("aulength %ld too big-overflows payload\n", auLength);
			err = outOfDataErr;
			break;
		}
		if (hir->printPayloadContents) {
			H_ATOM_PRINT_INCR(("<au=\"%ld\" length=\"%ld\" index=\"%ld\">\n", i, auLength, index));
				H_ATOM_PRINT_HEXDATA(auCurrent, auLength);
			H_ATOM_PRINT_DECR(("</au>\n"));
			auCurrent += auLength;
		}
	}

	if (hir->printPayloadContents) {
		H_ATOM_PRINT_DECR(("</payload>\n"));
	}


bail:
	return err;
}

//==========================================================================================

static char* nalNames[] = {
		"Unspec", "Non-IDR Slice", "DataPtn A Slice", "DataPtn B Slice", 
		"DataPtn C Slice", "IDR Slice", "SEI", "SPS", 
		"PPS", "AUDelim", "EOSeq", "EOStrm", 
		"Filler", "SPS Extn", NULL, NULL, 
		NULL, NULL, NULL, "Aux Slice", 
		NULL, NULL, NULL, NULL,
		"STAP_A", "STAP_B", "MTAP16", "MTAP24", 
		"FU_A", "FU_B", NULL, NULL };

static void Validate_NAL_Byte( UInt8 nalByte, UInt32 nalSize, Boolean doPrinting )
{
	char* nalName;
	
	nalName = nalNames[nalByte & 0x1F];
	if (nalName==NULL) {
		errprint("Reserved NAL Type %d used\n",nalByte & 0x1f);
		nalName = "Resvd";
	}

	H_ATOM_PRINT_INCR(("<NALUnit type=\"%s(%d)\", NRI=%d, size=%d>\n", nalName, nalByte & 0x1F, (nalByte>>5) & 3, nalSize));
	
	if (nalByte & 0x80) {
		errprint("NAL Unit F bit is not zero, NAL Byte 0x%x (NAL Type %d -- %s)\n", nalByte, nalByte & 0x1F, nalName);
	}
	
}

static OSErr Validate_H264_Payload( char *inPayload, UInt32 inLength, void *inRefCon )
{
	OSErr			err = noErr;
	HintInfoRec		*hir = (HintInfoRec*)inRefCon;
	UInt8			nalByte, fuHdr;
	UInt16			nalSize, don, donB, donD;
	Boolean			doPrinting = (hir->printSamples && hir->printPayloadContents);
	UInt8*			headerCurrent;
	UInt8*			headerLimit;
	UInt32			tsOffset;
	char*			nalName;
	
	nalByte = inPayload[0];
	Validate_NAL_Byte( nalByte, inLength, doPrinting );
	nalByte = nalByte & 0x1F;
	
	headerCurrent = (UInt8*) inPayload;
	headerLimit = headerCurrent + inLength;
	headerCurrent++;
	nalSize = (UInt16)inLength;
	
	switch (nalByte) {
		case kNAL_STAP_A:
		case kNAL_STAP_B:
			if (nalByte==kNAL_STAP_B) {
				don = EndianU16_BtoN(*((UInt16*)headerCurrent));
				H_ATOM_PRINT(("DON %d",don));
				headerCurrent += 2;
			}
			while (headerCurrent < headerLimit) {
				nalSize = EndianU16_BtoN(*((UInt16*)headerCurrent));
				headerCurrent += 2;
				Validate_NAL_Byte( headerCurrent[0], nalSize, doPrinting );
				if (doPrinting) atomprinthexdata((char*) headerCurrent, nalSize);
				H_ATOM_PRINT_DECR(("</NALUnit>\n"));
				headerCurrent += nalSize;
			}
			if (headerCurrent > headerLimit) errprint("NAL Unit overflowed %d\n",headerCurrent-headerLimit);
			break;
			
		case kNAL_MTAP16:
		case kNAL_MTAP24:
			donB = EndianU16_BtoN(*((UInt16*)headerCurrent));
			H_ATOM_PRINT(("DONB %d",donB));
			headerCurrent += 2;
			while (headerCurrent < headerLimit) {
				nalSize = EndianU16_BtoN(*((UInt16*)headerCurrent));
				headerCurrent += 2;
				
				donD = *((UInt8*)headerCurrent);
				H_ATOM_PRINT(("DOND %d",donD));
				headerCurrent += 1;
				
				if (nalByte==kNAL_MTAP16) {
					tsOffset = EndianU16_BtoN(*((UInt16*)headerCurrent));
					headerCurrent += 2;
				} else {
					tsOffset = EndianU24_BtoN(*((UInt32*)headerCurrent));
					headerCurrent += 3;
				}
				H_ATOM_PRINT(("TSOffset %d",nalSize));	

				Validate_NAL_Byte( headerCurrent[0], nalSize, doPrinting );
				if (doPrinting) atomprinthexdata((char*) headerCurrent, nalSize);
				H_ATOM_PRINT_DECR(("</NALUnit>\n"));
				headerCurrent += nalSize;
			}
			if (headerCurrent > headerLimit) errprint("NAL Unit overflowed %d\n",headerCurrent-headerLimit);
			break;

		case kNAL_FU_A:
		case kNAL_FU_B:
			fuHdr = *((UInt8*)headerCurrent);
			headerCurrent += 1;
			nalName = nalNames[fuHdr & 0x1F];
			if (nalName==NULL) {
				errprint("Reserved NAL Type %d used\n",nalByte & 0x1f);
				nalName = "Resvd";
			}

			H_ATOM_PRINT(("<FU Header S=%d,E=%d,R=%d, NALtype=\"%s(%d)\" />\n",
				(fuHdr>>7)&1,(fuHdr>>6)&1,(fuHdr>>5)&1,nalName,fuHdr & 0x1F));
			nalSize = (UInt16)(inLength - 2);
			
			if (nalByte == kNAL_FU_B) {
				don = EndianU16_BtoN(*((UInt16*)headerCurrent));
				H_ATOM_PRINT(("DON %d",don));
				headerCurrent += 2;
				nalSize -= 2;
				
				if (((fuHdr>>7)&1) != 1) errprint("FU_B should always have S=1\n");
			}
			H_ATOM_PRINT_INCR(("<NALUnit_fragment>\n"));
			if (doPrinting) atomprinthexdata((char*) headerCurrent, nalSize);
			H_ATOM_PRINT_DECR(("</NALUnit_fragment>\n"));
			break;	
					
		default:
			--headerCurrent;
			if (doPrinting) atomprinthexdata((char*) headerCurrent, nalSize);
			break;	
	}
	H_ATOM_PRINT_DECR(("</NALUnit>\n"));

//@@@ check sdp params
	
	return err;
}

#pragma mark -
//==========================================================================================
#define kMaxShortSDPLineLength		255

//==========================================================================================

OSErr Validate_Movie_SDP( char *inSDP )
{


	OSErr		err = noErr;
	char		tag;
	char		*current;
	char		*lineEnd;
	char		*sdpLine = NULL;
	char		shortSDPLine[kMaxShortSDPLineLength+1];
	Ptr			longSDPLineP = NULL;
	
	current = inSDP;
	
	do  {
		if (current[0] == '\0') {
			break;
		}
		lineEnd = SDP_Find_Line_End(current);
		// check that the line ends in CRLF
		Validate_SDP_Line_Ending(NULL, lineEnd);
		if (lineEnd > current) {
			tag = 0;
			SDP_Get_Tag(&current, &tag);

			// copy the line and null terminate it
			// if it's too long, we have to allocate a ptr instead
			if (lineEnd - current < kMaxShortSDPLineLength) {
				sdpLine = shortSDPLine;
			} else {
				BAILIFNIL( longSDPLineP = (Ptr)malloc(lineEnd-current+1), allocFailedErr );
				sdpLine = longSDPLineP;
			}
			memcpy(sdpLine, current, lineEnd-current);
			sdpLine[lineEnd-current] = '\0';
			
			switch (tag) {
				case 'a':
					Validate_SDP_Attribute_Line(NULL, sdpLine);
					break;
					
				case 'b':
					break;
					
				case 0:
					// we didn't find a tag-warning msg already printed in SDP_Get_Tag
					break;
				default:
					warnprint("Warning: unknown sdp tag '%c'\n", tag);
					break;
			}
		}
		current = SDP_Skip_Line_Ending_Chars(lineEnd);
		
	} while (current[0] != '\0');
	
	// ----- do any ending validation here
	
bail:
	return err;
}

#pragma mark -
//==========================================================================================

static OSErr Validate_hint_udta_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr			err = noErr;
	OSErr atomerr = noErr;
	UInt64 minOffset, maxOffset;
	SInt32 cnt;
	atomOffsetEntry *list;
	
	minOffset = aoe->offset + aoe->atomStartSize;
	maxOffset = aoe->offset + aoe->size - aoe->atomStartSize;
	
	BAILIFERR( FindAtomOffsets( aoe, minOffset, maxOffset, &cnt, &list ) );

	// Process 'hnti' atoms
	atomerr = ValidateAtomOfType( 'hnti', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_hnti_Atom, cnt, list, refcon );
	if (!err) err = atomerr;

	// Process 'hinf' atoms
	atomerr = ValidateAtomOfType( 'hinf', kTypeAtomFlagCanHaveAtMostOne, 
		Validate_hinf_Atom, cnt, list, refcon );
	if (!err) err = atomerr;

bail:
	return err;
}

//==========================================================================================

static OSErr Validate_hinf_Atom( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(aoe)
	OSErr			err = noErr;
	
	atomprintnotab(">\n"); 

	return err;
}

//==========================================================================================
static OSErr Validate_hnti_Atom( atomOffsetEntry *aoe, void *refcon )
{
	OSErr			err = noErr;
	HintInfoRec		*hir = (HintInfoRec*)refcon;
	char			*current;
	
	OSType			atomType;
	UInt32			atomLength;
	
	Ptr				hntiDataP = NULL;
	Ptr				sdpDataP = NULL;
	UInt64			temp64;
	
	Boolean			doPrinting = hir->printSamples;
	
	atomprintnotab(">\n"); 

	BAILIFNIL( hntiDataP = (Ptr)malloc((UInt32)aoe->size), allocFailedErr );

	BAILIFERR( GetFileData(aoe, hntiDataP, aoe->offset + aoe->atomStartSize, aoe->size - aoe->atomStartSize, &temp64) );
	

//@@@ this only processes the first atom
// we should cycle around
	current = hntiDataP;
	atomLength = EndianU32_BtoN(*((UInt32*)current));
	current += sizeof(UInt32);
	atomType = EndianU32_BtoN(*((UInt32*)current));
	current += sizeof(UInt32);
	
	if (atomLength > aoe->size - aoe->atomStartSize) {
		errprint("atomlength %ld in 'hnti' user data too big\n", atomLength);
		err = outOfDataErr;
		goto bail;
	}

	if (atomType == 'sdp ') {
		// we found the sdp data
		// make a copy and null terminate it
		atomLength -= 8; // subtract the atomlength/type fields from the length 
		BAILIFNIL( sdpDataP = (Ptr)malloc(atomLength+1), allocFailedErr );
		memcpy(sdpDataP, current, atomLength);
		sdpDataP[atomLength] = '\0';
		H_ATOM_PRINT_INCR(("<sdp>\n"));
//@@@ fix printing of sdp
// want it like hint dump
//			H_ATOM_PRINT(("%s", sdpDataP));
			H_ATOM_PRINT(("%.100s\n", sdpDataP));
		H_ATOM_PRINT_DECR(("<sdp>\n"));
		
//@@@ check that the sdp doesn't contain anything but chars and CRLF (no '\0' either)
		BAILIFERR( Validate_Track_SDP(hir, sdpDataP) );
	} else {
		errprint("no sdp atom in hnti user data\n");
		err = outOfDataErr;
		goto bail;
	}


bail:
	if (hntiDataP != NULL) {
		free(hntiDataP);
	}
	if (sdpDataP != NULL) {
		free(sdpDataP);
	}
	return err;
}

//==========================================================================================
static OSErr Validate_Track_SDP( HintInfoRec *hir, char *inSDP )
{
	OSErr		err = noErr;
	char		tag;
	char		*current;
	char		*lineEnd;
	char		*sdpLine = NULL;
	char		shortSDPLine[kMaxShortSDPLineLength+1];
	Ptr			longSDPLineP = NULL;
	
	current = inSDP;
	
	do  {
		if (current[0] == '\0') {
			break;
		}
		lineEnd = SDP_Find_Line_End(current);
		// check that the line ends in CRLF
		Validate_SDP_Line_Ending(hir, lineEnd);
		if (lineEnd > current) {
			tag = 0;
			SDP_Get_Tag(&current, &tag);

			// copy the line and null terminate it
			// if it's too long, we have to allocate a ptr instead
			if (lineEnd - current < kMaxShortSDPLineLength) {
				sdpLine = shortSDPLine;
			} else {
				BAILIFNIL( longSDPLineP = (Ptr)malloc(lineEnd-current+1), allocFailedErr );
				sdpLine = longSDPLineP;
			}
			memcpy(sdpLine, current, lineEnd-current);
			sdpLine[lineEnd-current] = '\0';
			
			switch (tag) {
				case 'm':
					Validate_SDP_Media_Line(hir, sdpLine);
					break;
					
				case 'a':
					Validate_SDP_Attribute_Line(hir, sdpLine);
					break;
					
				case 'b':
					break;
				
				case 0:
					// we didn't find a tag-warning msg already printed in SDP_Get_Tag
					break;
				default:
					warnprint("Warning: unknown sdp tag '%c'", tag);
					break;
			}
		}
		current = SDP_Skip_Line_Ending_Chars(lineEnd);
		
	} while (current[0] != '\0');
	
	// ----- do ending validation here
	// must have a payload name	
	if (hir->sdpInfo.payloadNum != 0) {
		if (compare_nocase(kMPEG4Generic_PayloadName, hir->sdpInfo.payloadName)) {
			hir->sdpInfo.payloadValidateProc = Validate_Generic_MPEG4_Audio_Payload;
		} else if (compare_nocase(kMPEG43016_PayloadName, hir->sdpInfo.payloadName)) {	
			hir->sdpInfo.payloadValidateProc = Validate_rfc3016_Payload;
		} else if (compare_nocase(kH264_PayloadName, hir->sdpInfo.payloadName)) {	
			hir->sdpInfo.payloadValidateProc = Validate_H264_Payload;
		} else {
			if (hir->sdpInfo.payloadName[0] == '\0') {
				errprint("missing rtpmap line for payload num %d", hir->sdpInfo.payloadNum);
			} else {
				warnprint("Warning: can't handle this payload '%s'", hir->sdpInfo.payloadName);
			}
		}	
	}
	
bail:
	return err;
}

//==========================================================================================
static OSErr Validate_SDP_Media_Line( HintInfoRec *hir, char *inLine )
{
//@@@ check mediatype is ok
//@@@ check port=0
	OSErr		err = noErr;
	char		*current = inLine;
	char		*next;
	SInt32		temp32;
	Boolean		foundNum;
	
	// m=<mediatype> <port> RTP/AVP <payloadNum>
	// m=audio 0 RTP/AVP 96
	
	// ----- media type
	next = strchr(current, kSpaceChar);
	if (next == NULL) {
		// line contains only the media type?
		warnprint("Warning: sdp media line contains only 1 word");
		goto bail;
	}
	current = next+1;	// skip over the space char
	
	// ----- port
	next = strchr(current, kSpaceChar);
	if (next == NULL) {
		// line contains only the media type?
		warnprint("Warning: sdp media line contains only 1 word");
		goto bail;
	}
	
	temp32 = Chars_To_Num(current, next, &foundNum);
	if (!foundNum) {
//@@@ err here
	}
	if (!is_in_range(temp32, 0, 0x0000ffff)) {
		warnprint("Warning: port out of range %ld\n",temp32);
	}
	if (temp32 != 0) {
		warnprint("port should be 0 but is %ld\n", temp32);
	}
	current = next+1;	
	
	// ----- RTP/AVP
	next = strchr(current, kSpaceChar);
	if (next == NULL) {
		// line contains only the media type?
		warnprint("Warning: sdp media line is too short");
		goto bail;
	}
	//@@@ check this is RTP/AVP
	
	current = next+1;
	
	
	// ----- payload number
	next = current + strlen(current);
	temp32 = Chars_To_Num(current, next, &foundNum);
	if (!foundNum) {
		errprint("payloadnum slot is not a number");
		err = paramErr;
		goto bail;
	}
	if (!is_in_range(temp32, 0, 0x000000ff)) {
		errprint("payloadnum out of range %ld\n",temp32);
		err = paramErr;
		goto bail;
	}
	hir->sdpInfo.payloadNum = (unsigned char)temp32;
	


bail:
	return err;
}

//==========================================================================================
static OSErr Validate_SDP_Attribute_Line( HintInfoRec *hir, char *inLine )
{
#define kMaxSDPTagLength		63
	OSErr		err = noErr;
	char		tagString[kMaxSDPTagLength+1];
	char		*value;


	// a=<tag>:<value>
	value = strchr(inLine, ':');
	if (value == 0) {
		errprint("attribute line - can't find tag");
		BAILIFERRSET( err = paramErr );
	}
	memcpy(tagString, inLine, value-inLine);
	tagString[value-inLine] = '\0';
	value++;	// skip over the :
	
	if (strcmp("fmtp", tagString) == 0) {
		err = Validate_fmtp_attribute(hir, value);
	} else if (strcmp("rtpmap", tagString) == 0) {
		err = Validate_rtpmap_attribute(hir, value);
	} else if (strcmp("mpeg4-iod", tagString) == 0) {
		err = Validate_iod_attribute(hir, value);
	} else if (strcmp("isma-compliance", tagString) == 0) {
		err = Validate_isma_attribute(hir, value);
	} else {
//@@@ warning	
	}
	
	
		
	
bail:
	return err;
}

//==========================================================================================
static OSErr Validate_fmtp_attribute( HintInfoRec *hir, char *inValue)
{
	OSErr		err = noErr;
	char		*current = inValue;
	char		*next;
	SInt32		temp32;
	Boolean		foundNum;
	Ptr			param = NULL;
	Ptr			paramValue = NULL;
	Boolean		doPrinting = hir->printSamples;

	// a=fmtp:<payloadnum> <param>=<value>[;<param>=<value]
	// a=fmtp:96 profile-level-i=1;config=00010304544392	
	
	// ----- payload num
	next = strchr(current, kSpaceChar);
	if (next == NULL) {
		errprint("bad fmtp attribute-no payloadnum");
		BAILIFERRSET( err = paramErr );
	}
	temp32 = Chars_To_Num(current, next, &foundNum);
	if (!foundNum) {
		errprint("fmtp attribute-payloadnum not a num");
		BAILIFERRSET( err = paramErr );
	}
	if (!is_in_range(temp32, 96, 255)) {
//@@@ diff err msg for static numbers
		errprint("fmtp attribute-payloadnum out of range");
		BAILIFERRSET( err = paramErr );
	}
	current = next+1;

	if (temp32 != hir->sdpInfo.payloadNum) {
		goto bail;	
	}

#define kMPEG4GenericParam_ConstantSize			"constantsize"
#define kMPEG4GenericParam_SizeLength			"sizelength"
#define kMPEG4GenericParam_IndexLength			"indexlength"
#define kMPEG4GenericParam_IndexDeltaLength		"indexdeltalength"

	// ----- pick off params
	while (get_next_fmtp_param(&current, &param, &paramValue)) {
		if (compare_nocase("config", param)) {
		
		} else if (compare_nocase(kMPEG4GenericParam_Mode, param)) {
			if (compare_nocase(kMPEG4GenericModeName_CELPCBR, paramValue)) {
				hir->genericPayloadMode = kMPEG4GenericMode_CELPCBR;
			} else if (compare_nocase(kMPEG4GenericModeName_CELPVBR, paramValue)) {
				hir->genericPayloadMode = kMPEG4GenericMode_CELPVBR;
			} else if (compare_nocase(kMPEG4GenericModeName_AACLowBitRate, paramValue)) {
				hir->genericPayloadMode = kMPEG4GenericMode_AACLowBitRate;
			} else if (compare_nocase(kMPEG4GenericModeName_AACHighBitRate, paramValue)) {
				hir->genericPayloadMode = kMPEG4GenericMode_AACHighBitRate;
			} else {
				errprint("fmtp mode unknown '%s'", paramValue);			
			}
			if (hir->genericPayloadMode != 0) {
				strcpy(hir->sdpInfo.modeName, paramValue);
			}
		} else if (compare_nocase(kMPEG4GenericParam_ConstantSize, param)) {
			temp32 = Chars_To_Num(paramValue, paramValue + strlen(paramValue), &foundNum);
			if (foundNum) {
				hir->constantSize = (UInt16)temp32;
			} else {
				errprint("fmtp constantsize param not a number '%s'", paramValue);
			}
		} else if (compare_nocase(kMPEG4GenericParam_SizeLength, param)) {
			temp32 = Chars_To_Num(paramValue, paramValue + strlen(paramValue), &foundNum);
			if (foundNum) {
				hir->numLengthBits = (UInt16)temp32;
			} else {
				errprint("fmtp sizelength param not a number '%s'", paramValue);
			}
		} else if (compare_nocase(kMPEG4GenericParam_IndexLength, param)) {
			temp32 = Chars_To_Num(paramValue, paramValue + strlen(paramValue), &foundNum);
			if (foundNum) {
				hir->numIndexBits = (UInt16)temp32;
			} else {
				errprint("fmtp indexlength param not a number '%s'", paramValue);
			}
	
		} else if (compare_nocase(kMPEG4GenericParam_IndexDeltaLength, param)) {
			temp32 = Chars_To_Num(paramValue, paramValue + strlen(paramValue), &foundNum);
			if (foundNum) {
				hir->numIndexDeltaBits = (UInt16)temp32;
			} else {
				errprint("fmtp indexdeltalength param not a number '%s'", paramValue);
			}	
		} else if (compare_nocase("sprop-parameter-sets", param)) {
			/* parameter sets, each base64, separated by commas */
			UInt8 next = 0;
			char* begin;
			char* paramEnd;
			char* paramnext;
			UInt32 nalSize, base64Size;
			char* nalDataP;
			BitBuffer bb;
			
			paramnext = paramValue;
			
			while (paramnext) {
				begin = paramnext;
				
				paramEnd = strchr(begin, ',');
				if (paramEnd == NULL) {
					paramEnd = begin + strlen(begin);
					paramnext = NULL;
				} else paramnext = paramEnd + 1;
				
				base64Size = paramEnd - begin;
				nalSize = base64Size;	// more than enough, will be adjusted down
				BAILIFNIL( nalDataP = (char *)malloc(nalSize), allocFailedErr );
				err = Base64DecodeToBuffer(begin, &base64Size, nalDataP, &nalSize);
				if (err) {
					errprint("bad parameter set-bad base64 encoding");
					goto bail;
				}
				BitBuffer_Init(&bb, (UInt8*) nalDataP, nalSize );
				
				/* need to fiddle the printing flags here? */
				Validate_NAL_Unit( &bb, 0, nalSize );
				free(nalDataP);
			}
		} else if ((compare_nocase(kH264_PayloadName, hir->sdpInfo.payloadName)) &&
					compare_nocase("profile-level-id", param)) {
			/* for AVC, a three byte value, hex representation */
			UInt8 profile, flags, level;
			temp32 = Chars_To_hexNum(paramValue, paramValue + strlen(paramValue), &foundNum);
			if (foundNum) {
				profile = (temp32 >> 16) & 0xFF;
				flags   = (temp32 >> 8 ) & 0xFF;
				level   = (temp32	  ) & 0xFF;
				if ((profile != 66) && (profile != 77) && (profile != 88))
					errprint("AVC Profile %d in SDP profile-level-ID is not baseline(66) main(77), or extended(88)\n",profile);
				if (flags & 0x1F)
					errprint("AVC flags in SDP profile-level-ID other than constraint_set, set 0x%x\n",flags);
				
				H_ATOM_PRINT(("AVC Profile in SDP profile-level-ID %d, flags 0x%x, level %d\n",profile,flags,level));
			} else {
				errprint("fmtp profile-level-id param not a hex number '%s'", paramValue);
			}
		}

		if (param != NULL) {
			free( param );
			param = NULL;
		}		
		if (paramValue != NULL) {
			free( paramValue );
			paramValue = NULL;
		}		
	}


	// check that the params match the mode
	if (compare_nocase(kMPEG4Generic_PayloadName, hir->sdpInfo.payloadName)) {
		switch (hir->genericPayloadMode) {
			case kMPEG4GenericMode_CELPCBR:
				if (hir->constantSize <=0) {
					errprint("constantsize (%ld) param out of range", hir->constantSize);
					err = paramErr;
				}
				break;

			case kMPEG4GenericMode_CELPVBR:
				if (hir->numLengthBits != kMPEG4Generic_CELPVBR_SizeLengthDefault) {
					errprint("sizelength (%ld) != default (%ld)", hir->numLengthBits, kMPEG4Generic_CELPVBR_SizeLengthDefault);
					err = paramErr;
				}
				if (hir->numIndexBits != kMPEG4Generic_CELPVBR_IndexLengthDefault) {
					errprint("indexlength (%ld) != default (%ld)", hir->numIndexBits, kMPEG4Generic_CELPVBR_IndexLengthDefault);
					err = paramErr;
				}
				if (hir->numIndexBits != kMPEG4Generic_CELPVBR_IndexDeltaLengthDefault) {
					errprint("indexdeltalength (%ld) != default (%ld)", hir->numIndexBits, kMPEG4Generic_CELPVBR_IndexDeltaLengthDefault);
					err = paramErr;
				}
				hir->bytesPerHeader = kMPEGGeneric_CELPVBR_OverheadBytesPerFrame;
				hir->indexMask = kMPEGGeneric_CELPVBR_IndexMask;
				hir->maxFrameLength = kMPEG4Generic_CELPVBR_MaxFrameLength;
				if (err == noErr) {
					hir->genericPayloadParamsOK = true;
				}
				break;
				
			case kMPEG4GenericMode_AACLowBitRate:
				if (hir->numLengthBits != kMPEG4Generic_AACLBR_SizeLengthDefault) {
					errprint("sizelength (%ld) != default (%ld)", hir->numLengthBits, kMPEG4Generic_AACLBR_SizeLengthDefault);
					err = paramErr;
				}
				if (hir->numIndexBits != kMPEG4Generic_AACLBR_IndexLengthDefault) {
					errprint("indexlength (%ld) != default (%ld)", hir->numIndexBits, kMPEG4Generic_AACLBR_IndexLengthDefault);
					err = paramErr;
				}
				if (hir->numIndexBits != kMPEG4Generic_AACLBR_IndexDeltaLengthDefault) {
					errprint("indexdeltalength (%ld) != default (%ld)", hir->numIndexBits, kMPEG4Generic_AACLBR_IndexDeltaLengthDefault);
					err = paramErr;
				}
				hir->bytesPerHeader = kMPEGGeneric_AACLBR_OverheadBytesPerFrame;
				hir->indexMask = kMPEGGeneric_AACLBR_IndexMask;
				hir->maxFrameLength = kMPEG4Generic_AACLBR_MaxFrameLength;
				if (err == noErr) {
					hir->genericPayloadParamsOK = true;
				}
				break;

			case kMPEG4GenericMode_AACHighBitRate:
				if (hir->numLengthBits != kMPEG4Generic_AACHBR_SizeLengthDefault) {
					errprint("sizelength (%ld) != default (%ld)", hir->numLengthBits, kMPEG4Generic_AACHBR_SizeLengthDefault);
					err = paramErr;
				}
				if (hir->numIndexBits != kMPEG4Generic_AACHBR_IndexLengthDefault) {
					errprint("indexlength (%ld) != default (%ld)", hir->numIndexBits, kMPEG4Generic_AACHBR_IndexLengthDefault);
					err = paramErr;
				}
				if (hir->numIndexBits != kMPEG4Generic_AACHBR_IndexDeltaLengthDefault) {
					errprint("indexdeltalength (%ld) != default (%ld)", hir->numIndexBits, kMPEG4Generic_AACHBR_IndexDeltaLengthDefault);
					err = paramErr;
				}
				hir->bytesPerHeader = kMPEGGeneric_AACHBR_OverheadBytesPerFrame;
				hir->indexMask = kMPEGGeneric_AACHBR_IndexMask;
				hir->maxFrameLength = kMPEG4Generic_AACHBR_MaxFrameLength;
				if (err == noErr) {
					hir->genericPayloadParamsOK = true;
				}
				break;

			default:
				errprint("fmtp - missing or unknown mode param\n");
				break;
		
		}
	} 
	
bail:
	if (param != NULL) {
		free( param );
		param = NULL;
	}		
	if (paramValue != NULL) {
		free( paramValue );
		paramValue = NULL;
	}		
	return err;
}

//==========================================================================================
static OSErr Validate_rtpmap_attribute( HintInfoRec *hir, char *inValue)
{
	OSErr		err = noErr;
	char		*current = inValue;
	char		*next;
	SInt32		temp32;
	Boolean		foundNum;

	// a=rtpmap:<payloadnum> <payloadname>[/<timescale>[/<numchannels>]]
	// a=rtpmap:96 mpeg4-generic/44100/1
	// a=rtpmap:96 mp4v
	// a=rtpmap:96 H264/90000
	
	// ----- payload num
	next = strchr(current, kSpaceChar);
	if (next == NULL) {
		errprint("bad rtpmap attribute");
		BAILIFERRSET( err = paramErr );
	}
	temp32 = Chars_To_Num(current, next, &foundNum);
	if (!foundNum) {
		errprint("rtpmap attribute-payloadnum not a num");
		BAILIFERRSET( err = paramErr );
	}
	if (!is_in_range(temp32, 96, 255)) {
//@@@ diff err msg for static numbers
		errprint("rtpmap attribute-payloadnum out of range");
		BAILIFERRSET( err = paramErr );
	}
	current = next+1;
	
	// ----- payload name
	next = strchr(current, '/');
	if (next == 0) {	// the rest is optional
		next = current + strlen(current);
	}
	if (next-current > kMaxSDPPayloadNameLength) {
		warnprint("Warning: payload name is unusually long");
	}
	
	if (temp32 == hir->sdpInfo.payloadNum) {
		// this is the payload name we're using
		temp32 = next-current;
		if (temp32 > kMaxSDPPayloadNameLength) {
			temp32 = kMaxSDPPayloadNameLength	;
		}	
		memcpy(hir->sdpInfo.payloadName, current, temp32);
		hir->sdpInfo.payloadName[temp32] = '\0';


	}
	
bail:
	return err;
}

static OSErr Validate_iod_attribute( HintInfoRec *hir, char *inValue)
{
#pragma unused(hir)
	OSErr		err = noErr;
	char		*current = inValue;
	char		*next;
	char		*end;
	const char*	urlStart = "data:application/mpeg4-iod;base64,";
	UInt32		base64Size;
	Ptr			iodDataP = NULL;
	UInt32		iodSize;
	
	// a=mpeg4-iod: "data:application/mpeg4-iod;base64,..."
	
	end = &inValue[strlen(inValue)];
	next = current;
	while (*next == ' ') {
		next++;
	}
	if (*next == '\0') {
		errprint("bad iod attribute-no url\n");
		BAILIFERRSET( err = paramErr );
	}
	if (*next != '\"' || *(end - 1) != '\"') {
		errprint("bad iod attribute-double quotes missing\n");
		BAILIFERRSET( err = paramErr );
	}
	next++;
	if (strncmp(next, urlStart, strlen(urlStart)) != 0) {
		errprint("bad iod attribute-bad url\n");
		BAILIFERRSET( err = paramErr );
	}
	next = (char *)((UInt64)next + (UInt64)strlen(urlStart));
	
	base64Size = end - next - 1;
	iodSize = base64Size;	// more than enough, will be adjusted down
	BAILIFNIL( iodDataP = (Ptr)malloc(iodSize), allocFailedErr );
	err = Base64DecodeToBuffer(next, &base64Size, iodDataP, &iodSize);
	if (err) {
		errprint("bad iod attribute-bad base64 encoding");
		goto bail;
	}

	BAILIFERR( Validate_iods_OD_Bits( iodDataP, iodSize, false ) );
	
bail:
	return err;
}

static OSErr Validate_isma_attribute( HintInfoRec *hir, char *inValue)
{
#pragma unused(hir)
	OSErr		err = noErr;
	SInt32	profile, i;
	float	lowest, authored;
	
	i = sscanf(inValue,"%u,%f,%f",&profile,&lowest,&authored);
	if (i<3) errprint("Bad ISMA compliance attribute %s\n",inValue);
	else {
		if ((profile<0) || (profile>4)) errprint("Bad ISMA compliance profile value %s\n",inValue);
		if ((lowest != 1.0) && (lowest != 2.0)) errprint("Bad ISMA compliance lowest spec value %s\n",inValue);
		if ((authored != 1.0) && (authored != 2.0)) errprint("Bad ISMA compliance authored spec value %s\n",inValue);
	}
	
	// a=isma-compliance:1,1.0,1
		
	//if (strcmp(inValue, "1,1.0,1") != 0) {
	//	errprint("bad isma compliance attribute");
	//	BAILIFERRSET( err = paramErr );
	//}
	
	return err;
}

#pragma mark <Base 64 utilities>

#define kBase64BadLookupChar		64
#define kBase64PadLookupChar		65

static const char sBase64DecodingTable [256] = {
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 62, 64, 64, 64, 63,
	52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 64, 64, 64, 65, 64, 64,
	64,  0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 10, 11, 12, 13, 14,
	15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 64, 64, 64, 64, 64,
	64, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
	41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64,
	64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64, 64
};

#define kBase64PadChar					'='
#define kBase64LineBreakChar			'\n'

#define kBase64DecodedCharsPerGroup		3
#define kBase64EncodedCharsPerGroup		4

// ---------------------------------------------------------------------------
//		Base64DecodeToBuffer
// ---------------------------------------------------------------------------

OSErr Base64DecodeToBuffer(const char *inData, UInt32 *ioEncodedLength, char *outDecodedData, UInt32 *ioDecodedDataLength)
{
	OSErr		err = noErr;

	SInt32		encodedDataProcessed = 0;
	const char	*current;
	const char	*end;
	char		lookupChar;
	char		*decodedCurrent;
	char		tempBuffer[4];
	int			countInTempBuffer = 0;
	int			tempNumToDecode;
	int			foundPadChar = 0;
	
	if ((ioEncodedLength == NULL)  ||  (ioDecodedDataLength == NULL))  {
		BAILIFERRSET( err = paramErr );
	}

	// we will always decode 4 bytes into 3 characters
	current = inData;
	end = inData + *ioEncodedLength;
	decodedCurrent = outDecodedData;

	// process encoded bytes - notice that if the encoded chars
	// (illegal base64 chars are not counted in this - e.g. '\n')
	// are not a mulple of 4, we will only process in multiples of 4
	// and leave any leftovers behind
	while (current < end)  {

		if ((lookupChar = sBase64DecodingTable[(unsigned char)(*current)]) != kBase64BadLookupChar)  {
			tempBuffer[countInTempBuffer] = lookupChar;
			++countInTempBuffer;
			if ((lookupChar == kBase64PadLookupChar)  &&  (foundPadChar == 0))  {
				foundPadChar = countInTempBuffer;
			}
			if (countInTempBuffer == kBase64EncodedCharsPerGroup)  {
				// we have 4 encoded characters now - decode it to 3
				// we have to account for padding characters
				switch (foundPadChar)  {
					case 1:
						// the whole thing is padding characters??
						// shouldn't happen but you never know
						tempNumToDecode = 0;
						break;
					case 2:
					case 3:
						tempNumToDecode = 1;
						break;
					case 4:
						tempNumToDecode = 2;
						break;
					default:
						tempNumToDecode = 3;
						break;
				}


				if ((UInt32)(decodedCurrent - outDecodedData + tempNumToDecode) > *ioDecodedDataLength)  {
					// done processing because we ran out of room in the decode buffer
					break;
				}
				
				if (tempNumToDecode > 0)  {
					decodedCurrent[0] = (tempBuffer [0] << 2) | ((tempBuffer [1] & 0x30) >> 4);
				}
				if (tempNumToDecode > 1)  {
					decodedCurrent [1] = ((tempBuffer [1] & 0x0F) << 4) | ((tempBuffer [2] & 0x3C) >> 2);
				}
				if (tempNumToDecode > 2)  {
					decodedCurrent [2] = ((tempBuffer [2] & 0x03) << 6) | (tempBuffer [3] & 0x3F);
				}
				countInTempBuffer = 0;
				decodedCurrent += tempNumToDecode;
				encodedDataProcessed = (current+1 - inData);
				foundPadChar = 0;
			}
		}  else  {
			// if the buffer is full of just illegal characters (e.g. \n), we will deal with it
			if (countInTempBuffer == 0)  {
				encodedDataProcessed = (current+1 - inData);
			}
		}
		++current;
	}
	*ioEncodedLength = encodedDataProcessed;
	*ioDecodedDataLength = (decodedCurrent - outDecodedData);
	
bail:
	return err;
}

#pragma mark -

//==========================================================================================
static OSErr SDP_Get_Tag( char **ioCurrent, char *outTag )
{
#define kSDPTagSeparatorChar		'='
#define kSDPTagLength				2

	OSErr		err = noErr;
	char		*current = *ioCurrent;

	if ((current[0] != '\0') && (current[1] != '\0')) {
		if (current[1] != kSDPTagSeparatorChar) {
			errprint("sdp line doesn't start with <char>= '%.20s'", current);
			err = paramErr;
		} else {
			*outTag = current[0];
			*ioCurrent += kSDPTagLength;
		}
	} else {
		errprint("sdp line doesn't start with <char>= '%.20s'", current);
		err = paramErr;
	}		
	return err;
}

//==========================================================================================
static char *SDP_Find_Line_End( char *inCurrent )
{
	char	*current = inCurrent;
	char	*lineEnd = 0;
	
	while ( (current[0] != '\0')	&&
			(current[0] != CR)		&&
			(current[0] != LF) ) {
		++current;
	}
	lineEnd = current;
	return lineEnd;
}

//==========================================================================================
static char *SDP_Skip_Line_Ending_Chars( char *inLineEnd )
{
	char	*nextLineStart = 0;
	
	// go past one CRLF, or just one CR or LF
	if ((inLineEnd[0] == CR) && (inLineEnd[1] == LF)) {
		nextLineStart = inLineEnd + 2;
	} else {
		if ((inLineEnd[0] == CR) || (inLineEnd[0] == LF)) {
			nextLineStart = inLineEnd + 1;
		} else if (inLineEnd[0] == '\0') {
			nextLineStart = inLineEnd;
		}
	}
	return nextLineStart;
}

//==========================================================================================
static void Validate_SDP_Line_Ending( HintInfoRec *hir, char *inEndOfLine )
{
#pragma unused(hir)
	// it should be CRLF
	if ((inEndOfLine[0] == CR)  &&
		(inEndOfLine[1] == LF)) {
		// this is the correct line ending
	} else {
		errprint("sdp line doesn't end in CRLF");
	}
}

#pragma mark -

//==========================================================================================
static SInt32 Chars_To_Num( char *inCharsStart, char *inCharsEnd, Boolean *outFoundNum )
{
#define kMaxCharsToNumLength	11
	char		tempString[kMaxCharsToNumLength+1];	
	int			length;
	SInt32		value = 0;
	Boolean		found = false;
	
	if (!is_num(inCharsStart, inCharsEnd)) {
		errprint("Chars_To_Num - not a number");
		goto bail;
	}
	
	// the number can't be > a long
	length = inCharsEnd - inCharsStart;
	if (length > kMaxCharsToNumLength) {
		errprint("Chars_To_Num - too many chars");
		goto bail;
	}

	memcpy(tempString, inCharsStart, length);
	tempString[length] = '\0';

	sscanf(tempString, "%d", &value);
	found = true;

bail:
	if (outFoundNum != NULL) {
		*outFoundNum = found;
	}
	return value;
}

static SInt32 Chars_To_hexNum( char *inCharsStart, char *inCharsEnd, Boolean *outFoundNum )
{
	char		tempString[kMaxCharsToNumLength+1];	
	int			length;
	SInt32		value = 0;
	Boolean		found = false;
	
	if (!is_hexnum(inCharsStart, inCharsEnd)) {
		errprint("Chars_To_hexNum - not a hex number");
		goto bail;
	}
	
	// the number can't be > a long
	length = inCharsEnd - inCharsStart;
	if (length > kMaxCharsToNumLength) {
		errprint("Chars_To_Num - too many chars");
		goto bail;
	}

	memcpy(tempString, inCharsStart, length);
	tempString[length] = '\0';

	sscanf(tempString, "%x", &value);
	found = true;

bail:
	if (outFoundNum != NULL) {
		*outFoundNum = found;
	}
	return value;
}


//==========================================================================================
static Boolean is_num( char *inCharsStart, char *inCharsEnd)
{
	char		*current = inCharsStart;
	Boolean		isNum = true;
	
	if (inCharsEnd <= inCharsStart) {
		isNum = false;
		goto bail;
	}

	while (current < inCharsEnd) {
		if ((current[0] < '0') || (current[0] > '9')) {
			isNum = false;
			break;
		}
		++current;
	}
bail:
	return isNum;
}

static Boolean is_hexnum( char *inCharsStart, char *inCharsEnd)
{
	char		*current = inCharsStart;
	Boolean		isNum = true;
	
	if (inCharsEnd <= inCharsStart) {
		isNum = false;
		goto bail;
	}

	while (current < inCharsEnd) {
		if ( ((current[0] < '0') || (current[0] > '9')) &&
			 ((current[0] < 'a') || (current[0] > 'f')) &&
			 ((current[0] < 'A') || (current[0] > 'F')) ) {
			isNum = false;
			break;
		}
		++current;
	}
bail:
	return isNum;
}


//==========================================================================================
static Boolean is_in_range( SInt32 inNum, SInt32 inMin, SInt32 inMax)
{
	Boolean		inRange;

	if ((inNum < inMin) || (inNum > inMax)) {
		inRange = false;
	} else {
		inRange = true;
	}
	return inRange;
}

//==========================================================================================
static Boolean compare_nocase(const char *s1, const char *s2)
{
	Boolean		matches = false;
	const char	*c1 = s1;
	const char	*c2 = s2;

	while ((c1[0] != '\0') && (c2[0] != '\0')) {
		if ( tolower(*c1) != tolower(*c2)) {
			matches = false;
			goto bail;
		}
		++c1;
		++c2;
	}
	if ((c1[0] == '\0') && (c2[0] == '\0')) {
		matches = true;
	}
bail:
	return matches;
}

//==========================================================================================
static Boolean get_next_fmtp_param(char **inLine, char **outTagString, char **outValueString)
{
	OSErr		err = noErr;
	char		*begin = *inLine;
	char		*paramEnd;
	char		*tagEnd;
	Boolean		found = false;
	Ptr			tag = NULL;
	Ptr			value = NULL;
	SInt32		length;
	

	// strip off leading spaces?
	// cisco puts spaces between params
	while (begin[0] == ' ') {
		++begin;
	}

	if (begin[0] == '\0') {
		goto bail;
	}

	paramEnd = strchr(begin, ';');
	if (paramEnd == NULL) {
		paramEnd = begin + strlen(begin);
	}
	
	tagEnd = strchr(begin, '=');
	if (tagEnd == NULL) {
		tagEnd = begin + strlen(begin);
	}
	if (tagEnd > paramEnd) {
		tagEnd = paramEnd;
	}
	
	length = tagEnd - begin;
	BAILIFNIL( tag = (Ptr)malloc(length + 1), allocFailedErr );
	memcpy(tag, begin, length);
	tag[length] = '\0';
	
	if (paramEnd != tagEnd) {
		length = paramEnd - tagEnd - 1;	// subtract the =
		BAILIFNIL( value = (Ptr)malloc(length + 1), allocFailedErr );
		memcpy(value, tagEnd+1, length);
		value[length] = '\0';
	} else {
		BAILIFNIL( value = (Ptr)malloc(1), allocFailedErr );
		value[0] = '\0';
	}
	found = true;
	*inLine = paramEnd+1;	
	
bail:
	if (err != noErr) {
		if (tag != NULL) {
			free(tag);
		}
		if (value != NULL) {
			free(value);
		}
		if (outTagString != NULL) {
			*outTagString = NULL;
		}
		if (outValueString != NULL) {
			*outValueString = NULL;
		}
	} else {
		if (outTagString != NULL) {
			*outTagString = tag;
		}
		if (outValueString != NULL) {
			*outValueString = value;
		}
	}
	return found;
}

#pragma mark -

//==========================================================================================
static OSErr get_original_track_info(UInt32 inRefTrackID, TrackInfoRec **outTIR)
{
	OSErr			err = noErr;	
	SInt32			i;
	MovieInfoRec	*mir = vg.mir;
	
	if (mir == NULL) {
		err = paramErr;
		goto bail;
	}
		
	for (i=0; i<mir->numTIRs; ++i) {
		if (mir->tirList[i].trackID == inRefTrackID) {
			*outTIR = &(mir->tirList[i]);
			break;
		}		
	}
	if (!(*outTIR)) {
		errprint("Can't find media track ID %d from hint track\n",inRefTrackID);
		/* for (i=0; i<mir->numTIRs; ++i) warnprint("   Candidate track ID %d\n",mir->tirList[i].trackID); */
	}

bail:
	return err;
}

//==========================================================================================
static OSErr get_track_sample(TrackInfoRec *tir, UInt32 inSampleNum, Ptr *dataOut, UInt32 *sizeOut, UInt32 *sampleDescriptionIndexOut)
{
	OSErr		err = noErr;
	UInt64		sampleOffset;

	if (tir != NULL) {
		BAILIFERR( err = GetSampleOffsetSize( tir, inSampleNum, &sampleOffset, sizeOut, sampleDescriptionIndexOut ) );
		BAILIFNIL( *dataOut = (Ptr)malloc(*sizeOut), allocFailedErr );
		BAILIFERR( err = GetFileData( vg.fileaoe, *dataOut, sampleOffset, *sizeOut, nil ) );
	}
bail:
	return err;
}


