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

UInt64 getAdjustedFileOffset(UInt64 offset64)
{
	UInt64 adjustedOffset = offset64;

	if (vg.numOffsetEntries > 0)
	{
		unsigned int index = 0;
		for (index = 0; offset64 > vg.offsetEntries[index].offset; index++)
		{
			adjustedOffset -= vg.offsetEntries[index].sizeRemoved;
		}
		if (index > 0)
			if (offset64 <= (vg.offsetEntries[index - 1].offset + vg.offsetEntries[index - 1].sizeRemoved-1))
			{
			fprintf(stderr, "Program error! Requested information is at offset %lu, which is in a removed region at index %d (offset: %lu, removed size: %lu), exiting!", offset64, index, vg.offsetEntries[index - 1].offset, vg.offsetEntries[index - 1].sizeRemoved);
			exit(-1);
			}
	}

	return adjustedOffset;
}

UInt64 inflateOffset(UInt64 offset64)
{
	UInt64 adjustedOffset = offset64;

	if (vg.numOffsetEntries > 0)
	{
		unsigned int index = 0;

		for (index = 0; index < vg.numOffsetEntries && adjustedOffset >= vg.offsetEntries[index].offset; index++)
		{
			adjustedOffset += vg.offsetEntries[index].sizeRemoved;
		}
	}

	return adjustedOffset;
}

int PeekFileData( atomOffsetEntry *aoe, void *dataP, UInt64 offset64, UInt64 size64 )
{
#pragma unused(aoe)
	int err = 0;
	fpos_t f_pos;
		
	if (offset64 > 0x7FFFFFFFL) {
		fprintf(stderr,"sorry - can't handle file offsets > 31-bits\n");
		err = noCanDoErr;
		goto bail;
	}
	
	err = fgetpos(vg.inFile, &f_pos);
	if (err) goto bail;
	
	err = GetFileData(aoe, dataP, offset64, size64, NULL);
	if (err) goto bail;

	err = fsetpos(vg.inFile, &f_pos);
	if (err) goto bail;

bail:
	return err;
}


//==========================================================================================

int GetFileData( atomOffsetEntry *aoe, void *dataP, UInt64 offset64, UInt64 size64, UInt64 *newoffset64 )
{
#pragma unused(aoe)
	int err = 0;
	SInt32 amtRead = 0;
	UInt64 size = size64;
	
	if (offset64 > 0x7FFFFFFFL) {
		fprintf(stderr,"sorry - can't handle file offsets > 31-bits\n");
		err = noCanDoErr;
		goto bail;
	}
	
	err = fseek(vg.inFile, (long)getAdjustedFileOffset(offset64), SEEK_SET);
	if (err) goto bail;
	
	amtRead = fread( dataP, 1, (size_t)size, vg.inFile );
	if ((UInt64)amtRead != size) {
		err = outOfDataErr;
		goto bail;
	}

	if (newoffset64) *newoffset64 = offset64 + size;

bail:
	return err;
}


int GetFileDataN64( atomOffsetEntry *aoe, void *dataP, UInt64 offset64, UInt64 *newoffset64 )
{
	int err;
	UInt64 temp;

	err = GetFileData( aoe, &temp, offset64, sizeof(temp), newoffset64 );
	if (!err) {
		*(UInt64*)dataP = EndianU64_BtoN(temp);
	}
	
	return err;
}

int GetFileDataN32( atomOffsetEntry *aoe, void *dataP, UInt64 offset64, UInt64 *newoffset64 )
{
	int err;
	UInt32 temp;

	err = GetFileData( aoe, &temp, offset64, sizeof(temp), newoffset64 );
	if (!err) {
		*(UInt32*)dataP = EndianU32_BtoN(temp);
	}
	
	return err;
}

int GetFileDataN16( atomOffsetEntry *aoe, void *dataP, UInt64 offset64, UInt64 *newoffset64 )
{
	int err;
	UInt16 temp;

	err = GetFileData( aoe, &temp, offset64, sizeof(temp), newoffset64 );
	if (!err) {
		*(UInt16*)dataP = EndianU16_BtoN(temp);
	}
	
	return err;
}

int GetFileCString( atomOffsetEntry *aoe, char **strP, UInt64 offset64, UInt64 maxSize64, UInt64 *newoffset64 )
{
	int err = 0;
	char str[8192];
	char *sp;
	UInt64 scnt = 0;
	
	*strP = nil;
	sp = &str[0]; *sp = '\0';
	while (scnt < maxSize64) {
		BAILIFERR( GetFileData( aoe, sp, offset64, 1, &offset64 ) );
		scnt++;
		if (*sp == 0) break; else sp++;
	}
	
	if ((*sp) != 0) {
		warnprint( "\nWARNING: C string not terminated\n" );
		*(++sp) = '\0'; scnt++;
	}

	BAILIFNIL( *strP = (char *)malloc(scnt), allocFailedErr );
	memcpy(*strP, &str[0], scnt);
	
bail:
	if (newoffset64) *newoffset64 = offset64;
	return err;
}

int GetFileUTFString( atomOffsetEntry *aoe, char **strP, UInt64 offset64, UInt64 maxSize64, UInt64 *newoffset64 )
{
	int err = 0;
	char* noticeP;
	int textIsUTF16 = false;		// otherwise, UTF-8
	int utf16TextIsLittleEndian = false;
	
	noticeP = *strP;
	BAILIFERR( GetFileData( aoe, noticeP, offset64, maxSize64, NULL ) );
		
	// check string type
	if (maxSize64 > 2) {
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
		char * pASCII = nil;
		UInt16 * pUTF16 = nil;
		UInt64 numChars = (maxSize64 - 2)/2;
		
		if (numChars == 0) { // no actual text
			errprint("UTF-16 text has BOM but no terminator\n");
		}
		else {
			UInt64 ix;
			
			// �� clf -- The right solution is probably to generate "\uNNNN" for Unicode characters not in the range 0-0x7f. That
			// will require the array be 5 times as large in the worst case.
			pASCII= noticeP; *pASCII = 0;
			
			pUTF16 = (UInt16*) (noticeP + 2);
			*newoffset64 = offset64 + 2;
			
			for (ix=0; ix < numChars-1; ix++, pUTF16++) {
				UInt8 thechar;
				UInt16 utf16Char = utf16TextIsLittleEndian ? EndianU16_LtoN(*pUTF16) : EndianU16_BtoN(*pUTF16);
				
				thechar	= (utf16Char & 0xff80) ? ((char) '\?') : (char)(utf16Char & 0x7f);
				*pASCII = thechar;
				pASCII++; *pASCII = 0;
				*newoffset64 += 2;
				if (thechar==0) break; 
			}
		}
	}
	else return GetFileCString(aoe, strP, offset64, maxSize64, newoffset64);
bail:
	return err;
}

int GetFileBitStreamDataToEndOfAtom( atomOffsetEntry *aoe, Ptr *bsDataPout, UInt32 *bsSizeout, UInt64 offset64, UInt64 *newoffset64 )
{
	int err = noErr;
	UInt32 bsSize = 0;
	Ptr bsDataP = nil;

	bsSize = (UInt32)(aoe->size - (offset64 - aoe->offset));
	BAILIFNIL( bsDataP = (Ptr)calloc(bsSize + bitParsingSlop, 1), allocFailedErr );
	BAILIFERR( GetFileData( aoe, bsDataP, offset64, bsSize, newoffset64 ) );

bail:
	if (bsDataPout) *bsDataPout = bsDataP;
	if (bsSizeout) *bsSizeout = bsSize;
	
	return err;
}

//=================================================================

int GetSampleOffsetSize( TrackInfoRec *tir, UInt32 sampleNum, UInt64 *offsetOut, UInt32 *sizeOut, UInt32 *sampleDescriptionIndexOut )
{
	int err = noErr;
	UInt32 stsCnt;
	UInt32 i;
	UInt32 sampleCnt = 1;
	UInt32 samplesPerChunk;
	UInt32 size = 0;
	UInt32 chunkNum;
	UInt32 sampleDelta;
	UInt64 offset = 0;
	UInt32 sampleDescriptionIndex = 0;
	
	if (sampleNum > tir->sampleSizeEntryCnt) {
		err = paramErr;
		goto bail;
	}
	 
	for (stsCnt = 1; stsCnt < tir->sampleToChunkEntryCnt; stsCnt++) {
		int numChunks;
		int numSamples;
		
		numChunks = (tir->sampleToChunk[stsCnt + 1].firstChunk - tir->sampleToChunk[stsCnt].firstChunk);
		numSamples = numChunks * tir->sampleToChunk[stsCnt].samplesPerChunk;
		if (sampleNum < (sampleCnt + numSamples)) {
			break;
		}
		sampleCnt += numSamples;
	}
	
	sampleDelta = sampleNum - sampleCnt;
	samplesPerChunk = tir->sampleToChunk[stsCnt].samplesPerChunk;
	chunkNum = tir->sampleToChunk[stsCnt].firstChunk + (sampleDelta / samplesPerChunk);
	sampleDelta %= samplesPerChunk;
	
	sampleCnt += samplesPerChunk * (chunkNum - tir->sampleToChunk[stsCnt].firstChunk);

	offset = tir->chunkOffset[chunkNum].chunkOffset;
	sampleDescriptionIndex = tir->sampleToChunk[stsCnt].sampleDescriptionIndex;
	if (tir->singleSampleSize) {
		size = tir->singleSampleSize;
		offset += sampleDelta * size; 
	} else {
		for (i = sampleCnt; i < sampleCnt + sampleDelta; i++) {
			offset += tir->sampleSize[i].sampleSize;
		}
		size = tir->sampleSize[sampleNum].sampleSize;
	}
	
bail:
	if (sampleDescriptionIndexOut) *sampleDescriptionIndexOut = sampleDescriptionIndex;
	*offsetOut = offset;
	*sizeOut = size;
	return err;
}

int GetChunkOffsetSize( TrackInfoRec *tir, UInt32 chunkNum, UInt64 *offsetOut, UInt32 *sizeOut, UInt32 *sampleDescriptionIndexOut )
{
	int err = noErr;
	UInt32 stsCnt;
	UInt32 i;
	UInt32 sampleCnt = 1;
	UInt32 samplesPerChunk;
	UInt32 size = 0;
	UInt64 offset = 0;
	UInt32 sampleDescriptionIndex = 0;
	
	if (chunkNum > tir->chunkOffsetEntryCnt) {
		err = paramErr;
		goto bail;
	}
	
	for (stsCnt = 1; stsCnt < tir->sampleToChunkEntryCnt; stsCnt++) {
		if (tir->sampleToChunk[stsCnt + 1].firstChunk > chunkNum) {
			break;
		}
		sampleCnt += (tir->sampleToChunk[stsCnt].samplesPerChunk * 
					(tir->sampleToChunk[stsCnt + 1].firstChunk - tir->sampleToChunk[stsCnt].firstChunk));
	}
	
	samplesPerChunk = tir->sampleToChunk[stsCnt].samplesPerChunk;
	sampleCnt += samplesPerChunk * (chunkNum - tir->sampleToChunk[stsCnt].firstChunk);
	sampleDescriptionIndex = tir->sampleToChunk[stsCnt].sampleDescriptionIndex;
	
	offset = tir->chunkOffset[chunkNum].chunkOffset;
	if (tir->singleSampleSize) {
		size = samplesPerChunk * tir->singleSampleSize;
	} else {
		for (i = sampleCnt; i < sampleCnt + samplesPerChunk; i++) {
			size += tir->sampleSize[i].sampleSize;
		}
	}
			
bail:
	if (sampleDescriptionIndexOut) *sampleDescriptionIndexOut = sampleDescriptionIndex;
	*offsetOut = offset;
	*sizeOut = size;
	return err;
}

//==========================================================================================

int GetFileStartCode( atomOffsetEntry *aoe, UInt32 *startCode, UInt64 offset64, UInt64 *newoffset64 )
{
#pragma unused(aoe)
	int err = 0;
	UInt64 curoffset = offset64;
	UInt32 bits = 0;
	
	if (offset64 > 0x7FFFFFFFL) {
		fprintf(stderr,"sorry - can't handle file offsets > 31-bits\n");
		err = noCanDoErr;
		goto bail;
	}
	
	err = fseek(vg.inFile, (long)getAdjustedFileOffset(offset64), SEEK_SET);
	if (err) goto bail;
	
	bits = fgetc( vg.inFile ); curoffset++;
		if (curoffset > aoe->maxOffset) { err = outOfDataErr; goto bail;}
	bits <<= 8; bits |= fgetc( vg.inFile ); curoffset++;
		if (curoffset > aoe->maxOffset) { err = outOfDataErr; goto bail;}
	bits <<= 8; bits |= fgetc( vg.inFile ); curoffset++;
		if (curoffset > aoe->maxOffset) { err = outOfDataErr; goto bail;}
	bits <<= 8; bits |= fgetc( vg.inFile ); curoffset++;
		if (curoffset > aoe->maxOffset) { err = outOfDataErr; goto bail;}
	
	while ((bits & 0xffffff00) != 0x00000100) {
		bits <<= 8; bits |= fgetc( vg.inFile ); curoffset++;
			if (curoffset > aoe->maxOffset) { err = outOfDataErr; goto bail;}
	}
	
	curoffset -= 4;
	
bail:
	if (newoffset64) *newoffset64 = curoffset;
	if (startCode) *startCode = bits;
	return err;
}

//=========  Endian Utilities =========

#if TYPE_LONGLONG && defined(_MSC_VER)
UInt64 Endian64_Swap(UInt64 value)
{
	UInt64 temp = 0;

	temp =  ((((UInt64)value)<<56) & 0xFF00000000000000);
	temp |= ((((UInt64)value)<<40) & 0x00FF000000000000);
	temp |= ((((UInt64)value)<<24) & 0x0000FF0000000000);
	temp |= ((((UInt64)value)<< 8) & 0x000000FF00000000);
	temp |= ((((UInt64)value)>> 8) & 0x00000000FF000000);
	temp |= ((((UInt64)value)>>24) & 0x0000000000FF0000);
	temp |= ((((UInt64)value)>>40) & 0x000000000000FF00);
	temp |= ((((UInt64)value)>>56) & 0x00000000000000FF);
	return temp;
}
#endif

void EndianMatrix_BtoN( MatrixRecord *matrix )
{
	int i,j;

	for (i = 0; i < 3; i++) {
		for (j = 0; j < 3; j++) {
			(*matrix)[i][j] = EndianU32_BtoN((*matrix)[i][j]);
		}
	}
}

void EndianSampleDescriptionHead_BtoN( SampleDescriptionHead *sdhP )
{
	sdhP->size = EndianU32_BtoN(sdhP->size);
	sdhP->sdType = EndianU32_BtoN(sdhP->sdType);
	sdhP->resvd1 = EndianU32_BtoN(sdhP->resvd1);
	sdhP->resvdA = EndianU16_BtoN(sdhP->resvdA);
	sdhP->dataRefIndex = EndianS16_BtoN(sdhP->dataRefIndex);
}

