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
#include <iostream>
#include <fstream>
#include <sstream>
#include <string.h>
#include "stdio.h"
#include "stdlib.h"
#if STAND_ALONE_APP
	#include "console.h"
#endif
void myexit(int num)
{
	fprintf(stderr, "Exiting with code %d\n", num);
	getchar();
}

#if 1
#define myTAB "  "
#else
#define myTAB "\t"
#endif

ValidateGlobals vg = {};
FILE *f;		//to print atom content to xml file (later use for xml creation)

static int keymatch (const char * arg, const char * keyword, int minchars);

void expandArgv(int srcArgc, const char** srcArgV, int &dstArgc, const char** &dstArgv);

//#define STAND_ALONE_APP 1  //  #define this if you're using a source level debugger (i.e. Visual C++ in Windows)
							  //  also, near the beginning of main(), hard-code your arguments (e.g. your test file)

//==========================================================================================

/*
 * Case-insensitive matching of possibly-abbreviated keyword switches.
 * keyword is the constant keyword (must be lower case already),
 * minchars is length of minimum legal abbreviation.
 */

static int keymatch (const char * arg, const char * keyword, int minchars)
{
  register int ca, ck;
  register int nmatched = 0;

  while ((ca = *arg++) != '\0') {
	if ((ck = *keyword++) == '\0')
	  return false;		/* arg longer than keyword, no good */
	if (isupper(ca))		/* force arg to lcase (assume ck is already) */
	  ca = tolower(ca);
	if (ca != ck)
	  return false;		/* no good */
	nmatched++;			/* count matched characters */
  }
  /* reached end of argument; fail if it's too short for unique abbrev */
  if (nmatched < minchars)
	return false;
  return true;			/* A-OK */
}

void writeEntry(char* srcPtr, int &srcIndex, char* &dstPtr, int &dstIndex, int maxArgc)
{
	if(dstIndex >= maxArgc)
	{
		fprintf(stderr,"May number of config arguments %d overshot, exiting!\n",maxArgc);
		exit(-1);
	}
	dstPtr = (char*)(malloc((strlen(srcPtr) + 1) * sizeof(char)));  //allocate memory for each row
	strcpy(dstPtr,srcPtr);

	srcIndex++;
	dstIndex++;
	return;
}

void expandArgv(int srcArgc, char** srcArgV, int &dstArgc, char** &dstArgv)
{
#define maxArgc 255

  dstArgv= (char**)malloc(sizeof(char*) * maxArgc);		//allocate memory for no. of rows

  int dstIndex = 0;

  for (int srcIndex = 0 ; ; )					//read line from text file
  {

	if(strcmp(srcArgV[srcIndex],"-configfile") == 0)
	{
		srcIndex++;

		FILE* f = fopen( srcArgV[srcIndex], "r" );		  //location of text file to be opened specified by str
		if(f == NULL)
		{
			fprintf(stderr,"-configfile %s used, file not found, exiting!\n",srcArgV[srcIndex]);
			exit(-1);
		}

		srcIndex++;

		char line[ 1000 ];

		while (fgets( line, 1000, f ))				 //read line from text file
		{
		  char * pch;
		  pch = strtok(line,"\n, ");				  //remove \n character and space
		  while (pch != NULL)
		  {
			  int dummy;
			  writeEntry(pch,dummy,dstArgv[dstIndex],dstIndex,maxArgc); //Dont change srcIndex any further
			  pch=strtok(NULL,"\n ");
		  }
		}

		fclose(f);
	}
	else
		writeEntry(srcArgV[srcIndex],srcIndex,dstArgv[dstIndex],dstIndex,maxArgc);

	if(srcIndex >= srcArgc) //All src args processed
	{
		dstArgc = dstIndex;
		break;
	}
  }

  return;
}

//==========================================================================================
//_MSL_IMP_EXP_C extern int ccommand(char ***);

#define getNextArgStr( _str_, _str_err_str_ ) \
		argn++; \
		arg = arrayArgc[argn]; \
		if( nil == arg ) \
		{ \
			fprintf( stderr, "Expected " _str_err_str_ " got end of args\n" ); \
			err = -1; \
			goto usageError; \
		} \
		if( arg[0] == '-' ) \
		{ \
			fprintf( stderr, "Expected " _str_err_str_ " next arg\n" ); \
			err = -1; \
			goto usageError; \
		} \
		strcpy(*(_str_), arg);


#if !STAND_ALONE_APP
int main(int argc, char *argv[]);
int main(int argc, char *argv[])
{
#else
int main(void);
int main(void)
{
	char *argv[] = {
		"ValidateMP4",
		"<mpeg4-file-path>"
		};
	int argc = sizeof(argv)/sizeof(char*);
#endif
	int argn;
	int gotInputFile = false;
	bool gotSegmentInfoFile = false;
	bool gotleafInfoFile = false;
	bool gotOffsetFile = false;
	bool logConsole = false;
	int err;
	char gInputFileFullPath[1024];
	char leafInfoFileName[1024];
	char offsetsFileName[1024];
	char sapType[1024];
	char temp[1024];
	int usedefaultfiletype = true;

	FILE *infile = nil;
	atomOffsetEntry aoe = {};

	vg.warnings = true;
//	vg.qtwarnings = true;
//	vg.print_atompath = true;
//	strcpy( vg.atompath, "moov-1:trak-1:mdia-1:minf-1:stbl-1:stsd-1" );
//	vg.print_atom = true;
//	vg.print_fulltable = true;
//	vg.print_sample = true;
//	vg.print_sampleraw = true;
//	vg.print_hintpayload = true;
	//vg.visualProfileLevelIndication = 255;
	// this is simply the wrong place for this;  it's not a program parameter, it's the mpeg-4
	//   profile/level indication as found in the video stream.
	// But neither movie info nor track info are available at the right points.  Ugh [dws]

	vg.checkSegAlignment = false;
	vg.checkSubSegAlignment = false;
	vg.minBufferTime = -1;
	vg.bandwidth = -1;
	vg.width = 0;
	vg.height = 0;
	vg.sarx = 1;
	vg.sary = 1;
	vg.framerate = 0;
	vg.codecprofile = 0;
	vg.audioChValue = 0;
	vg.suggestBandwidth = false;
	vg.isoLive = false;
	vg.isoondemand = false;
	vg.dynamic = false;
	vg.isomain = false;
	vg.bss = false;
	vg.subRepLevel = false;
	vg.startWithSAP = -1;
	vg.dash264base = false;
	vg.dashifbase = false;
	vg.dash264enc = false;
	vg.dashifondemand = false;
	vg.dashifmixed = false;
	vg.RepresentationIndex = false;
	vg.numOffsetEntries = 0;
	vg.lowerindexRange=-1;
	vg.higherindexRange=-1;
	vg.atomxml=false;
	vg.cmaf=false;
	vg.dvb=false;
	vg.hbbtv=false;
	vg.ctawave=false;
	memset(&vg.indexRange, 0, sizeof(vg.indexRange));
	vg.pssh_count = 0;
	vg.sencFound=false;
	vg.suppressAtomLevel=false;

	int boxCount = 0;
	char ** arrayArgc;
	int uArgc;
	expandArgv(argc,argv,uArgc,arrayArgc);


	fprintf (stdout, "<%s> : argc %d\n", __FUNCTION__, argc);
	for (int i=0; i < argc; i++)
	{
		fprintf (stdout, "<%s> : argv[%d] <%s>\n", __FUNCTION__,  i, argv[i]);
	}

	//return (0);

	// Check the parameters
	for( argn = 1; argn < uArgc ; argn++ )
	{
		const char *arg = arrayArgc[argn];		 //instead of reading from argv[], now read from array
		//const char * arg=argv[argn];

		if( '-' != arg[0] )
		{
			char *extensionstartp = nil;

			if (gotInputFile) {
				fprintf( stderr, "Unexpected argument \"%s\"\n", arg );
				err = -1;
				goto usageError;
			}
			strcpy(gInputFileFullPath, arg);
			gotInputFile = true;

#ifdef USE_STRCASECMP
	#define rStrCaseCmp(a,b)		strcasecmp(a,b)
#else
	#define rStrCaseCmp(a,b)		my_stricmp(a,b)
#endif
			extensionstartp = strrchr(gInputFileFullPath,'.');
			if (extensionstartp) {
				if (rStrCaseCmp(extensionstartp,".mp4") == 0) {
					vg.filetype = filetype_mp4;
					usedefaultfiletype = false;
				}
			}

			continue;
		}

		arg++;	// skip '-'

		if( keymatch( arg, "help", 1 ) ) {
			goto usageError;
		} else if( keymatch( arg, "warnings", 1 ) ) {
			vg.warnings = true;
		} else if ( keymatch( arg, "filetype", 1 ) ) {
			getNextArgStr( &vg.filetypestr, "filetype" );
		} else if ( keymatch( arg, "atompath", 1 ) ) {
			getNextArgStr( &vg.atompath, "atompath" );
		} else if ( keymatch( arg, "checklevel", 1 ) ) {
			getNextArgStr( &vg.checklevelstr, "checklevel" );
		} else if ( keymatch( arg, "printtype", 1 ) ) {
			getNextArgStr( &vg.printtypestr, "printtype" );
		} else if ( keymatch( arg, "infofile", 1 ) ) {
				getNextArgStr( &vg.segmentOffsetInfo, "infofile" ); gotSegmentInfoFile = true;
		} else if ( keymatch( arg, "segal", 5 ) ) {
				vg.checkSegAlignment = true;
		} else if ( keymatch( arg, "ssegal", 6 ) ) {
			vg.checkSubSegAlignment = true;
		} else if ( keymatch( arg, "minbuffertime", 13 ) ) {
			getNextArgStr( &temp, "minbuffertime" ); vg.minBufferTime = atof(temp);
		} else if ( keymatch( arg, "bandwidth", 9 ) ) {
			getNextArgStr( &temp, "bandwidth" ); vg.bandwidth = atoi(temp);
		} else if ( keymatch( arg, "sbw", 3 ) ) {
				vg.suggestBandwidth = true;
		} else if ( keymatch( arg, "isolive", 7 ) ) {
				vg.isoLive = true;
		} else if ( keymatch( arg, "isoondemand", 7 ) ) {
				vg.isoondemand = true;
		} else if ( keymatch( arg, "isomain", 7 ) ) {
				vg.isomain = true;
		} else if ( keymatch( arg, "dynamic", 7 ) ) {
				vg.dynamic = true;
		} else if ( keymatch( arg, "indexrange", 10 ) ) {
				getNextArgStr( &vg.indexRange, "indexrange" );
		} else if ( keymatch( arg, "level", 5 ) ) {
				vg.subRepLevel = true;
		} else if ( keymatch( arg, "startwithsap", 6 ) ) {
				getNextArgStr( &sapType, "startwithsap" );vg.startWithSAP = atoi(sapType);
		} else if ( keymatch( arg, "bss", 3 ) ) {
				vg.bss = true; vg.checkSegAlignment = true; //The conditions required for setting the @segmentAlignment attribute to a value other than 'false' for the Adaptation Set are fulfilled.
		} else if ( keymatch( arg, "leafinfo", 8 ) ) {
				getNextArgStr( &leafInfoFileName, "leafinfo" ); gotleafInfoFile = true;
		} else if ( keymatch( arg, "offsetinfo", 9 ) ) {
				getNextArgStr( &offsetsFileName, "offsetinfo" ); gotOffsetFile = true;
		} else if (keymatch(arg, "logconsole", 10)) {
			logConsole = true;
		} else if ( keymatch( arg, "dash264base", 11 ) ) {
				vg.dash264base = true;
		} else if ( keymatch( arg, "dashifbase", 10 ) ) {
				vg.dashifbase = true;
		} else if ( keymatch( arg, "dash264enc", 10 ) ) {
				vg.dash264enc = true;
		} else if ( keymatch( arg, "dashifondemand", 10 ) ) {
				vg.dashifondemand = true;
		} else if ( keymatch( arg, "dashifmixed", 10 ) ) {
				vg.dashifmixed = true;
		} else if ( keymatch( arg, "repindex", 8 ) ) {
				vg.RepresentationIndex = true;
		} else if ( keymatch( arg, "samplenumber", 1 ) ) {
			getNextArgStr( &vg.samplenumberstr, "samplenumber" );

		} else if ( keymatch( arg, "width", 5 ) ) {
						  getNextArgStr( &temp, "width" ); vg.width = atoi(temp);
				} else if ( keymatch( arg, "height", 6 ) ) {
						  getNextArgStr( &temp, "height" ); vg.height = atoi(temp);
		} else if( keymatch( arg, "sarx", 4 ) ) {
					getNextArgStr( &temp, "sarx" ); vg.sarx = atoi(temp);
				} else if( keymatch( arg, "sary", 4 ) ) {
					getNextArgStr( &temp, "sary" ); vg.sary = atoi(temp);
				} else if ( keymatch( arg, "framerate", 9 ) ) {
						  getNextArgStr( &temp, "framerate" );
						  if(strstr(temp, "/")){
							  char * pch;
							  pch = strstr(temp, "/");
							  strncpy (pch," ",1);
							  puts(temp);

							  char * pEnd;
							  vg.framerate = strtof(temp, &pEnd)/strtof(pEnd, NULL);
						  }
						  else{
							  vg.framerate = strtof(temp, NULL);
						  }
				} else if ( keymatch( arg, "codecs", 6 ) ) {
					getNextArgStr( &vg.codecs, "codecs" );
				} else if ( keymatch( arg, "codecprofile", 12 ) ) {
						  getNextArgStr( &temp, "codecprofile" ); vg.codecprofile = atoi(temp);
				} else if ( keymatch( arg, "codeclevel", 10 ) ) {
						  getNextArgStr( &temp, "codeclevel" ); vg.codeclevel = atoi(temp);
				} else if ( keymatch( arg, "codectier", 9 ) ) {
						  getNextArgStr( &temp, "codectier" );
						  if(strstr(temp, "L"))
							  vg.codectier = 0;
						  else if(strstr(temp, "H"))
							  vg.codectier = 1;
				} else if ( keymatch( arg, "audiochvalue", 12 ) ) {
						 getNextArgStr( &temp, "audiochvalue" ); vg.audioChValue = atoi(temp);

		} else if ( keymatch( arg, "default_kid", 11 ) ) { //Related to the case of encrypted content.
						 getNextArgStr( &vg.default_KID, "default_kid" );

		}else if ( keymatch( arg, "pssh_count", 10 ) ) { //Related to the case of encrypted content.
						 getNextArgStr( &temp, "pssh_count" ); vg.pssh_count=atoi(temp);


		}else if ( keymatch( arg, "psshbox", 7 ) ) { //Related to the case of encrypted content.
						 getNextArgStr( &temp, "psshbox" );
			 vg.psshfile[boxCount++]=temp;


		} else if ( keymatch( arg, "atomxml", 1)) {
			 vg.atomxml = true;
		} else if ( keymatch( arg, "cmaf", 1)) {
			 vg.cmaf = true;
		} else if ( keymatch( arg, "dvb", 1)) {
						 vg.dvb = true;
				} else if ( keymatch( arg, "hbbtv", 1)) {
						 vg.hbbtv = true;
				} else if ( keymatch( arg, "ctawave", 1)) {
						vg.ctawave = true;
				} else if ( keymatch( arg, "dashifll", 1)) {
						vg.dashifll = true;
				} else if ( keymatch( arg, "inbandeventstreamll", 1)) {
						vg.inbandeventstreamll = true;
				} else if ( keymatch( arg, "suppressatomlevel", 1)) {
					vg.suppressAtomLevel = true;
				} else {
			fprintf( stderr, "Unexpected option \"%s\"\n", arg);
			err = -1;
			goto usageError;
		}
	}


	for(int i = 0; i < uArgc; i++)
	{
	  char * currentPtr = arrayArgc[i];
	  free(currentPtr);			//free the memory allocated by malloc in doubleduplicateArgv
	}

	free(arrayArgc);


	if (vg.indexRange[0] != 0)
	  if (2 != sscanf(vg.indexRange,"%d-%d",&vg.lowerindexRange,&vg.higherindexRange))
	  {
		fprintf(stderr, "Error parsing range \"%d-%d\" from \"%s\"!\n", vg.lowerindexRange, vg.higherindexRange, vg.indexRange);
		vg.lowerindexRange = vg.higherindexRange = -1; //reset
	  }


	//=====================
	// Process input parameters

	if (logConsole)
	{
		FILE * tempfp = freopen("stdout.txt", "w", stdout);
		if (tempfp == NULL)
			fprintf(stderr, "Error creating redirect file stdout.txt!\n");

		tempfp = freopen("stderr.txt", "w", stderr);
		if (tempfp == NULL)
			fprintf(stderr, "Error creating redirect file stderr.txt!\n");
	}

	if ((usedefaultfiletype && (vg.filetypestr[0] == 0)) ||				// default to mp4
			  (strcmp(vg.filetypestr, "mp4") == 0)) {
		vg.filetype = filetype_mp4;
	} else if (strcmp(vg.filetypestr, "mp4v") == 0) {
		vg.filetype = filetype_mp4v;
	} else if (vg.filetype == 0) {
		fprintf( stderr, "Invalid filetype\n" );
		err = -1;
		goto usageError;
	}

	if (vg.checklevelstr[0] == 0) {
		vg.checklevel = 1;				// default
	} else {
		vg.checklevel = atoi(vg.checklevelstr);
		if (vg.checklevel < 1) {
			fprintf( stderr, "Invalid check level\n" );
			goto usageError;
		}
	}

	if (vg.printtypestr[0] == 0) {
		// default is not to print anything
	} else {
		char instr[256];
		char *tokstr;

		strcpy(instr, vg.printtypestr);
		tokstr = strtok(instr,"+");
		while (tokstr) {
			if		(keymatch(tokstr, "atompath", 5)) {
				vg.print_atompath = true;
			} else if (keymatch(tokstr, "atom", 4)) {
				vg.print_atom = true;
			} else if (keymatch(tokstr, "fulltable", 1)) {
				vg.print_fulltable = true;
			} else if (keymatch(tokstr, "sampleraw", 7)) {
				vg.print_sampleraw = true;
			} else if (keymatch(tokstr, "sample", 6)) {
				vg.print_sample = true;
			} else if (keymatch(tokstr, "hintpayload", 1)) {
				vg.print_hintpayload = true;
			} else {
				fprintf( stderr, "Invalid print type option\n" );
				goto usageError;
			}
			tokstr = strtok(nil,"+");
		}
	}

	if((vg.minBufferTime == -1) != (vg.bandwidth == -1))
	{
		fprintf( stderr, "minBufferTime and bandwidth must be provided together as options!\n" );
		goto usageError;
	}
	if((vg.width == 0) != (vg.height == 0))
	{
		fprintf( stderr, "width and height must be provided together as options!\n" );
		goto usageError;
	}

	if (vg.samplenumberstr[0] == 0) {
		vg.samplenumber = 0;			// zero means print them all if you print any
	} else {
		vg.samplenumber = atoi(vg.samplenumberstr);
		if (vg.samplenumber < 1) goto usageError;
	}

	//=====================

	if (!gotInputFile) {
		err = -1;
		fprintf( stderr, "No input file specified\n" );
		goto usageError;
	}

	infile = fopen(gInputFileFullPath, "rb");
	if (!infile) {
		err = -1;
		fprintf( stderr, "Could not open input file \"%s\"\n", gInputFileFullPath );
		goto usageError;
	}

	fprintf(stdout,"\n\n\n<!-- Source file is '%s' -->\n", gInputFileFullPath);

	if(vg.atomxml){
		f = fopen("atominfo.xml", "w");
	}

	if (gotOffsetFile)
		loadOffsetInfo(offsetsFileName);

	vg.inFile = infile;
	vg.inOffset = 0;
	err = fseek(infile, 0, SEEK_END);
	if (err) goto bail;
	vg.inMaxOffset = inflateOffset(ftell(infile));
	if (vg.inMaxOffset < 0) {
		err = vg.inMaxOffset;
		goto bail;
	}

	aoe.type = 'file';
	aoe.size = vg.inMaxOffset;
	aoe.offset = 0;
	aoe.atomStartSize = 0;
	aoe.maxOffset = aoe.size;

	vg.fileaoe = &aoe;		// used when you need to read file & size from the file

	if(gotSegmentInfoFile)
	{
		int numSegments = 0;

		for(int ii = 0 ; ii < 2 ; ii++)
		{
			FILE *segmentOffsetInfoFile = fopen(vg.segmentOffsetInfo, "rb");
			if (!segmentOffsetInfoFile) {
				err = -1;
				fprintf( stderr, "Could not open segment info file \"%s\"\n", vg.segmentOffsetInfo );
				goto usageError;
			}

			if(ii == 1)
			{
				vg.segmentSizes = (UInt64 *)malloc(sizeof(UInt64)*numSegments);
				vg.segmentInfoSize = numSegments;
				vg.simsInStyp = (bool *)malloc(sizeof(bool)*numSegments);
				vg.psshFoundInSegment = (bool *)malloc(sizeof(bool)*numSegments);
				vg.tencFoundInSegment = (bool *)malloc(sizeof(bool)*numSegments);
				vg.dsms = (bool *)malloc(sizeof(bool)*numSegments);
			}

			numSegments = 0;

			while(1)
			{
				int temp1;
				UInt64 temp2;
				int ret = fscanf(segmentOffsetInfoFile,"%d %ld\n",&temp1,&temp2);
				if(ret < 2)
					break;

				if(ii == 1)
				{
					vg.segmentSizes[numSegments] = temp2;
					vg.simsInStyp[numSegments] = false;
					vg.psshFoundInSegment[numSegments] = false;
					vg.tencFoundInSegment[numSegments] = false;
					vg.dsms[numSegments] = false;
				}
				numSegments++;
				if(numSegments == 1 && temp1 > 0)
					vg.initializationSegment=false;
				else
					vg.initializationSegment=true;

			}

			if(numSegments == 0)
				{
					err = -1;
					fprintf( stderr, "Empty segment info file \"%s\"\n", vg.segmentOffsetInfo );
					goto usageError;
				}

			fclose(segmentOffsetInfoFile);
		}
		vg.dashSegment = true;	//Either this, or for non-segmented file = self-intializing segment, brand DASH shall be in ftyp, or use another dash-specific brand to initialize this
	}
	else
	{
		vg.segmentSizes = (UInt64 *)malloc(sizeof(UInt64)*1);
		vg.segmentInfoSize = 1;
		vg.initializationSegment=false;
		vg.segmentSizes[0] = aoe.size;
		vg.simsInStyp = (bool *)malloc(sizeof(bool)*1);
		vg.simsInStyp[0] = false;
		vg.psshFoundInSegment = (bool *)malloc(sizeof(bool)*1);
		vg.psshFoundInSegment[0] = false;
		vg.tencFoundInSegment = (bool *)malloc(sizeof(bool)*1);
		vg.tencFoundInSegment[0] = false;
		vg.dsms = (bool *)malloc(sizeof(bool)*1);
		vg.dsms[0] = false;
		vg.dashSegment = false;
	}

	vg.psshInInit = false;
	vg.tencInInit = false;
	vg.processedStypes = 0;
	vg.accessUnitDurationNonIndexedTrack = 0;

	if(vg.checkSegAlignment || vg.checkSubSegAlignment || vg.bss)
	{
		if(gotleafInfoFile)
			loadLeafInfo(leafInfoFileName);
		else
		{
			printf("Segment/Subsegment alignment check request, leaf info file not found!\n");
			vg.checkSegAlignment = vg.checkSubSegAlignment = false;
		}
	}

	if (vg.filetype == filetype_mp4v) {
		err = ValidateElementaryVideoStream( &aoe, nil );
	} else {
		err = ValidateFileAtoms( &aoe, nil );
		fprintf(stdout,"<!#- Finished testing file '%s' -->\n", gInputFileFullPath);
	}

	goto bail;

	//=====================

usageError:
	fprintf( stderr, "Usage: %s [-filetype <type>] "
								"[-printtype <options>] [-checklevel <level>] [-infofile <Segment Info File>] [-leafinfo <Leaf Info File>] [-segal] [-ssegal] [-startwithsap TYPE] [-level] [-bss] [-isolive] [-isoondemand] [-isomain] [-dynamic] [-dash264base] [-dashifbase] [-dash264enc] [-dashifondemand] [-dashifmixed] [-dashifll] [-repindex] [-atomxml] [-cmaf] [-dvb] [-hbbtv] [-ctawave] [-suppressatomlevel]", "ValidateMP4" );
	fprintf( stderr, " [-samplenumber <number>] [-verbose <options>] [-offsetinfo <Offset Info File>] [-logconsole ] [-help] inputfile\n" );
	fprintf( stderr, "	-a[tompath]	  <atompath> - limit certain operations to <atompath> (e.g. moov-1:trak-2)\n" );
	fprintf( stderr, "					 this effects -checklevel and -printtype (default is everything) \n" );
	fprintf( stderr, "	-p[rinttype]	 <options> - controls output (combine options with +) \n" );
	fprintf( stderr, "					 atompath - output the atompath for each atom \n" );
	fprintf( stderr, "					 atom - output the contents of each atom \n" );
	fprintf( stderr, "					 fulltable - output those long tables (e.g. samplesize tables)  \n" );
	fprintf( stderr, "					 sample - output the samples as well \n" );
	fprintf( stderr, "								 (depending on the track type, this is the same as sampleraw) \n" );
	fprintf( stderr, "					 sampleraw - output the samples in raw form \n" );
	fprintf( stderr, "					 hintpayload - output payload for hint tracks \n" );
	fprintf( stderr, "	-c[hecklevel]	<level> - increase the amount of checking performed \n" );
	fprintf( stderr, "					 1: check the moov container (default -atompath is ignored) \n" );
	fprintf( stderr, "					 2: check the samples \n" );
	fprintf( stderr, "					 3: check the payload of hint track samples \n" );
	fprintf( stderr, "	-infofile		<Segment Info File> - Offset file generated by assembler \n" );
	fprintf( stderr, "	-leafinfo		 <Leaf Info File> - Information file generated by this software (named leafinfo.txt) for another representation, provided to run for cross-checks of alignment\n" );
	fprintf( stderr, "	-segal  -		 Check Segment alignment based on <Leaf Info File>\n" );
	fprintf( stderr, "	-ssegal -		 Check Subegment alignment based on <Leaf Info File>\n" );
	fprintf( stderr, "	-bandwidth		For checking @bandwidth/@minBufferTime\n" );
	fprintf( stderr, "	-minbuffertime	For checking @bandwidth/@minBufferTime\n" );
	fprintf( stderr, "	-width			For checking width\n" );
	fprintf( stderr, "	-height		   For checking height\n" );
	fprintf( stderr, "	-sbw			  Suggest a good @bandwidth if the one provided is non-conforming\n" );
	fprintf( stderr, "	-isolive		  Make checks specific for media segments conforming to ISO Base media file format live profile\n" );
	fprintf( stderr, "	-isoondemand	  Make checks specific for media segments conforming to ISO Base media file format On Demand profile\n" );
	fprintf( stderr, "	-isomain		  Make checks specific for media segments conforming to ISO Base media file format main profile\n" );
	fprintf( stderr, "	-dynamic		  MPD type=dynamic\n" );
	fprintf( stderr, "	-startwithsap	 Check for a specific SAP type as announced in the MPD\n" );
	fprintf( stderr, "	-level			SubRepresentation@level checks\n" );
	fprintf( stderr, "	-bss			  Make checks specific for bitstream switching\n" );
	fprintf( stderr, "	-dash264base	  Make checks specific for DASH264 Base IOP\n" );
	fprintf( stderr, "	-dashifbase	   Make checks specific for DASHIF Base IOP\n" );
	fprintf( stderr, "	-dash264enc	   Make checks specific for encrypted DASH264 content\n" );
		fprintf( stderr, "	-dashifondemand   Make checks specific for encrypted DASH-IF IOP On Demand content\n" );
	fprintf( stderr, "	-dashifmixed	  Make checks specific for encrypted DASH-IF IOP Mixed On Demand content\n" );
		fprintf( stderr, "	-dashifll		 Make checks specific for Low Latency DASH-IF content\n" );
	fprintf( stderr, "	-repindex		 Make checks specific for @RepresentationIndex");
	fprintf( stderr, "	-indexrange	   Byte range where sidx is expected\n");
	fprintf( stderr, "	-width			Expected width of the video track\n");
	fprintf( stderr, "	-height		   Expected height of the video track\n");
		fprintf( stderr, "	-framerate		Expected framerate of the video track\n");
		fprintf( stderr, "	-codecprofile	 Expected codec profile of the video track\n");
		fprintf( stderr, "	-codectier		Expected codec tier of the video track\n");
		fprintf( stderr, "	-codeclevel	   Expected codec level of the video track\n");
	fprintf( stderr, "	-default_kid	  Expected default_KID for the mp4 content protection\n");
	fprintf( stderr, "	-s[amplenumber]   <number> - limit sample checking or printing operations to sample <number> \n" );
	fprintf( stderr, "					  most effective in combination with -atompath (default is all samples) \n" );
	fprintf( stderr, "	-offsetinfo	   <Offset Info File> - Partial file optimization information file: if the file has several byte ranges removed, this file provides the information as offset-bytes removed pairs\n");
	fprintf( stderr, "	-logconsole	   Redirect stdout and stderr to stdout.txt and stderr.txt, respectively \n");
	fprintf( stderr, "	-atomxml		  Output the contents of each atom into an xml \n" );
	fprintf( stderr, "	-cmaf			 Check for CMAF conformance \n" );
		fprintf( stderr, "	-dvb			  Check for DVB conformance \n" );
		fprintf( stderr, "	-hbbtv			Check for HbbTV conformance \n" );
		fprintf( stderr, "	-ctawave		  Check for CTA WAVE conformance \n" );
		fprintf( stderr, "	-suppressatomlevel   For suppressing atom level information in error messages \n" );
	fprintf( stderr, "	-h[elp] - print this usage message \n" );


	//=====================

bail:
	if (infile) {
		fclose(infile);
	}
	if (logConsole)
	{
		fclose(stdout);
		fclose(stderr);
	}
	if(vg.atomxml){
		if (f)
		{
			fclose(f);
		}
	}

	return err;
}

void loadLeafInfo(char *leafInfoFileName)
{
	FILE *leafInfoFile = fopen(leafInfoFileName,"rt");
	if(leafInfoFile == NULL)
	{
		printf("Leaf info file %s not found, alignment wont be checked!\n",leafInfoFileName);
		vg.checkSegAlignment = vg.checkSubSegAlignment = false;
		vg.bss = false;
		return;
	}

	fscanf(leafInfoFile,"%u\n",&vg.accessUnitDurationNonIndexedTrack);

	fscanf(leafInfoFile,"%u\n",&vg.numControlTracks);

	vg.controlLeafInfo = (LeafInfo **)malloc(vg.numControlTracks*sizeof(LeafInfo *));
	vg.numControlLeafs = (unsigned int *)malloc(vg.numControlTracks*sizeof(unsigned int));
	vg.trackTypeInfo = (TrackTypeInfo *)malloc(vg.numControlTracks*sizeof(TrackTypeInfo));

	for(unsigned int i = 0 ; i < vg.numControlTracks ; i++)
	{
		fscanf(leafInfoFile,"%u %u\n",&vg.trackTypeInfo[i].track_ID,&vg.trackTypeInfo[i].componentSubType);
	}

	for(unsigned int i = 0 ; i < vg.numControlTracks ; i++)
	{
		fscanf(leafInfoFile,"%u\n",&(vg.numControlLeafs[i]));

		vg.controlLeafInfo[i] = (LeafInfo *)malloc(vg.numControlLeafs[i]*sizeof(LeafInfo));

		for(UInt32 j = 0 ; j < vg.numControlLeafs[i] ; j++)
			fscanf(leafInfoFile,"%d %Lf %Lf\n",(int *)&vg.controlLeafInfo[i][j].firstInSegment,&vg.controlLeafInfo[i][j].earliestPresentationTime,&vg.controlLeafInfo[i][j].lastPresentationTime);

	}

	fclose(leafInfoFile);
}

void loadOffsetInfo(char *offsetsFileName)
{
	FILE *offsetsFile = fopen(offsetsFileName,"rt");
	if(offsetsFile == NULL)
	{
		printf("Offset info file %s not found, exiting!\n",offsetsFileName);
		exit(-1);
	}

	int numEntries = 0;
	UInt64 dummy1, dummy2;

	while(1)
	{
		int ret = fscanf(offsetsFile,"%lu %lu\n",&dummy1,&dummy2);
		if(ret > 2)
		{
			printf("%d entries found on entry number %d, improper offset info file, exiting!\n",ret,numEntries+1);
			exit(-1);
		}
		if(ret < 2)
			break;
		numEntries ++;
	}

	if(numEntries == 0)
	{
		printf("No valid entries found in offset info file, exiting!\n");
		exit(-1);
	}
	vg.numOffsetEntries = numEntries;

	vg.offsetEntries = (OffsetInfo *)malloc(vg.numOffsetEntries*sizeof(OffsetInfo));
	if(vg.offsetEntries == NULL)
	{
		printf("Failure to allocate %d offset entries, exiting!\n",vg.numOffsetEntries);
		exit(-1);
	}

	rewind(offsetsFile);

	for(unsigned int index = 0 ; index < vg.numOffsetEntries ; index ++)
	{
		fscanf(offsetsFile,"%lu %lu\n",&vg.offsetEntries[index].offset,&vg.offsetEntries[index].sizeRemoved);
		index = index;
	}

	fclose(offsetsFile);
}

//==========================================================================================

#include <stdarg.h>
// change here if you want to send both types of output to stdout to get interleaved output
#if 1
	#define _stdout stdout
	#define _stderr stderr
#else
	#define _stdout stdout
	#define _stderr stdout
#endif

void toggleprintatom( Boolean onOff )
{

	// need hackaround to print certain things - can't figure out arg scheme
	// true is on, false is off
	vg.printatom = onOff;
//	if( vg.printatom )
//		fprintf( _stdout, "--> turning ON vg.printatom \n");
//	else
//		fprintf( _stdout, "--> turning OFF vg.printatom \n" );

}

void toggleprintatomdetailed( Boolean onOff )
{

	// need hackaround to print certain things - can't figure out arg scheme
	// true is on, false is off
	vg.printatom = onOff;
	vg.print_fulltable = onOff;
//	if( vg.printatom )
//		fprintf( _stdout, "--> turning ON vg.printatom and vg.print_fulltable \n" );
//	else
//		fprintf( _stdout, "--> turning OFF vg.printatom and vg.print_fulltable \n" );

}

void toggleprintsample( Boolean onOff )
{

	// need hackaround to print certain things - can't figure out arg scheme
	// true is on, false is off
	vg.printsample = onOff;

}

void atomprinttofile(const char* formatStr, va_list ap)
{
	vfprintf (f, formatStr, ap);
}

void atomprintnotab(const char *formatStr, ...)
{
	va_list 		ap;
	va_start(ap, formatStr);

	if (vg.printatom) {
		vfprintf( _stdout, formatStr, ap );
	}

	if(vg.atomxml){
		va_start(ap, formatStr);
		atomprinttofile(formatStr, ap);
		va_end(ap);
	}

	va_end(ap);
}

void atomtable_begin ( const char *name )
{
	if (vg.tabcnt != 0)
	{
		atomprint("<\n");
	}
	atomprint("<%s>\n", name);
	vg.tabcnt++;
}

void atomtable_end ( const char *name )
{
	vg.tabcnt++;
	atomprint("<\%s>\n", name);
}


void atomprint(const char *formatStr, ...)
{
	va_list 		ap;
	va_start(ap, formatStr);

	if (vg.printatom) {
		printf ("vg.printatom\n");
		SInt32 tabcnt = vg.tabcnt;
		while (tabcnt-- > 0) {
			fprintf(_stdout,myTAB);
		}
		vfprintf( _stdout, formatStr, ap );
	}

	if(vg.atomxml){
		SInt32 tabcnt = vg.tabcnt;
 		while (tabcnt-- > 0) {
 			fprintf(f,myTAB);
 		}
		va_start(ap, formatStr);
		atomprinttofile(formatStr, ap);
		va_end(ap);
	}

	va_end(ap);
}

void atomprinthexdata(char *dataP, UInt32 size)
{
	char hexstr[4] = "12 ";
	int widthCnt = 0;
	char c;
	static char hc[16] = {'0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F'};

	while (size) {
		c = *dataP++;
		hexstr[0] = hc[(c>>4)&0x0F];
		hexstr[1] = hc[(c   )&0x0F];
		if (widthCnt == 0) {
			atomprint(hexstr);
		} else {
			atomprintnotab(hexstr);
		}
		if (++widthCnt >= 16) {
			widthCnt = 0;
			atomprint("\n");
		}
		size--;
	}
	if (widthCnt != 0)
		atomprint("\n");
}



void atomprintdetailed(const char *formatStr, ...)
{
	va_list 		ap;
	va_start(ap, formatStr);

	if (vg.printatom && vg.print_fulltable) {
		SInt32 tabcnt = vg.tabcnt;
		while (tabcnt-- > 0) {
			fprintf(_stdout,myTAB);
		}
		vfprintf( _stdout, formatStr, ap );
	}

	va_end(ap);
}

void sampleprint(const char *formatStr, ...)
{
	va_list 		ap;
	va_start(ap, formatStr);

	if (vg.printsample) {
		SInt32 tabcnt = vg.tabcnt;
		while (tabcnt-- > 0) {
			fprintf(_stdout,myTAB);
		}
		vfprintf( _stdout, formatStr, ap );
	}

	va_end(ap);
}

void sampleprintnotab(const char *formatStr, ...)
{
	va_list 		ap;
	va_start(ap, formatStr);

	if (vg.printsample) {
		vfprintf( _stdout, formatStr, ap );
	}

	va_end(ap);
}

void sampleprinthexdata(char *dataP, UInt32 size)
{
	char hexstr[4] = "12 ";
	int widthCnt = 0;
	char c;
	static char hc[16] = {'0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F'};

	while (size) {
		c = *dataP++;
		hexstr[0] = hc[(c>>4)&0x0F];
		hexstr[1] = hc[(c   )&0x0F];
		if (widthCnt == 0) {
			sampleprint(hexstr);
		} else {
			sampleprintnotab(hexstr);
		}
		if (++widthCnt >= 16) {
			widthCnt = 0;
			sampleprint("\n");
		}
		size--;
	}
	if (widthCnt != 0)
		sampleprint("\n");
}


void sampleprinthexandasciidata(char *dataP, UInt32 size)
{
	char hexstr[4] = "12 ";
	char asciiStr[17];
	int widthCnt = 0;
	char threeSpaces[4] = "   ";
	char c;
	static char hc[16] = {'0','1','2','3','4','5','6','7','8','9','A','B','C','D','E','F'};


	// similar to sampleprinthexdata() but also prints ascii characters to the right of hex dump
	//   (ala Mac OS X's HexDump or 9's MacsBug; if the character is not ascii, it will print a '.' )
	// sampleprintnotab("\n");

	asciiStr[16] = 0;
	while (size) {
		c = *dataP++;
		hexstr[0] = hc[(c>>4)&0x0F];
		hexstr[1] = hc[(c   )&0x0F];

		if( isprint( c ) && c != 0 )
				{
			asciiStr[ widthCnt ] = c ;
						// When *dataP contains something like "...%LS...." , then some compilers throw errors because it looks like format specifier.
						// Hence this case is fixed here.
						if(c == '%')
							asciiStr[ widthCnt ] = 'p' ;
				}
		else
			asciiStr[ widthCnt ] = '.';

		if (widthCnt == 0) {
			sampleprint(hexstr);
		} else {
			sampleprintnotab(hexstr);
		}
		if (++widthCnt >= 16) {
			sampleprintnotab( threeSpaces );  // some space between hex chars and ascii chars
			sampleprintnotab( asciiStr );  // prints the ascii characters to the right of hex dump

			widthCnt = 0;
			sampleprintnotab("\n");
		}

		size--;
	}
	if (widthCnt != 0){

			// for the last line, fill out the rest of the hex row with blanks
			//   and fill the unused right end of asciiStr with blanks
		while( widthCnt < 16 ){
			sampleprintnotab( threeSpaces );
			asciiStr[ widthCnt++ ] = ' ';
		}


		sampleprintnotab( threeSpaces );
		sampleprintnotab( asciiStr );
		sampleprintnotab("\n");
	}

	// sampleprintnotab("\n");

}


void _warnprint(const char *formatStr, ...)
{
	va_list 		ap;
	va_start(ap, formatStr);

	if (vg.warnings)
		vfprintf( _stderr, formatStr, ap );

	va_end(ap);
}


void _errprint(const char *formatStr, ...)
{
	va_list 		ap;
	va_start(ap, formatStr);

		if(vg.suppressAtomLevel){
			fprintf( _stderr, "### error:\r###");
		}
		else{
			fprintf( _stderr, "### error: %s \r###		",vg.curatompath);
		}
	vfprintf( _stderr, formatStr, ap );

	va_end(ap);
}

void bailprint(const char *level, OSErr errcode)
{
	switch(errcode){
		case -50:
			errprint("%s: Parameter-related error (out-of-range value, non-conformant type, etc.) for attribute validation\n", level);
			break;
		case -2019:
			errprint("%s: Memory allocation error encountered in attribute validation\n", level);
			break;
		case -2020:
			errprint("%s: Not enough bits left in the bitstream for further attribute validation\n", level);
			break;
		case -2021:
			errprint("%s: Too many bits left in the bitstream after the complete validation\n", level);
			break;
		case -2022:
			errprint("%s: Cannot handle the bad attribute length for attribute validation\n", level);
			break;
		case -2023:
			errprint("%s: Bad attribute size for attribute validation\n", level);
			break;
		case -2024:
			errprint("%s: Bad attribute value for attribute validation\n", level);
			break;
		default:
			errprint("%s: %d\n",level, errcode);
			break;
	}
}

int my_stricmp(const char* p, const char* q)
{
	while (tolower(*p) == tolower(*q) && *p && *q)
	{
		p++;
		q++;
	}

	return tolower(*p) - tolower(*q);
}

int mapStringToUInt32(char *src, UInt32 *target)
{
	if(src == NULL || target == NULL)
	{
		fprintf(stderr, "mapStringToUInt32: NULL pointer exception");
		return -1;
	}

	char *tmp = (char *)target;

	//Write in reverse (big-endian) format)

	tmp[0] = src[3];
	tmp[1] = src[2];
	tmp[2] = src[1];
	tmp[3] = src[0];

	return 0;

}

char *ostypetostr(UInt32 num)
{
	static char str[sizeof(num)+1] = {};

	str[0] = (num >> 24) & 0xff;
	str[1] = (num >> 16) & 0xff;
	str[2] = (num >>  8) & 0xff;
	str[3] = (num >>  0) & 0xff;

	return str;
}

char *ostypetostr_r(UInt32 num, char * buffer)
{
	buffer[0] = (num >> 24) & 0xff;
	buffer[1] = (num >> 16) & 0xff;
	buffer[2] = (num >>  8) & 0xff;
	buffer[3] = (num >>  0) & 0xff;
	buffer[4] = 0;

	return buffer;
}

//  careful about using more than one call to this in the same print statement, they end up all being the same
//	for cases where you need it more than once in the same print statment, use int64toxstr_r() instead
char *int64toxstr(UInt64 num)
{
	static char str[32];
	UInt32 hi,lo;

	hi = num>>32;
	lo = num&(0xffffffff);
	if (hi) {
		sprintf(str,"0x%u%8.8u",hi,lo);
	} else {
		sprintf(str,"0x%u",lo);
	}
	return str;
}

char *int64toxstr_r(UInt64 num, char * str)
{
	UInt32 hi,lo;

	hi = num>>32;
	lo = num&(0xffffffff);
	if (hi) {
		sprintf(str,"0x%u%8.8u",hi,lo);
	} else {
		sprintf(str,"0x%u",lo);
	}
	return str;
}

//  careful about using more than one call to this in the same print statement, they end up all being the same
//	for cases where you need it more than once in the same print statment, use int64toxstr_r() instead
char *int64todstr(UInt64 num)
{
	static char str[40];
	UInt32 hi,lo;

	hi = num>>32;
	lo = num&(0xffffffff);

	if (hi)
		sprintf(str,"%u%8.8u",hi,lo);
	else
		sprintf(str,"%u",lo);
	return str;
}


char *int64todstr_r(UInt64 num, char * str)
{
	UInt32 hi,lo;

	hi = num>>32;
	lo = num&(0xffffffff);

	if (hi)
		sprintf(str,"%u%8.8u",hi,lo);
	else
		sprintf(str,"%u",lo);
	return str;
}

//  careful about using more than one call to this in the same print statement, they end up all being the same
char *langtodstr(UInt16 num)
{
	static char str[5];

	str[4] = 0;

	if (num==0) {
		str[0] = str[1] = str[2] = ' ';
	}
	else {
		str[0] = ((num >> 10) & 0x1F) + 0x60;
		str[1] = ((num >> 5 ) & 0x1F) + 0x60;
		str[2] = ( num		& 0x1F) + 0x60;
	}

	return str;
}


//  careful about using more than one call to this in the same print statement, they end up all being the same
//	for cases where you need it more than once in the same print statment, use fixed16str_r() instead
char *fixed16str(SInt16 num)
{
	static char str[40];
	float f;

	f = num;
	f /= 0x100;

	sprintf(str,"%f",f);

	return str;
}

char *fixed16str_r(SInt16 num, char * str)
{
	float f;

	f = num;
	f /= 0x100;

	sprintf(str,"%f",f);

	return str;
}


//  careful about using more than one call to this in the same print statement, they end up all being the same
//	for cases where you need it more than once in the same print statment, use fixed32str_r() instead
char *fixed32str(SInt32 num)
{
	static char str[40];
	double f;

	f = num;
	f /= 0x10000;

	sprintf(str,"%lf",f);

	return str;
}

char *fixed32str_r(SInt32 num, char * str)
{
	double f;

	f = num;
	f /= 0x10000;

	sprintf(str,"%lf",f);

	return str;
}



//  careful about using more than one call to this in the same print statement, they end up all being the same
//	for cases where you need it more than once in the same print statment, use fixedU32str_r() instead
char *fixedU32str(UInt32 num)
{
	static char str[40];
	double f;

	f = num;
	f /= 0x10000;

	sprintf(str,"%lf",f);

	return str;
}

char *fixedU32str_r(UInt32 num, char * str)
{
	double f;

	f = num;
	f /= 0x10000;

	sprintf(str,"%lf",f);

	return str;
}

	//  copy non-terminated C string (chars) to terminated C string (str)
void copyCharsToStr( char *chars, char *str, UInt16 count ){
	SInt16 i;

	for( i = 0; i < count; ++i )
		str[i] = chars[i];

	str[ count ] = 0;

}
  //To remove all occurences of the specified character.
void remove_all_chars(char* str, char c) {
		char *pr = str, *pw = str;
		while (*pr) {
			*pw = *pr++;
			pw += (*pw != c);
		}
		*pw = '\0';
}
  //Convert hexadecimal to integer
int hex_to_int(char c){
		int first,second,result ;
	if (c >= 97)
		  c = c - 32;
	first= c / 16 - 3;
	second= c % 16;
		result = first*10 + second;
		if(result > 9) result--;
		return result;
}
  //Convert hexadecimal to ASCII
int hex_to_ascii(char c, char d){
		int high,low;
		high= hex_to_int(c) * 16;
		low = hex_to_int(d);
		return high+low;
}

  //Convert base64 string to ASCII
#define TABLELEN		64
//#define BUFFFERLEN	  128

#define ENCODERLEN	  4
#define ENCODEROPLEN	0
#define ENCODERBLOCKLEN 3

#define PADDINGCHAR	 '='
#define BASE64CHARSET   "ABCDEFGHIJKLMNOPQRSTUVWXYZ"\
						"abcdefghijklmnopqrstuvwxyz"\
						"0123456789"\
						"+/";
int encodeblock(char *input, char *output, int oplen){
   int rc = 0, iplen = 0;
   char encodedstr[ENCODERLEN + 1] = "";
   char encodingtabe[TABLELEN + 1] = BASE64CHARSET;

   iplen = strlen(input);
   encodedstr[0] = encodingtabe[ input[0] >> 2 ];
   encodedstr[1] = encodingtabe[ ((input[0] & 0x03) << 4) |
								 ((input[1] & 0xf0) >> 4) ];
   encodedstr[2] = (iplen > 1 ? encodingtabe[ ((input[1] & 0x0f) << 2) |
											  ((input[2] & 0xc0) >> 6) ] : PADDINGCHAR);
   encodedstr[3] = (iplen > 2 ? encodingtabe[ input[2] & 0x3f ] : PADDINGCHAR);
   strncat(output, encodedstr, oplen-strlen(output));

   return rc;
}

int Base64Encode(char *input, char *output, int oplen){
   int rc = 0;
   int index = 0, ipindex = 0, iplen = 0;
   char encoderinput[ENCODERBLOCKLEN + 1] = "";

   iplen = strlen(input);
   while(ipindex < iplen){
	  for(index = 0; index < 3; index++){
		 if(ipindex < iplen){
			encoderinput[index] = input[ipindex];
		 }else{
			encoderinput[index] = 0;
		 }
		 ipindex++;
	  }
	  rc = encodeblock(encoderinput, output, oplen);
   }

   return rc;
}
//==========================================================================================

void addEscapedChar( char *str, char c );
void addEscapedChar( char *str, char c )
{
	char addc[4] = {};

	if ((('a' <= c) && (c <= 'z'))
		|| (('A' <= c) && (c <= 'Z'))
		|| (('0' <= c) && (c <= '9'))
	//	add extra chars here
	//  we want to escape - & . for now
		) {
		addc[0] = c;
	} else {
		char n;

		addc[0] = '%';

		n = ((c >> 4) & 0x0F);
		if (n < 10)
			n = n + '0';
		else
			n = (n - 10) + 'a';
		addc[1] = n;

		n = ((c) & 0x0F);
		if (n < 10)
			n = n + '0';
		else
			n = (n - 10) + 'a';
		addc[2] = n;
	}

	strcat(str, addc);
}

void addAtomToPath( atompathType workingpath, OSType atomId, SInt32 atomIndex, atompathType curpath )
{
	strcpy( curpath, workingpath );
	if (workingpath[0])
		strcat( workingpath, ":");
	addEscapedChar(workingpath, (atomId>>24) & 0xff);
	addEscapedChar(workingpath, (atomId>>16) & 0xff);
	addEscapedChar(workingpath, (atomId>> 8) & 0xff);
	addEscapedChar(workingpath, (atomId>> 0) & 0xff);
	strcat( workingpath, "-");
	sprintf(&workingpath[strlen(workingpath)],"%u", atomIndex);
}

void restoreAtomPath( atompathType workingpath, atompathType curpath )
{
	strcpy( workingpath, curpath );
}

//===========================================================
//  Validate a Video Elementary Stream
//===========================================================

OSErr ValidateElementaryVideoStream( atomOffsetEntry *aoe, void *refcon )
{
#pragma unused(refcon)
	TrackInfoRec tir = {};
	OSErr err = noErr;
	UInt32 startCode;
	UInt32 prevStartCode;
	UInt64 offset1 = aoe->offset;
	UInt64 offset2, offset3;
	UInt32 sampleNum = 0;
	BitBuffer bb;
	Ptr dataP;
	UInt32 dataSize;
	UInt32 refcons[2];

	if (vg.checklevel < checklevel_samples)
		vg.checklevel = checklevel_samples;

	tir.sampleDescriptionCnt = 1;
	tir.validatedSampleDescriptionRefCons = &refcons[0];

	err = GetFileStartCode( aoe, &prevStartCode, offset1, &offset2 );
	if (err) {
		fprintf(stderr,"### did NOT find ANY start codes\n");
		goto bail;
	}

	do {
		err = GetFileStartCode( aoe, &startCode, offset2, &offset3 );

		if (err) {
			offset3 = aoe->maxOffset;
		}

		if (err || (startCode == 0x000001B6) || (startCode == 0x000001B3)) {
			if (!err && prevStartCode == 0x000001B3) {
				goto nextone;
			}

			dataSize = (UInt32)(offset3 - offset1);
			BAILIFNIL( dataP = (Ptr)malloc(dataSize), allocFailedErr );
			err = GetFileData( vg.fileaoe, dataP, offset1, dataSize, nil );

			err = BitBuffer_Init(&bb, (UInt8 *)dataP, dataSize);

			if (sampleNum == 0) {
				atomprint("<Video_Sample_Description offset=\"%s\" size=\"%d\"",
								int64toxstr(offset1),dataSize); vg.tabcnt++;
					Validate_vide_ES_Bitstream( &bb, &tir );
				--vg.tabcnt; atomprint("</Video_Sample_Description>\n");
			} else {
				atomprint("<Video_Sample sample_num=\"%d\" offset=\"%s\" size=\"%d\"",
								sampleNum, int64toxstr(offset1),dataSize); vg.tabcnt++;
					Validate_vide_sample_Bitstream( &bb, &tir );
				--vg.tabcnt; atomprint("</Video_Sample_Description>\n");
			}

			sampleNum++;
			offset1 = offset2 = offset3;
		}
nextone:
		prevStartCode = startCode;
		offset2 = offset3 + 4;
	} while (!err);



bail:
	return err;
}


