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


//==========================================================================================
OSErr Validate_dac3_Atom( atomOffsetEntry *aoe, void *refcon)
{
	OSErr err = noErr;
	UInt64 offset;

	UInt8 fscod;
	UInt8 bsid;
	UInt8 bsmod;
	UInt8 acmod;
	UInt8 lfeon;
	UInt8 bit_rate_code;
	UInt8 reserved;
	UInt32 overall = 0;

	offset = aoe->offset + aoe->atomStartSize;

	atomprint("<dac3\n");
	vg.tabcnt++;

	BAILIFERR(GetFileData( aoe, &overall, offset, 3, &offset ));

	fscod = (overall & 0xC00000) >> 22;
	bsid =  (overall & 0x3E0000) >> 17;
	bsmod = (overall & 0x01C000) >> 14;
	acmod = (overall & 0x003800) >> 11;
	lfeon = (overall & 0x000400) >> 10;
	bit_rate_code = (overall & 0x0003E0) >> 5;
	reserved = overall & 0x00001F;

	atomprint("overall=\"%ld\"\n", overall);
	atomprint("fscod=\"%ld\"\n", fscod);
	atomprint("bsid=\"%ld\"\n", bsid);
	atomprint("bsmod=\"%ld\"\n", bsmod);
	atomprint("acmod=\"%ld\"\n", acmod);
	atomprint("lfeon=\"%ld\"\n", lfeon);
	atomprint("bit_rate_code=\"%ld\"\n", bit_rate_code);
	atomprint("reserved=\"%ld\"\n", reserved);

	vg.tabcnt--;

bail:
	atomprint(">\n");
	atomprint("</dac3>\n");
	return err;
}
//==========================================================================================
//ETSI TS 102 366 F.3 Page 196
OSErr Validate_dec3_Atom( atomOffsetEntry *aoe, void *refcon)
{
	OSErr err = noErr;
	UInt64 offset;

	UInt16 first_iter;
	UInt16 data_rate;
	UInt8 num_ind_sub;

	UInt16 second_iter;
	UInt8 fscod;
	UInt8 bsid;
	UInt8 reserved;
	UInt8 asvc;
	UInt8 bsmod;
	UInt8 acmod;
	UInt8 lfeon;

	UInt8 third_iter;
	UInt8 reserved1;
	UInt8 num_dep_sub;

	UInt8 fourth_iter;
	UInt16 chan_loc;
	UInt8 reserved2;

	UInt16 padding;
	UInt64 reserved3;
	int i;

	offset = aoe->offset + aoe->atomStartSize;

	atomprint("<dec3\n");
	vg.tabcnt++;


	BAILIFERR(GetFileData( aoe, &first_iter, offset, sizeof(first_iter), &offset ));
	data_rate = (first_iter & 0xFFF8) >> 3;
	num_ind_sub = (first_iter & 0x0007);
	atomprint("data_rate=\"%d\"\n", data_rate);
	atomprint("num_ind_sub=\"%d\"\n", num_ind_sub);

	for (i=0; i<num_ind_sub; i++) {
		BAILIFERR(GetFileData( aoe, &second_iter, offset, 2, &offset ));
		fscod = (second_iter & 0xC000) >> 14;
		bsid = (second_iter & 0x3E00) >> 9;
		reserved = (second_iter & 0x0100) >> 8;
		asvc = (second_iter & 0x0080) >> 7;
		bsmod = (second_iter & 0x0070) >> 4;
		acmod = (second_iter & 0x000E) >> 1;
		lfeon = (second_iter & 0x0001);

		BAILIFERR(GetFileData( aoe, &third_iter, offset, 1, &offset ));
		reserved1 = (third_iter & 0xE0) >> 5;
		num_dep_sub = (third_iter & 0x1E) >> 1;

		atomprint("fscod_%d=\"%d\"\n", i, fscod);
		atomprint("bsid_%d=\"%d\"\n", i, bsid);
		atomprint("reserved_%d=\"%d\"\n", i, reserved);
		atomprint("asvc_%d=\"%d\"\n", i, asvc);
		atomprint("bsmod_%d=\"%d\"\n", i, bsmod);
		atomprint("acmod_%d=\"%d\"\n", i, acmod);
		atomprint("lfeon_%d=\"%d\"\n", i, lfeon);
		atomprint("reserved1_%d=\"%d\"\n", i, reserved1);
		atomprint("num_dep_sub_%d=\"%d\"\n", i, num_dep_sub);

		if (num_dep_sub > 0) {
			BAILIFERR(GetFileData( aoe, &fourth_iter, offset, 1, &offset ));
			chan_loc = (fourth_iter & 0xFF) + ((third_iter & 0x01) << 8);
			atomprint("chan_loc_%d=\"%d\"\n", i, chan_loc);
		}
		else {
			reserved2 = third_iter & 0x01;
			atomprint("reserved2_%d=\"%d\"\n", i, reserved2);
		}
	}

	padding = (UInt16)(aoe->size - offset);
	if (padding > sizeof (reserved3) ) GOTOBAIL;


	if (padding > 0) {
		BAILIFERR(GetFileData( aoe, &reserved3, offset, padding, &offset ));
		atomprint("reserved3=\"%d\"\n", reserved3);
	}


bail:
	vg.tabcnt--;
	atomprint(">\n");
	atomprint("</dec3>\n");
	return err;
}

//==========================================================================================
//ETSI TS 103 190-2 V1.2.1 (2018-02) E.5 Page 217
OSErr Validate_dac4_Atom( atomOffsetEntry *aoe, void *refcon)
{
	OSErr err = noErr;
	unsigned int size;
	unsigned int type;
	UInt64 offset;

	atomprint("<dac4\n");
	vg.tabcnt++;

	void* bsDataP;
	BitBuffer bb;

	offset = aoe->offset;

	BAILIFERR(GetFileData( aoe, &size, offset, sizeof(size), &offset ));
	size=EndianU32_BtoN (size);

	BAILIFERR(GetFileData( aoe, &type, offset, sizeof(type), &offset ));

	BAILIFNIL( bsDataP = calloc(size - 8 + bitParsingSlop, 1), allocFailedErr );

	BAILIFERR( GetFileData( aoe, bsDataP, offset, size - 8, &offset ) );

	BitBuffer_Init(&bb, (UInt8 *)bsDataP, size - 8);

	BAILIFERR( Validate_ac4_dsi_v1( &bb, refcon));

bail:
	vg.tabcnt--;
	atomprint("</dac4>\n");
	if(err){
		 bailprint("Validate_dac4_Atom", err);
	}
	return err;
}

//==========================================================================================
//ETSI TS 103 190-2 V1.2.1 (2018-02) E.5a Page 217
OSErr Validate_lac4_Atom( atomOffsetEntry *aoe, void *refcon)
{
	OSErr err = noErr;
	unsigned int size;
	unsigned int type;
	UInt64 offset;
	UInt32 bits_counter = 0;
	UInt32  flags;
	UInt32 reserved_shall_be_zero;
	UInt32 num_presentation_labels;

	atomprint("<lac4\n");
	vg.tabcnt++;

	void* bsDataP;
	BitBuffer bb;

	offset = aoe->offset;

	BAILIFERR(GetFileData( aoe, &size, offset, sizeof(size), &offset ));
	size=EndianU32_BtoN (size);

	BAILIFERR(GetFileData( aoe, &type, offset, sizeof(type), &offset ));

	BAILIFNIL( bsDataP = calloc(size - 8 + bitParsingSlop, 1), allocFailedErr );

	BAILIFERR( GetFileData( aoe, bsDataP, offset, size - 8, &offset ) );

	BitBuffer_Init(&bb, (UInt8 *)bsDataP, size - 8);

	flags = GetBits(&bb, 24, &err); if (err) GOTOBAIL;
	atomprint("flags=\"%d\"\n", flags);
	bits_counter += 24;

	reserved_shall_be_zero = GetBits(&bb, 7, &err); if (err) GOTOBAIL;
	atomprint("reserved_shall_be_zero=\"%d\"\n", reserved_shall_be_zero);
	bits_counter += 7;

	num_presentation_labels = GetBits(&bb, 9, &err); if (err) GOTOBAIL;
	atomprint("num_presentation_labels=\"%d\"\n", num_presentation_labels);
	bits_counter += 9;


bail:
	vg.tabcnt--;
	atomprint("</lac4>\n");
	if(err){
		 bailprint("Validate_lac4_Atom", err);
	}
	return err;
}


//==========================================================================================
OSErr Validate_ac4_dsi_v1( BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	UInt64 i;
	UInt32 bits_counter,padding;

	atomprint(">\n");
	atomprint("<ac4_dsi_v1\n");
	vg.tabcnt++;

	bits_counter=0;
	UInt8 ac4_dsi_version; ac4_dsi_version = GetBits(bb, 3, &err); if (err) GOTOBAIL;
	atomprint("ac4_dsi_version=\"%d\"\n", ac4_dsi_version);

	if (ac4_dsi_version != 1)
	{
		errprint("ETSI TS 103 190-2 v1.2.1 Annex E Line 14191: On encoding, its value shall be written equal to 1, but value is %d\n", ac4_dsi_version );
	}

	UInt8 bitstream_version; bitstream_version  = GetBits(bb, 7, &err); if (err) GOTOBAIL;
	atomprint("bitstream_version=\"%d\"\n", bitstream_version);

	UInt8 fs_index; fs_index = GetBits(bb, 1, &err); if (err) GOTOBAIL;
	atomprint("fs_index=\"%d\"\n", fs_index);

	UInt8 frame_rate_index; frame_rate_index = GetBits(bb, 4, &err); if (err) GOTOBAIL;
	atomprint("frame_rate_index=\"%d\"\n", frame_rate_index);

	UInt16 n_presentations; n_presentations= GetBits(bb, 9, &err); if (err) GOTOBAIL;
	atomprint("n_presentations=\"%d\"\n", n_presentations);
	bits_counter+=(UInt32)24;

	if(bitstream_version>1) {
		UInt8 b_program_id; b_program_id= GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_program_id=\"%d\"\n", b_program_id);

		bits_counter+=1;
		if(b_program_id) {
			UInt16 short_program_id; short_program_id = GetBits(bb, 16, &err); if (err) GOTOBAIL;
			atomprint("short_program_id=\"%d\"\n",short_program_id);

			UInt8 b_uuid; b_uuid=GetBits(bb, 1, &err); if (err) GOTOBAIL;
			bits_counter+=17;
			if(b_uuid) {
				UInt16 program_uuid[8];
				for(i=0;i<8;i++) {
					program_uuid[i] = GetBits(bb, 16, &err); if (err) GOTOBAIL;
					atomprint("program_uuid_%ld=\"%d\"\n",i,program_uuid[i]);
				}
				bits_counter+=(16*8);
			}
		}
	}

	BAILIFERR( Validate_ac4_bitrate_dsi( bb, refcon, &bits_counter) );

	padding= (bits_counter %8)?8 - (bits_counter % 8):0;
	(void)GetBits(bb, padding, &err); if (err) GOTOBAIL;

	for(i=0;i<n_presentations;i++) {
		UInt8 presentation_version; presentation_version=GetBits(bb, 8, &err); if (err) GOTOBAIL;
		atomprint("presentation_version_%ld=\"%d\"\n", i, presentation_version);

		UInt32 pres_bytes; pres_bytes=GetBits(bb, 8, &err); if (err) GOTOBAIL;
		atomprint("pres_bytes_%ld=\"%d\"\n", i, pres_bytes);

		UInt8 presentation_bytes; presentation_bytes = 0;

		if(pres_bytes==255)  {
			UInt16 add_pres_bytes; add_pres_bytes=GetBits(bb, 16, &err); if (err) GOTOBAIL;
			atomprint("add_pres_bytes_%ld=\"%d\"\n", i, add_pres_bytes);

			pres_bytes+=add_pres_bytes;
		}
		if(presentation_version==0) {
			BAILIFERR( Validate_ac4_presentation_v0_dsi( bb, refcon, &presentation_bytes, i));
		}
		else  {
			if(presentation_version==1) {
				BAILIFERR( Validate_ac4_presentation_v1_dsi( bb, refcon, pres_bytes, &presentation_bytes, i));
			}
			else  {
				presentation_bytes=0;
			}
		}
		atomprint("presentation_bytes_%ld=\"%d\"\n", i, presentation_bytes);

		UInt16 skip_bytes; skip_bytes= pres_bytes - presentation_bytes;

		for(int j=0;j<skip_bytes;j++) {
			(void)GetBits(bb, 8, &err); if (err) GOTOBAIL;
		}
	}
	bail:
		atomprint(">\n");
		vg.tabcnt--;
		atomprint("</ac4_dsi_v1>\n");
		return err;
}

//==========================================================================================
OSErr Validate_ac4_bitrate_dsi( BitBuffer *bb, void *refcon, UInt32* bits_counter)
{
	OSErr err = noErr;

	vg.tabcnt++;

	UInt8  bit_rate_mode; bit_rate_mode = GetBits(bb, 2,  &err); if (err) GOTOBAIL;
	atomprint("bit_rate_mode=\"%d\"\n", bit_rate_mode);

	UInt32 bit_rate;  bit_rate = GetBits(bb, 32, &err); if (err) GOTOBAIL;
	atomprint("bit_rate=\"%d\"\n", bit_rate);

	UInt32 bit_rate_precision;  bit_rate_precision = GetBits(bb, 32, &err); if (err) GOTOBAIL;
	atomprint("bit_rate_precision=\"%d\"\n", bit_rate_precision);

	*bits_counter=*bits_counter+66;

bail:
	vg.tabcnt--;
	return err;
}

//==========================================================================================
//ETSI TS 103 190-2 V1.2.1 (2018-02) E.8 Page 222
OSErr Validate_ac4_presentation_v0_dsi( BitBuffer *bb, void *refcon, UInt8* presentation_bytes, UInt64 count)
{
	OSErr err = noErr;
	UInt64 i,j, bits_counter,padding;
	UInt8 presentation_config,b_add_emdf_substreams;
	bits_counter = 0;
	presentation_config = GetBits(bb, 5, &err); if (err) GOTOBAIL;
	atomprint("presentation_config_%ld=\"%d\"\n", count, presentation_config);
	bits_counter += 5;
	if (presentation_config == 6)
	{
		b_add_emdf_substreams = 1;
	}
	else
	{
		UInt8 mdcompat; mdcompat = GetBits(bb, 3, &err); if (err) GOTOBAIL;
		atomprint("mdcompat_%ld=\"%d\"\n", count, mdcompat);

		UInt8 b_presentation_id; b_presentation_id = GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_presentation_id_%ld=\"%d\"\n", count, b_presentation_id);

		bits_counter+=4;
		if(b_presentation_id){
			UInt8 presentation_id; presentation_id = GetBits(bb, 5, &err); if (err) GOTOBAIL;
			atomprint("presentation_id_%ld=\"%d\"\n", count, presentation_id);
			bits_counter+=5;
		}

		UInt8 dsi_frame_rate_multiply_info = GetBits(bb, 2, &err); if (err) GOTOBAIL;
		atomprint("dsi_frame_rate_multiply_info_%ld=\"%d\"\n", count,dsi_frame_rate_multiply_info);

		UInt8 presentation_emdf_version = GetBits(bb, 5, &err); if (err) GOTOBAIL;
		atomprint("presentation_emdf_version_%ld=\"%d\"\n", count, presentation_emdf_version);

		UInt16 presentation_key_id = GetBits(bb, 10, &err); if (err) GOTOBAIL;
		atomprint("presentation_key_id_%ld=\"%d\"\n", count, presentation_key_id);

		UInt32 presentation_channel_mask = GetBits(bb, 24, &err); if (err) GOTOBAIL;
		atomprint("presentation_channel_mask_%ld=\"%d\"\n", count, presentation_channel_mask);
		if (presentation_channel_mask & (1<<23))
		{
			errprint("ETSI TS 103 190-2 v1.2.1 Annex E Line 14438: Bit 23 (the most significant bit) shall be set to false,but it is true\n" );
		}

		bits_counter += 41;
		if (presentation_config == 0x1f)
		{
			BAILIFERR( Validate_ac4_substream_dsi( bb, refcon, &bits_counter, count));
		}
		else
		{
			UInt8 b_hsf_ext = GetBits(bb, 1, &err); if (err) GOTOBAIL;
			atomprint("b_hsf_ext_%ld=\"%d\"\n", count, b_hsf_ext);

			bits_counter += 1;
			if(presentation_config == 0 || presentation_config == 1 || presentation_config ==2)
			{
				BAILIFERR( Validate_ac4_substream_dsi( bb, refcon, &bits_counter, count));
				BAILIFERR( Validate_ac4_substream_dsi( bb, refcon, &bits_counter, count));
			}
			if(presentation_config == 3 || presentation_config == 4)
			{
				BAILIFERR( Validate_ac4_substream_dsi( bb, refcon, &bits_counter, count ));
				BAILIFERR( Validate_ac4_substream_dsi( bb, refcon, &bits_counter, count));
				BAILIFERR( Validate_ac4_substream_dsi( bb, refcon, &bits_counter, count));
			}
			if(presentation_config == 5)
				BAILIFERR( Validate_ac4_substream_dsi( bb, refcon, &bits_counter, count));

			if(presentation_config > 5)
			{
				UInt8 n_skip_bytes = GetBits(bb, 7, &err); if (err) GOTOBAIL;
				atomprint("n_skip_bytes_%ld=\"%d\"\n", count, n_skip_bytes);

				bits_counter += 7;
				for(i = 0;i < n_skip_bytes; i++)
				{
					/*skip_data*/(void)GetBits(bb, 8, &err); if (err) GOTOBAIL;
					bits_counter += 8;
				}
			}
		}
		UInt8 b_pre_virtualized = GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_pre_virtualized_%ld=\"%d\"\n", count, b_pre_virtualized);

		b_add_emdf_substreams=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_add_emdf_substreams_%ld=\"%d\"\n", count, b_add_emdf_substreams);
		bits_counter+=2;
	}

	if(b_add_emdf_substreams)
	{
		UInt8 n_add_emdf_substreams=GetBits(bb, 7, &err); if (err) GOTOBAIL;
		atomprint("n_add_emdf_substreams_%ld=\"%d\"\n", count, n_add_emdf_substreams);
		bits_counter+=7;

		for(j = 0;j < n_add_emdf_substreams;j++)
		{
			UInt8 substream_emdf_version = GetBits(bb, 5, &err); if (err) GOTOBAIL;
			atomprint("substream_emdf_version_%lld_%ld=\"%d\"\n", count, j, substream_emdf_version);

			UInt16 substream_key_id = GetBits(bb, 10, &err); if (err) GOTOBAIL;
			atomprint("substream_key_id_%lld_%ld=\"%d\"\n", count, j, substream_key_id);

			bits_counter += 15;
		}
	}
	padding = (bits_counter %8) ? 8 - (bits_counter % 8) : 0;
	/*byte_align*/(void)GetBits(bb, padding, &err); if (err) GOTOBAIL;
	bits_counter += padding;
	if (presentation_bytes) {
		*presentation_bytes=(UInt8)(bits_counter + padding)/8;
	}

bail:
	return err;
}

//==========================================================================================

OSErr Validate_ac4_substream_dsi( BitBuffer *bb, void *refcon, UInt64* bits_counter, UInt64 count)
{
	OSErr err = noErr;
	UInt64 i;

	UInt8 channel_mode; channel_mode=GetBits(bb, 5, &err); if (err) GOTOBAIL;
	atomprint("channel_mode_%ld=\"%d\"\n",count, channel_mode);

	UInt8 dsi_sf_multiplier; dsi_sf_multiplier = GetBits(bb, 2, &err); if (err) GOTOBAIL;
	atomprint("dsi_sf_multiplier_%ld=\"%d\"\n",count, dsi_sf_multiplier);

	UInt8 b_substream_bitrate_indicator; b_substream_bitrate_indicator=GetBits(bb, 1, &err); if (err) GOTOBAIL;
	atomprint("b_substream_bitrate_indicator_%ld=\"%d\"\n",count,  b_substream_bitrate_indicator);

	*bits_counter+=8;
	if(b_substream_bitrate_indicator){
		UInt8 substream_bitrate_indicator; substream_bitrate_indicator = GetBits(bb, 5, &err); if (err) GOTOBAIL;
		atomprint("substream_bitrate_indicator_%ld=\"%d\"\n",count, substream_bitrate_indicator);
		*bits_counter+=5;
	}

	// According to the definition of channel_mode, it should be expressed as
	// the ch_mode value from table 78, for all currently defined channel
	// configurations. See TS 103190-2 v1.2.1, section E.9.2.
	if((channel_mode >= 7) && (channel_mode <= 10))
	{
		UInt8 add_ch_base; add_ch_base = GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("add_ch_base_%ld=\"%d\"\n",count, add_ch_base);

		*bits_counter+=1;
	}
	UInt8 b_content_type; b_content_type = GetBits(bb, 1, &err); if (err) GOTOBAIL;
	atomprint("b_content_type_%ld=\"%d\"\n",count, b_content_type);

	*bits_counter+=1;
	if(b_content_type)
	{
		UInt8 content_classifier; content_classifier = GetBits(bb, 3, &err); if (err) GOTOBAIL;
		atomprint("content_classifier_%ld=\"%d\"\n",count, content_classifier);

		UInt8 b_language_indicator; b_language_indicator = GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_language_indicator_%ld=\"%d\"\n",count, b_language_indicator);

		*bits_counter+=4;

		if(b_language_indicator)
		{
			UInt8 n_language_tag_bytes; n_language_tag_bytes = GetBits(bb, 6, &err); if (err) GOTOBAIL;
			atomprint("n_language_tag_bytes_%ld=\"%d\"\n",count, n_language_tag_bytes);

			*bits_counter+=6;
			for(i=0;i<n_language_tag_bytes;i++){
				UInt8 language_tag_bytes; language_tag_bytes = GetBits(bb, 8, &err); if (err) GOTOBAIL;
				atomprint("language_tag_bytes_%ld=\"%d\"\n",count, language_tag_bytes);

				*bits_counter+=8;
			}
		}
	}
bail:
	return err;
}


//==========================================================================================

//ETSI TS 102 366 E.10 Page 194
OSErr Validate_ac4_presentation_v1_dsi( BitBuffer *bb, void *refcon, UInt8 pres_bytes, UInt8* presentation_bytes, UInt64 count)
{
	OSErr err = noErr;
	UInt64 i;
	UInt32 j, bits_counter, padding, presentation_channel_mask_v1;
	UInt8 presentation_config, b_add_emdf_substreams, mdcompat,b_presentation_id,
		b_presentation_channel_coded, dsi_presentation_ch_mode,dsi_presentation_channel_mode_core,
		b_presentation_core_differs, b_presentation_core_channel_coded,
		b_presentation_filter, n_filter_bytes,pres_top_channel_pairs,b_enable_presentation,
		n_substream_groups_minus2 , n_substream_groups, sg,n_skip_bytes,
		n_add_emdf_substreams, b_presentation_bitrate_info, pres_b_4_back_channels_present,
		presentation_emdf_version, dsi_frame_rate_multiply_info, dsi_frame_rate_fraction_info,
		b_extended_presentation_id, presentation_id, b_multi_pid, b_pre_virtualized;
	UInt16  b_alternative, presentation_key_id;

	vg.tabcnt++;


	bits_counter=0;
	presentation_config=GetBits(bb, 5, &err); if (err) GOTOBAIL;
	atomprint("presentation_config_%ld=\"%d\"\n", count, presentation_config);
	bits_counter+=5;
	if(presentation_config == 0x06){
		b_add_emdf_substreams=1;
	}
	else {
		mdcompat=GetBits(bb, 3, &err); if (err) GOTOBAIL;
		atomprint("mdcompat_%ld=\"%d\"\n",count, mdcompat);
		b_presentation_id = GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_presentation_id_%ld=\"%d\"\n",count, b_presentation_id);
		bits_counter+=4;
		if(b_presentation_id){
			presentation_id=GetBits(bb, 5, &err); if (err) GOTOBAIL;
			atomprint("presentation_id_%ld=\"%d\"\n",count, presentation_id);
			bits_counter+=5;
		}

		dsi_frame_rate_multiply_info = GetBits(bb, 2, &err); if (err) GOTOBAIL;
		atomprint("dsi_frame_rate_multiply_info_%ld=\"%d\"\n",count, dsi_frame_rate_multiply_info);

		dsi_frame_rate_fraction_info = GetBits(bb, 2, &err); if (err) GOTOBAIL;
		atomprint("dsi_frame_rate_fraction_info_%ld=\"%d\"\n",count, dsi_frame_rate_fraction_info);

		presentation_emdf_version = GetBits(bb, 5, &err); if (err) GOTOBAIL;
		atomprint("presentation_emdf_version_%ld=\"%d\"\n",count, presentation_emdf_version);

		presentation_key_id = GetBits(bb, 10, &err); if (err) GOTOBAIL;
		atomprint("presentation_key_id_%ld=\"%d\"\n",count, presentation_key_id);

		b_presentation_channel_coded=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_presentation_channel_coded_%ld=\"%d\"\n",count, b_presentation_channel_coded);

		bits_counter+=20;

		if(b_presentation_channel_coded){
			dsi_presentation_ch_mode=GetBits(bb, 5, &err); if (err) GOTOBAIL;
			atomprint("dsi_presentation_ch_mode_%ld=\"%d\"\n",count, dsi_presentation_ch_mode);
			bits_counter+=5;
			if(dsi_presentation_ch_mode == 11 || dsi_presentation_ch_mode == 12 ||
				dsi_presentation_ch_mode == 13 || dsi_presentation_ch_mode==14 )
			{
				pres_b_4_back_channels_present = GetBits(bb, 1, &err); if (err) GOTOBAIL;
				atomprint("pres_b_4_back_channels_present_%ld=\"%d\"\n",count, pres_b_4_back_channels_present);
				pres_top_channel_pairs = GetBits(bb, 2, &err); if (err) GOTOBAIL;
				atomprint("pres_top_channel_pairs_%ld=\"%d\"\n",count, pres_top_channel_pairs);
				bits_counter+=3;
			}
			presentation_channel_mask_v1 = GetBits(bb, 24, &err); if (err) GOTOBAIL;
			atomprint("presentation_channel_mask_v1_%ld=\"%d\"\n",count, presentation_channel_mask_v1);
			bits_counter+=24;
		}
		b_presentation_core_differs=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_presentation_core_differs_%ld=\"%d\"\n",count, b_presentation_core_differs);
		bits_counter+=1;
		if(b_presentation_core_differs)
		{
			b_presentation_core_channel_coded=GetBits(bb, 1, &err); if (err) GOTOBAIL;
			atomprint("b_presentation_core_channel_coded_%ld=\"%d\"\n",count, b_presentation_core_channel_coded);
			bits_counter+=1;
			if(b_presentation_core_channel_coded){
				dsi_presentation_channel_mode_core = GetBits(bb, 2, &err); if (err) GOTOBAIL;
				atomprint("dsi_presentation_channel_mode_core_%ld=\"%d\"\n",count, dsi_presentation_channel_mode_core);
				bits_counter+=2;
			}

		}
		b_presentation_filter=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_presentation_filter_%ld=\"%d\"\n",count, b_presentation_filter);
		bits_counter+=1;
		if(b_presentation_filter)
		{
			b_enable_presentation = GetBits(bb, 1, &err); if (err) GOTOBAIL;
			atomprint("b_enable_presentation_%ld=\"%d\"\n",count, b_enable_presentation);
			n_filter_bytes=GetBits(bb, 8, &err); if (err) GOTOBAIL;
			bits_counter+=9;
			for(i=0;i<n_filter_bytes;i++){
				/*filter_data*/(void)GetBits(bb, 8, &err); if (err) GOTOBAIL;
				bits_counter+=8;
			}
		}
		if(presentation_config == 0x1f){
			BAILIFERR( Validate_ac4_substream_group_dsi( bb, refcon, &bits_counter));
		}
		else {
			b_multi_pid = GetBits(bb, 1, &err); if (err) GOTOBAIL;
			atomprint("b_multi_pid_%ld=\"%d\"\n",count, b_multi_pid);
			bits_counter+=1;
			if(presentation_config ==0 || presentation_config==1 || presentation_config==2)
			{
				BAILIFERR( Validate_ac4_substream_group_dsi( bb, refcon, &bits_counter));
				BAILIFERR( Validate_ac4_substream_group_dsi( bb, refcon, &bits_counter));
			}
			if(presentation_config ==3 || presentation_config ==4)
			{
				BAILIFERR( Validate_ac4_substream_group_dsi( bb, refcon, &bits_counter));
				BAILIFERR( Validate_ac4_substream_group_dsi( bb, refcon, &bits_counter));
				BAILIFERR( Validate_ac4_substream_group_dsi( bb, refcon, &bits_counter));
			}
			if(presentation_config ==5){
				 n_substream_groups_minus2=GetBits(bb, 3, &err); if (err) GOTOBAIL;
				 bits_counter+=3;
				 n_substream_groups = n_substream_groups_minus2 + 2;
				 for(sg=0;sg<n_substream_groups;sg++){
					 BAILIFERR( Validate_ac4_substream_group_dsi( bb, refcon, &bits_counter));
				}
			}
			if(presentation_config>5){
				n_skip_bytes=GetBits(bb, 7, &err); if (err) GOTOBAIL;
				bits_counter+=7;
				for(i=0;i<n_skip_bytes;i++){
					/*skip_data*/(void)GetBits(bb, 8, &err); if (err) GOTOBAIL;
					bits_counter+=8;
				}
			}
		}

		b_pre_virtualized = GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_pre_virtualized_%ld=\"%d\"\n",count, b_pre_virtualized);

		b_add_emdf_substreams=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		atomprint("b_add_emdf_substreams_%ld=\"%d\"\n",count, b_add_emdf_substreams);
		bits_counter+=2;

	}

	if(b_add_emdf_substreams){
		n_add_emdf_substreams=GetBits(bb, 7, &err); if (err) GOTOBAIL;
		bits_counter+=7;
		for(j=0;j<n_add_emdf_substreams;j++){
		   /*substream_emdf_version*/(void)GetBits(bb, 5, &err); if (err) GOTOBAIL;
		   /*substream_key_id*/(void)GetBits(bb, 10, &err); if (err) GOTOBAIL;
		   bits_counter+=15;
		}
	}


	b_presentation_bitrate_info=GetBits(bb, 1, &err); if (err) GOTOBAIL;
	bits_counter+=1;
	if(b_presentation_bitrate_info){
		/*temp*/(void)GetBits(bb, 2, &err); if (err) GOTOBAIL;
		/*temp*/(void)GetBits(bb, 32, &err); if (err) GOTOBAIL;
		/*temp*/(void)GetBits(bb, 32, &err); if (err) GOTOBAIL;
		bits_counter+=66;
	}


	b_alternative=GetBits(bb, 1, &err); if (err) GOTOBAIL;
	bits_counter+=1;
	if(b_alternative)
	{
		padding= (bits_counter %8)?8 - (bits_counter % 8):0;
		/*byte_align*/(void)GetBits(bb, padding, &err); if (err) GOTOBAIL;
		bits_counter+=padding;
		BAILIFERR( Validate_ac4_alternative_info( bb, refcon, &bits_counter));
	}

	padding= (bits_counter %8)?8 - (bits_counter % 8):0;
	/*byte_align*/(void)GetBits(bb, padding, &err); if (err) GOTOBAIL;
	bits_counter+=padding;


	if (bits_counter <= ((UInt32)pres_bytes-1)*8){
		/*de_indicator*/(void)GetBits(bb, 1, &err); if (err) GOTOBAIL;
		/*reserved*/(void)GetBits(bb, 5, &err); if (err) GOTOBAIL;
		b_extended_presentation_id=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		bits_counter+=7;
		if(b_extended_presentation_id){
			/*extended_presentation_id*/(void)GetBits(bb, 9, &err); if (err) GOTOBAIL;
			bits_counter+=9;
		}
		else{
			/*reserved*/(void)GetBits(bb, 1, &err); if (err) GOTOBAIL;
			bits_counter+=1;
		}
	}

	if (presentation_bytes) {
		*presentation_bytes=(UInt8)(bits_counter)/8;
	}
bail:
	vg.tabcnt--;
	return err;
}

//==========================================================================================

OSErr Validate_ac4_substream_group_dsi( BitBuffer *bb, void *refcon , UInt32 * bits_counter)
{
	OSErr err = noErr;
	UInt64 i;
	UInt8 b_channel_coded,n_substreams,  b_substream_bitrate_indicator,
	b_ajoc, b_static_dmx, b_hsf_ext, b_substreams_present, dsi_sf_multiplier,
	substream_bitrate_indicator, b_content_type, b_language_indicator, n_language_tag_bytes;

	vg.tabcnt++;

	b_substreams_present = GetBits(bb, 1, &err); if (err) GOTOBAIL;
	atomprint("b_substreams_present=\"%d\"\n",b_substreams_present);

	b_hsf_ext = GetBits(bb, 1, &err); if (err) GOTOBAIL;
	atomprint("b_hsf_ext=\"%d\"\n",b_hsf_ext);

	b_channel_coded=GetBits(bb, 1, &err); if (err) GOTOBAIL;
	atomprint("b_channel_coded=\"%d\"\n",b_channel_coded);

	n_substreams=GetBits(bb, 8, &err); if (err) GOTOBAIL;
	atomprint("n_substreams=\"%d\"\n",n_substreams);

	*bits_counter+=11;

	for(i=0; i<n_substreams;i++){
		dsi_sf_multiplier = GetBits(bb, 2, &err); if (err) GOTOBAIL;
		b_substream_bitrate_indicator=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		*bits_counter+=3;
		if(b_substream_bitrate_indicator){
			substream_bitrate_indicator = GetBits(bb, 5, &err); if (err) GOTOBAIL;
			*bits_counter+=5;
		}
		if(b_channel_coded){
			/*dsi_substream_channel_mask*/(void)GetBits(bb, 24, &err); if (err) GOTOBAIL;
			*bits_counter+=24;
		}
		else{
		   b_ajoc=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		   *bits_counter+=1;
		   if(b_ajoc){
				b_static_dmx=GetBits(bb, 1, &err); if (err) GOTOBAIL;
				*bits_counter+=1;
				if(b_static_dmx==0){
					/*n_dmx_objects_minus1*/(void)GetBits(bb, 4, &err); if (err) GOTOBAIL;
					*bits_counter+=4;
				}
				/*n_umx_objects_minus1*/(void)GetBits(bb, 6, &err); if (err) GOTOBAIL;
				*bits_counter+=6;
		   }
		   /*b_substream_contains_bed_objects*/(void)GetBits(bb, 1, &err); if (err) GOTOBAIL;
		   /*b_substream_contains_dynamic_objects*/(void)GetBits(bb, 1, &err); if (err) GOTOBAIL;
		   /*b_substream_contains_ISF_objects*/(void)GetBits(bb, 1, &err); if (err) GOTOBAIL;
		   /*reserved*/(void)GetBits(bb, 1, &err); if (err) GOTOBAIL;
		   *bits_counter+=4;
		}

	}
	b_content_type=GetBits(bb, 1, &err); if (err) GOTOBAIL;
	*bits_counter+=1;
	if(b_content_type){
		/*content_classifier*/(void)GetBits(bb, 3, &err); if (err) GOTOBAIL;
		b_language_indicator=GetBits(bb, 1, &err); if (err) GOTOBAIL;
		*bits_counter+=4;
		if(b_language_indicator){
			n_language_tag_bytes=GetBits(bb, 6, &err); if (err) GOTOBAIL;
			*bits_counter+=6;
			for(i=0;i<n_language_tag_bytes;i++){
				/*language_tag_bytes*/(void)GetBits(bb, 8, &err); if (err) GOTOBAIL;
				*bits_counter+=8;
			}
		}

	}

bail:
	vg.tabcnt--;
	return err;
}
//==========================================================================================

OSErr Validate_ac4_alternative_info( BitBuffer *bb, void *refcon, UInt32 * bits_counter )
{
	OSErr err = noErr;
	UInt64 i,t;
	UInt8 n_targets;
	UInt16 name_len;

	name_len=GetBits(bb, 16, &err); if (err) GOTOBAIL;
	*bits_counter+=16;
	for(i=0;i<name_len;i++){
		/*presentation_name*/(void)GetBits(bb, 8, &err); if (err) GOTOBAIL;
		*bits_counter+=8;
	}
	n_targets=GetBits(bb, 5, &err); if (err) GOTOBAIL;
	*bits_counter+=5;
	for(t=0;t<n_targets;t++){
		/*target_md_compat*/(void)GetBits(bb, 3, &err); if (err) GOTOBAIL;
		/*target_device_category*/(void)GetBits(bb, 8, &err); if (err) GOTOBAIL;
		*bits_counter+=11;
	}

bail:
	return err;
}

//==========================================================================================
