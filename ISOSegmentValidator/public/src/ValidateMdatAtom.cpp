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

#define atomprint atomprint
#define test(name) //printf("\"" #name "\"=\"%d\"\n", name)

#if 0
typedef struct
{
	const char  *name;
	UInt32	  n_bits;
	UInt32	  value;
} Atom;

#define MAX_ATOMS 1024
static Atom atom_list[MAX_ATOMS];
#endif

typedef struct
{
   UInt32  protection_length_primary;
   UInt32  protection_length_secondary;
   UInt32  protection_bits_primary;
   UInt32  protection_bits_secondary;
} AC4_emdf_protection;

typedef struct
{
	UInt32 substream_index;
} AC4_emdf_payloads_substream_info;


typedef struct
{
	AC4_emdf_payloads_substream_info ac4_emdf_payloads_substream_info;
	AC4_emdf_protection ac4_emdf_protection;
	UInt32  emdf_version;
	UInt32  key_id;
	UInt32  b_emdf_payloads_substream_info;
} AC4_emdf_info;


typedef struct
{
	UInt32  b_multiplier;
	UInt32  multiplier_bit;
} AC4_frame_rate_multiply_info;

typedef struct
{
	UInt32  b_frame_rate_fraction;
	UInt32  b_frame_rate_fraction_is_4;
} AC4_frame_rate_fractions_info;


typedef struct
{
	UInt32  b_alternative;
	UInt32  b_pres_ndot;
	UInt32  substream_index;
} AC4_presentation_substream_info;

typedef struct
{
	UInt32  channel_mode;
	UInt32  b_4_back_channels_present;
	UInt32  b_centre_present;
	UInt32  top_channels_present;
	UInt32  b_sf_multiplier;
	UInt32  sf_multiplier;
	UInt32  b_bitrate_info;
	UInt32  bitrate_indicator;
	UInt32  add_ch_base;
	UInt32  b_audio_ndot;
	UInt32  substream_index;
} AC4_substream_info_chan;

typedef struct
{

} AC4_substream_info_ajoc;

typedef struct
{

} AC4_hsf_ext_substream_info;

typedef struct
{

} Oamd_substream_info;

typedef struct
{

} Content_type;


typedef struct
{
	AC4_substream_info_ajoc ac4_substream_info_ajoc;
	AC4_substream_info_chan ac4_substream_info_chan;
	AC4_hsf_ext_substream_info ac4_hsf_ext_substream_info;
	Content_type ac4_content_type;
	Oamd_substream_info oamd_substream_info;
	UInt32  b_substreams_present;
	UInt32  b_hsf_ext;
	UInt32  b_single_substream;
	UInt32  n_lf_substreams;
	UInt32  n_lf_substreams_minus2;
	UInt32  b_oamd_substream;
	UInt32  b_ajoc;
	UInt32  sus_ver;
	UInt32  b_channel_coded;
	UInt32  b_content_type;
} AC4_substream_group_info;

typedef struct
{
	AC4_substream_group_info ac4_substream_group_info;
	UInt32 group_index;

} AC4_sgi_specifier;

typedef struct
{
} AC4_presentation_info;

typedef struct
{
	AC4_presentation_substream_info ac4_presentation_substream_info;
	AC4_frame_rate_multiply_info ac4_frame_rate_multiply_info;
	AC4_frame_rate_fractions_info ac4_frame_rate_fractions_info;
	AC4_emdf_info ac4_emdf_info;
	AC4_sgi_specifier ac4_sgi_specifier;
	UInt32  b_single_substream_group;
	UInt32  b_tmp;
	UInt32  presentation_version;
	UInt32  presentation_config;
	UInt32  b_add_emdf_substreams;
	UInt32  mdcompat;
	UInt32  b_presentation_id;
	UInt32  presentation_id;
	UInt32  b_presentation_filter;
	UInt32  b_enable_presentation;
	UInt32  b_multi_pid;
	UInt32  n_substream_groups_minus_2;
	UInt32  n_substream_groups;
	UInt32  b_pre_virtualized;
	UInt32  n_add_emdf_substreams;
	UInt32  frame_rate_fraction;
	UInt32  frame_rate_factor;
} AC4_presentation_v1_info;


typedef struct
{
	AC4_presentation_info ac4_presentation_info;
	AC4_presentation_v1_info ac4_presentation_v1_info;
	AC4_substream_group_info ac4_substream_group_info;
	UInt32  bitstream_version;
	UInt32  sequence_counter;
	UInt32  b_wait_frames;
	UInt32  wait_frames;
	UInt32  br_code;
	UInt32  fs_index;
	UInt32  frame_rate_index;
	UInt32  b_iframe_global;
	UInt32  b_single_presentation;
	UInt32  b_more_presentations;
	UInt32  b_payload_base;
	UInt32  payload_base_minus1;
	UInt32  b_program_id;
	UInt32  short_program_id;
	UInt32  b_program_uuid_present;
	UInt32  program_uuid;
	UInt32  n_presentations;
	UInt32  payload_base;
	UInt32  n_substream_groups;
	UInt32  total_n_substream_groups;
} AC4_Toc;

static AC4_Toc ac4_toc;

#define ATOMPRINT_FIELD(fmt, table, var) \
{ \
	if (!strcmp(fmt, "array_b") ) \
	{ \
		atomprint("%s=\"" fmt "\"\n", #var, table->var); \
	} \
	else \
	{ \
		atomprint("%s=\"" fmt "\"\n", #var, table->var); \
	} \
}

#define VALIDATE_FIELD(fmt, table, var, bits) \
{ \
	if (bits != 0) \
	{ \
		table->var = GetBits(bb, bits, &err); if (err) goto bail; \
		if (!strcmp(fmt, "array_b") ) \
		{ \
			atomprint("%s=\"" fmt "\"\n", #var, table->var); \
		} \
		else \
		{ \
			atomprint("%s=\"" fmt "\"\n", #var, table->var); \
		} \
	} \
}


inline OSErr variable_bits(BitBuffer *bb, UInt32 n_bits, UInt32 *value)
{
	OSErr err = noErr;
	UInt8 b_read_more;
	printf("<%s>: variable_bits(%lu)\n", __FILE__, n_bits);
	*value = 0;
	do {
		*value += GetBits(bb, n_bits, &err);
		if (err) return err;
		b_read_more = GetBits(bb, 1, &err);
		if (err) return err;
		if (b_read_more)
		{
			*value = (*value << n_bits) + (1 << n_bits);
		}
	} while (b_read_more);

	return err;
}

/*


presentation_version()
{
	val = 0;
	while (b_tmp == 1) {   ...... 1
		val++;
	}
	return val;
}
*/

#define BITCOUNT_TO_VAL( table, var ) \
	do { \
			UInt8 b_tmp; \
			UInt8 bit=0; \
			table->var = 0; \
			do \
			{ \
				b_tmp = GetBits(bb, 1, &err); if (err) goto bail; \
				atomprint("%s_%d=\"%d\"\n", #var"_bit", bit, b_tmp); \
				if (b_tmp) \
				{ \
					table->var++; \
				} \
				bit++; \
			} while (b_tmp) ; \
	} while (false)

OSErr Validate_AC4_emdf_protection(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	 UInt32 primary_length;
	printf ("<%s> : ent \n", __FUNCTION__);

	atomprint("<ac4_emdf_protection\n");
	vg.tabcnt++;

	AC4_emdf_protection *table = (AC4_emdf_protection*)refcon;

	VALIDATE_FIELD("%d", table, protection_length_primary, 2);
	VALIDATE_FIELD("%d", table, protection_length_secondary, 2);

	primary_length = 0;
	switch (table->protection_length_primary)
	{
		case 1:
			primary_length = 8;
			break;
		case 2:
			primary_length = 32;
			break;
		case 3:
			primary_length = 128;
			break;
		default:
			printf("<%s> : table->protection_length_primary %lu is invalid\n", __FUNCTION__, table->protection_length_primary);
			break;
	}
	VALIDATE_FIELD("%d", table, protection_bits_primary, primary_length);

	UInt32 secondary_length;
	switch (table->protection_length_secondary)
	{
		case 0:
			secondary_length = 0;
			break;
		case 1:
			secondary_length  = 8;
			break;
		case 2:
			secondary_length  = 32;
			break;
		case 3:
			secondary_length = 128;
		default:
			break;
	}
	VALIDATE_FIELD("%d", table, protection_bits_secondary, secondary_length);

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_emdf_protection>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}


OSErr Validate_AC4_emdf_payloads_substream_info(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	atomprint("<ac4_emdf_payloads_substream_info\n");
	vg.tabcnt++;

	AC4_emdf_payloads_substream_info *table = (AC4_emdf_payloads_substream_info*)refcon;

	VALIDATE_FIELD  ("%d", table, substream_index, 2 );
	if (table->substream_index == 3)
	{
		BAILIFERR(variable_bits(bb, 2, &table->substream_index));
	}

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_emdf_payloads_substream_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_hsf_ext_substream_info(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	atomprint("<ac4_hsf_ext_substream_info\n");
	vg.tabcnt++;

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_hsf_ext_substream_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}


OSErr Validate_AC4_substream_info_chan(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_substream_info_chan *table = &ac4_toc->ac4_substream_group_info.ac4_substream_info_chan;

	atomprint(">\n");
	atomprint("<ac4_substream_info_chan\n");
	vg.tabcnt++;

	//6.3.2.7.2 channel_mode

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_substream_info_chan>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_substream_group_info(BitBuffer *bb, void *refcon)
{

	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	atomprint(">\n");
	atomprint("<ac4_substream_group_info\n");
	vg.tabcnt++;

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_substream_group_info *table = &ac4_toc->ac4_substream_group_info;

	VALIDATE_FIELD("%d", table, b_substreams_present, 1);
	VALIDATE_FIELD("%d", table, b_hsf_ext, 1);
	VALIDATE_FIELD("%d", table, b_single_substream, 1);

	table->n_lf_substreams = 0;
	if (table->b_single_substream)
	{
		table->n_lf_substreams = 1;
	}
	else
	{
		VALIDATE_FIELD("%d", table, n_lf_substreams_minus2, 2);
		table->n_lf_substreams = table->n_lf_substreams_minus2 + 2;
		if (table->n_lf_substreams == 5)
		{
			BAILIFERR(variable_bits(bb, 2, &table->n_lf_substreams));
		}
	}

	VALIDATE_FIELD("%d", table, b_channel_coded, 1);
	if (table->b_channel_coded)
	{
		for (UInt32 sus=0; sus<table->n_lf_substreams; sus++)
		{
			if (ac4_toc->bitstream_version == 1)
			{
				VALIDATE_FIELD("%d", table, sus_ver, 1);
			}
			else
			{
				table->sus_ver = 1;
			}
			BAILIFERR(Validate_AC4_substream_info_chan(bb, table));
			if (table->b_hsf_ext)
			{
				BAILIFERR(Validate_AC4_hsf_ext_substream_info(bb, table));
			}
		}
	}

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_substream_group_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_presentation_info(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);
	atomprint("<ac4_presentation_info\n");
	vg.tabcnt++;

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_presentation_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_substream_index_table(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);
	atomprint("<substream_index_table\n");
	vg.tabcnt++;

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</substream_index_table>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_presentation_substream_info(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_presentation_substream_info *table = &ac4_toc->ac4_presentation_v1_info.ac4_presentation_substream_info;

	atomprint(">\n");
	atomprint("<ac4_presentation_substream_info\n");
	vg.tabcnt++;

	VALIDATE_FIELD("%d",table, b_alternative, 1);
	VALIDATE_FIELD("%d",table, b_pres_ndot, 1);
	VALIDATE_FIELD("%d",table, substream_index, 2);
	if (table->substream_index == 3)
	{
		printf ("table->substream_index_1 %lu\n", table->substream_index);
		BAILIFERR(variable_bits(bb, 2, &table->substream_index));
		printf ("table->substream_index_2 %lu\n", table->substream_index);
	}

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_presentation_substream_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_frame_rate_multiply_info(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_frame_rate_multiply_info *table = &ac4_toc->ac4_presentation_v1_info.ac4_frame_rate_multiply_info;

	atomprint(">\n");
	atomprint("<frame_rate_multiply_info\n");
	vg.tabcnt++;

	switch (ac4_toc->frame_rate_index)
	{
		case 2:
		case 3:
		case 4:
			VALIDATE_FIELD  ("%d", table, b_multiplier, 1 );
			if (table->b_multiplier)
			{
				VALIDATE_FIELD  ("%d", table, multiplier_bit, 1 );
			}
			break;

		case 0:
		case 1:
		case 7:
		case 8:
		case 9:
			VALIDATE_FIELD  ("%d", table, b_multiplier, 1 );
			break;

		default:
			break;
	}
bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</frame_rate_multiply_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

// ETSI TS 103 190-1 V1.2.1 (2015-06) 4.3.3.5.3
static OSErr frame_rate_factor(const AC4_Toc *ac4_toc, UInt32 *frame_rate_factor)
{
	OSErr err = noErr;

	const AC4_frame_rate_multiply_info *ac4_frame_rate_multiply_info = &ac4_toc->ac4_presentation_v1_info.ac4_frame_rate_multiply_info;

	UInt32 l_frame_rate_factor = 0;

	switch (ac4_toc->frame_rate_index)
	{
		case 2:
		case 3:
		case 4:
			if (ac4_frame_rate_multiply_info->b_multiplier)
			{
				if (ac4_frame_rate_multiply_info->multiplier_bit)
				{
					l_frame_rate_factor = 4;
				}
				else
				{
					l_frame_rate_factor = 2;
				}
			}
			else
			{
				l_frame_rate_factor = 1;
			}
			break;

		case 0:
		case 1:
		case 7:
		case 8:
		case 9:
			if (ac4_frame_rate_multiply_info->b_multiplier)
			{
				l_frame_rate_factor = 2;
			}
			else
			{
				l_frame_rate_factor = 1;
			}
			break;

		case 5:
		case 6:
		case 10:
		case 11:
		case 12:
		case 13:
			l_frame_rate_factor = 1;
			break;

		default:
			errprint("%s : invalid frame_rate_index %d\n", __FUNCTION__, ac4_toc->frame_rate_index);
			err = badAtomErr;
			break;
	}

	*frame_rate_factor = l_frame_rate_factor;
	return err;
}

// ETSI TS 103 190-2 V1.2.1 (2018-02) 6.2.1.4
OSErr Validate_AC4_frame_rate_fractions_info(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	atomprint(">\n");
	atomprint("<ac4_frame_rate_fractions_info\n");
	vg.tabcnt++;

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_frame_rate_fractions_info *table = &ac4_toc->ac4_presentation_v1_info.ac4_frame_rate_fractions_info;

	UInt32 frame_rate_fraction = 1;

	switch (ac4_toc->frame_rate_index)
	{
		case 5:
		case 6:
		case 7:
		case 8:
		case 9:
			BAILIFERR(frame_rate_factor(ac4_toc, &ac4_toc->ac4_presentation_v1_info.frame_rate_factor));
			if (ac4_toc->ac4_presentation_v1_info.frame_rate_factor == 1 )
			{
				VALIDATE_FIELD("%d", table, b_frame_rate_fraction, 1);
				if (table->b_frame_rate_fraction)
				{
				   frame_rate_fraction = 2;
				}
			}
			break;

		case 10:
		case 11:
		case 12:
			VALIDATE_FIELD("%d", table, b_frame_rate_fraction, 1);
			if (table->b_frame_rate_fraction)
			{
				VALIDATE_FIELD("%d", table, b_frame_rate_fraction_is_4, 1);
				if (table->b_frame_rate_fraction_is_4)
				{
					frame_rate_fraction = 4;
				}
				else
				{
					frame_rate_fraction = 2;
				}
			}
			break;

		default:
			break;
	}

bail:
	ac4_toc->ac4_presentation_v1_info.frame_rate_fraction = frame_rate_fraction;

	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_frame_rate_fractions_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_emdf_info(BitBuffer *bb, void *refcon)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	atomprint("<ac4_emdf_info>\n");
	vg.tabcnt++;

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_emdf_info *table = &ac4_toc->ac4_presentation_v1_info.ac4_emdf_info;

	BAILIFERR(Validate_AC4_emdf_payloads_substream_info(bb, &table->ac4_emdf_payloads_substream_info));
	BAILIFERR(Validate_AC4_emdf_protection(bb, &table->ac4_emdf_protection));

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_emdf_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_sgi_specifier(BitBuffer *bb, void *refcon, UInt8 *max_group_index)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	atomprint("<ac4_sgi_specifier>\n");
	vg.tabcnt++;

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_sgi_specifier *table = &ac4_toc->ac4_presentation_v1_info.ac4_sgi_specifier;

	*max_group_index = 0;

	if (ac4_toc->bitstream_version == 1)
	{
		BAILIFERR(Validate_AC4_substream_group_info(bb, refcon));
	}
	else
	{
		table->group_index = GetBits(bb, 3, &err);
		if (err) goto bail;
		if (table->group_index == 7)
		{
			UInt32 temp;
			BAILIFERR(variable_bits(bb, 2, &temp));
			table->group_index += temp;
		}
		atomprint("group_index=\"%d\"\n", table->group_index);
		*max_group_index = table->group_index;
	}

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_sgi_specifier>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

OSErr Validate_AC4_presentation_v1_info(BitBuffer *bb, void *refcon, UInt8 *max_group_index)
{
	OSErr err = noErr;
	printf ("<%s> : ent \n", __FUNCTION__);

	AC4_Toc *ac4_toc = (AC4_Toc*)refcon;
	AC4_presentation_v1_info *table = &ac4_toc->ac4_presentation_v1_info;
	UInt8 group_index = 0;

	atomprint(">\n");
	atomprint("<ac4_presentation_v1_info\n");
	vg.tabcnt++;

	VALIDATE_FIELD  ("%d", table, b_single_substream_group,		   1 );
	if (table->b_single_substream_group != 1)
	{
		VALIDATE_FIELD  ("%d", table, presentation_config,			3 );
		if (table->presentation_config == 7)
		{
			BAILIFERR(variable_bits(bb, 2, &table->presentation_config));
		}
	}

	if ( ac4_toc->bitstream_version != 1)
	{
		BITCOUNT_TO_VAL(table, presentation_version);
	}

	if ( (table->b_single_substream_group != 1)  && ( table->presentation_config == 6 ))
	{
		table->b_add_emdf_substreams = 1;
	}
	else
	{
		if ( ac4_toc->bitstream_version != 1)
		{
			VALIDATE_FIELD  ("%d", table, mdcompat, 3 );
		}
		VALIDATE_FIELD  ("%d", table, b_presentation_id, 1 );
		if (table->b_presentation_id)
		{
			BAILIFERR(variable_bits(bb, 2, &table->presentation_id));
		}
		atomprint(">\n");
		BAILIFERR( Validate_AC4_frame_rate_multiply_info( bb, ac4_toc));
		BAILIFERR( Validate_AC4_frame_rate_fractions_info( bb, ac4_toc));
		BAILIFERR( Validate_AC4_emdf_info( bb, ac4_toc));
		VALIDATE_FIELD  ("%d", table, b_presentation_filter, 1 );
		if (table->b_presentation_filter)
		{
			table->b_enable_presentation = 1;
		}
		if (table->b_single_substream_group == 1)
		{
			BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
			ac4_toc->n_substream_groups = 1;
			if (group_index > *max_group_index)
			{
				*max_group_index = group_index;
			}
		}
		else
		{
			VALIDATE_FIELD  ("%d", table, b_multi_pid, 1 );
			switch (table->presentation_config)
			{
				case 0:
					/* Music and Effects + Dialogue */
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					ac4_toc->n_substream_groups = 2;
					break;

				case 1:
					/* Main + DE */
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					ac4_toc->n_substream_groups = 1;
					break;

				case 2:
					/* Main + Associated Audio */
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					ac4_toc->n_substream_groups = 2;
					break;

				case 3:
					/* Music and Effects + Dialogue + Associated Audio */
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					ac4_toc->n_substream_groups = 3;
					break;

				case 4:
					/* Main + DE + Associated Audio */
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
					if (group_index > *max_group_index)
					{
						*max_group_index = group_index;
					}
					ac4_toc->n_substream_groups = 2;
					break;

				case 5:
					/* Arbitrary number of roles and substream groups */
					VALIDATE_FIELD  ("%d", table, n_substream_groups_minus_2, 2 );
					table->n_substream_groups = table->n_substream_groups_minus_2 + 2;
					if (table->n_substream_groups == 5)
					{
						BAILIFERR(variable_bits(bb, 2, &table->n_substream_groups));
					}
					for (UInt32 sg = 0; sg < table->n_substream_groups; sg++)
					{
						BAILIFERR(Validate_AC4_sgi_specifier(bb, ac4_toc, &group_index));
						if (group_index > *max_group_index)
						{
							*max_group_index = group_index;
						}
					}
					break;

				default:
					/* EMDF and other data */
					//BAILIFERR( presentation_config_ext_info(bb, refcon));
					break;
		}
	}
	VALIDATE_FIELD  ("%d", table, b_pre_virtualized, 1 );
	VALIDATE_FIELD  ("%d", table, b_add_emdf_substreams, 1 );
	BAILIFERR( Validate_AC4_presentation_substream_info(bb, ac4_toc));
}

if (table->b_add_emdf_substreams)
{
	VALIDATE_FIELD  ("%d", table, n_add_emdf_substreams, 2 );
	if (table->n_add_emdf_substreams == 0)
	{
		BAILIFERR(variable_bits(bb, 2, &table->n_add_emdf_substreams));
	}
	for (UInt32 i = 0; i < table->n_add_emdf_substreams; i++)
	{
		BAILIFERR( Validate_AC4_emdf_info(bb, refcon));
	}
}

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</ac4_presentation_v1_info>\n");
	printf ("<%s> : ret %d\n", __FUNCTION__, err);
	return err;
}

// ETSI TS 103 190-2 V1.1.1 (2015-09)
//==========================================================================================
OSErr Validate_AC4_Toc( BitBuffer *bb, void *refcon)
{
	printf ("<%s> : ent : bb %p refcon %p\n", __FUNCTION__, bb, refcon);
	OSErr err = noErr;

	AC4_Toc *table = (AC4_Toc*)refcon;

	atomprint(">\n");
	atomprint("<ac4_toc\n");
	vg.tabcnt++;

	VALIDATE_FIELD  ("%d", table,  bitstream_version,		  2 );
	if (table->bitstream_version == 3)
	{
		UInt32 temp;
		BAILIFERR(variable_bits(bb, 2, &temp));
		table->bitstream_version += temp;
	}
	VALIDATE_FIELD  ("%d", table, sequence_counter,		   10 );
	VALIDATE_FIELD  ("%d", table, b_wait_frames,			   1 );
	if (table->b_wait_frames)
	{
		VALIDATE_FIELD  ("%d", table, wait_frames,			 3 );
		if (table->wait_frames > 0)
		{
			VALIDATE_FIELD  ("%d", table, br_code,			 2 );
		}
	}
	VALIDATE_FIELD  ("%d", table, fs_index,					1 );
	VALIDATE_FIELD  ("%d", table, frame_rate_index,			4 );
	VALIDATE_FIELD  ("%d", table, b_iframe_global,			 1 );
	VALIDATE_FIELD  ("%d", table, b_single_presentation,	   1 );

	if (table->b_single_presentation)
	{
		table->n_presentations = 1;
	}
	else
	{
		VALIDATE_FIELD  ("%d", table, b_more_presentations,	 1 );
		if (table->b_more_presentations)
		{
			BAILIFERR(variable_bits(bb, 2, &table->n_presentations));
		}
		else
		{
			table->n_presentations = 0;
		}
	}

	ATOMPRINT_FIELD ("%d", table, n_presentations);

	table->payload_base = 0;
	VALIDATE_FIELD  ("%d", table, b_payload_base,			   1 );
	if (table->b_payload_base)
	{
		VALIDATE_FIELD  ("%d", table, payload_base_minus1,	  5 );
		table->payload_base += table->payload_base_minus1 + 1;
		if (table->payload_base == 0x20)
		{
			BAILIFERR(variable_bits(bb, 3, &table->payload_base));
		}
	}

	if (table->bitstream_version <= 1)
	{
		for (UInt32 i = 0; i < table->n_presentations; i++)
		{
			BAILIFERR(Validate_AC4_presentation_info(bb, table ));
		}
	}
	else
	{
		VALIDATE_FIELD  ("%d", table, b_program_id,				1 );
		if ( table->b_program_id )
		{
			VALIDATE_FIELD  ("%d", table, short_program_id,		   16 );
			VALIDATE_FIELD  ("%d", table, b_program_uuid_present,	  1 );
			if ( table->b_program_uuid_present )
			{
				VALIDATE_FIELD  ("%d", table, program_uuid,		   16 * 8);
			}
		}

		UInt8 max_group_index = 0;
		for (UInt32 i=0; i < table->n_presentations; i++)
		{
			BAILIFERR(Validate_AC4_presentation_v1_info(bb, table, &max_group_index));
		}

		table->n_substream_groups = 1;
		table->total_n_substream_groups = 1 + max_group_index;
		for (UInt32 i=0; i < table->total_n_substream_groups; i++)
		{
			BAILIFERR(Validate_AC4_substream_group_info(bb, table));
		}
	}

bail:
	atomprint (">\n");
	if (err == noErr)
	{
		err = Validate_substream_index_table(bb, table);
	}
	vg.tabcnt--;
	atomprint("</ac4_toc>\n");

	printf ("<%s> : ret : err %d\n", __FUNCTION__, err);
	return err;
}


//==========================================================================================
OSErr Validate_mdat_Atom( atomOffsetEntry *aoe, void *refcon)
{
	printf ("<%s> : ent : aoe %p refcon %p\n", __FUNCTION__, aoe, refcon);
	OSErr err = noErr;
	unsigned int size;
	unsigned int type;
	UInt64 offset;

	void* bsDataP = NULL;
	BitBuffer bbuf, *bb;
	bb = &bbuf;

	// Get version/flags
	offset = aoe->offset;

	BAILIFERR(GetFileData( aoe, &size, offset, 4, &offset ));
	size=EndianU32_BtoN (size);

	atomprint("<mdat size=\"%d\"\n", size);
	vg.tabcnt++;

	BAILIFERR(GetFileData( aoe, &type, offset, 4, &offset ));
	type=EndianU32_BtoN(type);

	if (strstr(vg.codecs, "ac-4"))
	{
	BAILIFNIL( bsDataP = calloc(size - 8 + bitParsingSlop, 1), allocFailedErr );

	BAILIFERR( GetFileData( aoe, bsDataP, offset, size - 8, &offset ) );

	BitBuffer_Init(bb, (UInt8 *)bsDataP, size - 8);

		BAILIFERR(Validate_AC4_Toc(bb, &ac4_toc));
	}

bail:
	atomprint (">\n");
	vg.tabcnt--;
	atomprint("</mdat>\n");

	free(bsDataP);
	printf ("<%s> : ret : err %d\n", __FUNCTION__, err);
	return err;
}
