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



OSErr BitBuffer_Init(BitBuffer *bb, UInt8 *p, UInt32 length)
{
	int err = noErr;
	
	if (length > 0x0fffffff) {
		err = paramErr;
		goto bail;
	}

	bb->ptr = (UInt8*)p;
	bb->length = length;
	
	bb->cptr = (UInt8*)p;
	bb->cbyte = *bb->cptr;
	bb->curbits = 8;
	
	bb->bits_left = length * 8;
	
	bb->prevent_emulation   = 0;
	bb->emulation_position  = (bb->cbyte == 0 ? 1 : 0);

bail:
	return err;
}



OSErr GetBytes(BitBuffer *bb, UInt32 nBytes, UInt8 *p)
{
	OSErr err = noErr;
	unsigned int i;
	
	for (i = 0; i < nBytes; i++) {
		*p++ = (UInt8)GetBits(bb, 8, &err);
		if (err) break;
	}
	
	return err;
}

OSErr SkipBytes(BitBuffer *bb, UInt32 nBytes)
{
	OSErr err = noErr;
	unsigned int i;
	
	for (i = 0; i < nBytes; i++) {
		GetBits(bb, 8, &err);
		if (err) break;
	}
	
	return err;
}


UInt32 NumBytesLeft(BitBuffer *bb)
{
	UInt32 numBytes;
	
	numBytes = ((bb->bits_left + 7) / 8);
	return numBytes;
}

UInt32 GetBits(BitBuffer *bb, UInt32 nBits, OSErr *errout)
{
	OSErr err = noErr;
	int myBits;
	int myValue = 0; 
	int myResidualBits;
	int leftToRead;
	
	if (nBits==0) goto bail;
	
	if (nBits > bb->bits_left || 0 == bb->bits_left) {
		err = outOfDataErr;
		goto bail;
	}
	
    if (bb->curbits <= 0) {
        bb->cbyte = *++bb->cptr;
        bb->curbits = 8;
		
		if (bb->prevent_emulation != 0) {
			if ((bb->emulation_position >= 2) && (bb->cbyte == 3)) {
				bb->cbyte = *++bb->cptr;
				bb->bits_left -= 8;
				bb->emulation_position = 0;
				if (nBits>bb->bits_left) {
					err = outOfDataErr;
					goto bail;
				}
			}
			else if (bb->cbyte == 0) bb->emulation_position += 1;
			else bb->emulation_position = 0;
		}
	}
	
	if (nBits > bb->curbits)
		myBits = bb->curbits;
	else
		myBits = nBits;
		
	myValue = (bb->cbyte>>(8-myBits));
	myResidualBits = bb->curbits - myBits;
	leftToRead = nBits - myBits;
	bb->bits_left -= myBits;
	
	bb->curbits = myResidualBits;
	bb->cbyte = ((bb->cbyte) << myBits) & 0xff;

	if (leftToRead > 0) {
		UInt32 newBits;
		newBits = GetBits(bb, leftToRead, &err);
		myValue = (myValue<<leftToRead) | newBits;
	}
	
bail:	
	if (errout) *errout = err;
	return myValue;
}


UInt32 PeekBits(BitBuffer *bb, UInt32 nBits, OSErr *errout)
{
	OSErr err = noErr;
	BitBuffer curbb = *bb;
	int myBits;
	int myValue = 0;
	int myResidualBits;
	int leftToRead;
	
	if (nBits == 0) goto bail;
	
	if (nBits>bb->bits_left) {
		err = outOfDataErr;
		goto bail;
	}

	if (bb->curbits <= 0) {
		bb->cbyte = *++bb->cptr;
		bb->curbits = 8;
	}
	
	if (nBits > bb->curbits)
		myBits = bb->curbits;
	else
		myBits = nBits;
		
	myValue = (bb->cbyte>>(8-myBits));
	myResidualBits = bb->curbits - myBits;
	leftToRead = nBits - myBits;
	
	bb->curbits = myResidualBits;
	bb->cbyte = ((bb->cbyte) << myBits) & 0xff;
	
	if (leftToRead > 0) {
		UInt32 newBits;
		newBits = PeekBits(bb, leftToRead, &err);
		myValue = (myValue<<leftToRead) | newBits;
	}
	
bail:
	*bb = curbb;
	if (errout) *errout = err;
	return myValue;
}




OSErr GetDescriptorTagAndSize(BitBuffer *bb, UInt32 *tagOut, UInt32 *sizeOut)
{
	OSErr err = noErr;
	UInt32 tag = 0;
	UInt32 size = 0;
	UInt32 collectSize = 0;
	
	tag = GetBits(bb, 8, &err); if (err) goto bail;

	size = 0;
	collectSize = 0x80;
	while (collectSize & 0x80) {
		collectSize = GetBits(bb, 8, &err); if (err) goto bail;
		size <<= 7;
		size |= (collectSize & 0x7f);
	}

bail:
	*tagOut = tag;
	*sizeOut = size;
	
	return err;
}

#define kMPEG4_Video_StartCodeLength	32
#define kMPEG4_Video_StartCodeMask		0xFFFFFF00
#define kMPEG4_Video_StartCodeValue		0x00000100
#define kMPEG4_Video_StartCodeTagMask	0x000000FF


Boolean BitBuffer_IsVideoStartCode(BitBuffer *bb)
{
	OSErr			err = noErr;
	Boolean			isStartCode = false;
	UInt32			temp32;
	
	temp32 = PeekBits(bb, kMPEG4_Video_StartCodeLength, &err);
	if (err != noErr ) goto bail;
	if ((temp32 & kMPEG4_Video_StartCodeMask) == kMPEG4_Video_StartCodeValue) {
		isStartCode = true;
	}
bail:
	return isStartCode;
}

OSErr BitBuffer_GetVideoStartCode(BitBuffer *bb, unsigned char *outStartCode)
{
	OSErr			err = noErr;
	Boolean			isStartCode = false;
	UInt32			temp32;
	
	temp32 = GetBits(bb, kMPEG4_Video_StartCodeLength, &err);
	if (err != noErr ) goto bail;
	if ((temp32 & kMPEG4_Video_StartCodeMask) == kMPEG4_Video_StartCodeValue) {
		*outStartCode = temp32 & kMPEG4_Video_StartCodeTagMask;
	}
bail:
	return err;
}

UInt32 read_golomb_uev(BitBuffer *bb, OSErr *errout)
{
	OSErr err = noErr;
	
	UInt32 power = 1;
	UInt32 value = 0;
	UInt32 leading = 0;
	UInt32 nbits = 0;
	
	leading = GetBits(bb, 1, &err);  if (err) goto bail;
	
	while (leading == 0) { 
		power = power << 1;
		nbits++;
		leading = GetBits(bb, 1, &err);  if (err) goto bail;
	}
	
	if (nbits > 0) {
		value = GetBits( bb, nbits, &err); if (err) goto bail;
	}
	
bail:
	if (errout) *errout = err;
	return (power - 1 + value);
}

SInt32 read_golomb_sev(BitBuffer *bb, OSErr *errout)
{
	OSErr err = noErr;
	UInt32 uev;
	SInt32 val;
	
	uev = read_golomb_uev( bb, &err ); if (err) goto bail;
	if (uev & 1)
		val = (uev + 1)/2;
		else
		val = -1 * (uev/2);
bail:
	if (errout) *errout = err;
	return val;
}

UInt32 strip_trailing_zero_bits(BitBuffer *bb, OSErr *errout)
{
	OSErr err = noErr;
	UInt8 bit_check = 1;
	UInt8* byte_ptr;
	UInt32 trailing = 0, bits;
	
	bits = bb->bits_left;
	byte_ptr = bb->cptr;
	
	bits -= bb->curbits;
	byte_ptr++;
	
	byte_ptr += (bits / 8);
	bits = bits % 8;
	if (bits == 0) { bits = 8; byte_ptr--; }
	
	bit_check = 1 << (8- bits);
	
	while (( *byte_ptr & bit_check ) == 0) {
		trailing++;
		if (bit_check == 0x80) {
			if ((--byte_ptr) < bb->cptr) { 
				err = outOfDataErr;
				goto bail;
			}
			bit_check = 1;
		} else bit_check = bit_check << 1;
		bb->bits_left -= 1;
	}
	bail:
	if (errout != NULL) *errout = err;
	return trailing;
}