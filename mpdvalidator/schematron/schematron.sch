<?xml version="1.0" encoding="UTF-8"?>
<schema xmlns="http://purl.oclc.org/dsdl/schematron" xmlns:dash="urn:mpeg:dash:schema:mpd:2011" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:cenc="urn:mpeg:cenc:2013" queryBinding='xslt2' schemaVersion='ISO19757-3'>
	<ns prefix="dash" uri="urn:mpeg:dash:schema:mpd:2011"/>
	<ns prefix="xlink" uri="http://www.w3.org/1999/xlink"/>
	<ns prefix="xsi" uri="http://www.w3.org/2001/XMLSchema-instance"/>
	<ns prefix="xs" uri="http://www.w3.org/2001/XMLSchema"/>
	<ns prefix="cenc" uri="urn:mpeg:cenc:2013"/>

	<!-- include some helper functions for codec specific assertions, needed for DVB DASH assertions -->
	<extends href="helper-functions.sch"/>
	
	<title>Schema for validating MPDs</title>
	<pattern>
		<title>MPD element</title>
		<!-- R1.*: Check the conformance of MPD -->
		<rule context="dash:MPD">
			<!-- R1.0 -->
			<assert test="if (@type = 'dynamic' and not(@availabilityStartTime)) then false() else true()">If MPD is of type "dynamic" availabilityStartTime shall be defined.</assert>
			<!-- R1.1 -->
			<assert test="if (@type = 'dynamic' and not(@publishTime)) then false() else true()">If MPD is of type "dynamic" publishTime shall be defined.</assert>
			<!-- R1.3 -->
			<!-- <assert test="if (@type = 'static' and not(@mediaPresentationDuration)) then false() else true()">If MPD is of type "static" mediaPresentationDuration shall be defined.</assert>  -->
			<!-- R1.4 -->
			<assert test="if (@type = 'static' and descendant::dash:Period[1]/@start and (years-from-duration(descendant::dash:Period[1]/@start) + months-from-duration(descendant::dash:Period[1]/@start) + days-from-duration(descendant::dash:Period[1]/@start) + hours-from-duration(descendant::dash:Period[1]/@start) + minutes-from-duration(descendant::dash:Period[1]/@start) +  seconds-from-duration(descendant::dash:Period[1]/@start)) > 0) then false() else true()">If MPD is of type "static" and the first period has a start attribute the start attribute shall be zero.</assert>
			<!-- R1.5 -->
			<assert test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod)) then false() else true()">If mediaPresentationDuration is not defined for the MPD minimumUpdatePeriod shall be defined or vice versa.</assert>
			<!-- R1.7 -->
			<assert test="if (not(@profiles) or (contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:full:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-simple:2011') or contains (@profiles, 'http://dashif.org/guidelines/dashif#ac-4') or contains (@profiles, 'http://dashif.org/guidelines/dashif#mpeg-h-3da') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9-hdr') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9-hdr') or contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(@profiles, 'urn:dvb:dash:profile:dvb-dash:2014') or contains(@profiles, 'http://dashif.org/guidelines/dashif#ec-3'))) then true() else false()">An unknown profile string (other than the On-Demand profile -"urn:mpeg:dash:profile:isoff-on-demand:2011", the live profile -"urn:mpeg:dash:profile:isoff-live:2011", the main profile- "urn:mpeg:dash:profile:isoff-main:2011", the full profile "urn:mpeg:dash:profile:full:2011", the mp2t-main profile -"urn:mpeg:dash:profile:mp2t-main:2011", the mp2t-simple profile -"urn:mpeg:dash:profile:mp2t-simple:2011", the Dolby AC-4 profile -"http://dashif.org/guidelines/dashif#ac-4", -the multichannel audio extension with MPEG-H 3D Audio profile -"http://dashif.org/guidelines/dashif#mpeg-h-3da", the VP9-HD profile -"http://dashif.org/guidelines/dashif#vp9", the VP9-UHD profile -"http://dashif.org/guidelines/dash-if-uhd#vp9", the VP9-HDR profile -"http://dashif.org/guidelines/dashif#vp9-hdr" or "http://dashif.org/guidelines/dash-if-uhd#vp9-hdr", the DVB-DASH profile -"urn:dvb:dash:profile:dvb-dash:2014", the HbbTV 1.5 profile -"urn:hbbtv:dash:profile:isoff-live:2012", the DASH-IF multchannel audio extension with Enhanced AC-3 -"http://dashif.org/guidelines/dashif#ec-3")found.</assert>
			<!-- R1.8 -->
			<assert test="if (not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or not(@type) or @type='static') then true() else false()">For On-Demand profile, the MPD @type shall be "static".</assert>
			<!-- R1.9 -->
			<assert test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod) and not(dash:Period[last()]/@duration)) then false() else true()">If minimumUpdatePeriod is not present and the last period does not include the duration attribute the mediaPresentationDuration must be present.</assert>
			<!-- R1.10: Disabled, there is no such conformance point in DASH 2nd edition (cuurent) -->
			<!-- assert test="if (@type='dynamic' and not(@id)) then false() else true()">If the MPD type is dynamic, the id shall be present </assert-->


                </rule>
	</pattern>
	<pattern>
		<title>Period element</title>
		<!-- R2.*: Check the conformance of Period -->
		<rule context="dash:Period">
			<!-- R2.0 -->
			<assert test="if (string(@bitstreamSwitching) = 'true' and string(child::dash:AdaptationSet/@bitstreamSwitching) = 'false') then false() else true()">If bitstreamSwitching is set to true all bitstreamSwitching declarations for AdaptationSet within this Period shall not be set to false.</assert>
			<!-- R2.1 -->
			<assert test="if (@id = preceding::dash:Period/@id) then false() else true()">The id of each Period shall be unique.</assert>
			<!-- R2.2: This rule has been found to not work properly, hence disabled for now -->
			<!-- assert test="if ((years-from-duration(@start) + months-from-duration(@start) + days-from-duration(@start) + hours-from-duration(@start) + minutes-from-duration(@start) +  seconds-from-duration(@start)) > (years-from-duration(following-sibling::dash:Period/@start) + months-from-duration(following-sibling::dash:Period/@start) + days-from-duration(following-sibling::dash:Period/@start) + hours-from-duration(following-sibling::dash:Period/@start) + minutes-from-duration(following-sibling::dash:Period/@start) +  seconds-from-duration(following-sibling::dash:Period/@start))) then false() else true()">Periods shall be physically ordered in the MPD file in increasing order of their start time.</assert-->
			<!-- R2.3 -->
			<assert test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in Period.</assert>
			<!-- R2.4 -->
			<assert test="if (not(@id) and ancestor::dash:MPD/@type = 'dynamic') then false() else true()">If the MPD is dynamic the Period element shall have an id.</assert>
			<!-- R2.5 -->
			<assert test="if (not(descendant-or-self::dash:BaseURL) and not(descendant-or-self::dash:SegmentTemplate) and not(descendant-or-self::dash:SegmentList) and not(@xlink:href = 'urn:mpeg:dash:resolve-to-zero:2013')) then false() else true()">At least one BaseURL, SegmentTemplate or SegmentList shall be defined in Period, AdaptationSet or Representation.</assert>
            		<assert test="if (@duration = xs:duration(PT0S) and count(child::dash:AdaptationSet)) then false() else true()">If the duration attribute is set to zero, there should only be a single AdaptationSet present.</assert>
                        <!-- 	DASH 8.3.2 -->
                        <assert test="if (contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') and (child::dash:SegmentList or child::dash:SegmentTemplate)) then false() else true()">Neither the Period.SegmentList element nor the Period.SegmentTemplate element shall be present for On-Demand profile, violated here. </assert>
		</rule>
	</pattern>
	<pattern>
		<title>AdaptationSet element</title>
		<!-- R3.*: Check the conformance of AdaptationSet -->
		<rule context="dash:AdaptationSet">
			<!-- R3.0 -->
			<assert test="if (@id = preceding-sibling::dash:AdaptationSet/@id) then false() else true()">The id of each AdaptationSet within a Period shall be unique.</assert>
			<!-- R3.1 -->
			<assert test="if ((@lang = descendant::dash:ContentComponent/@lang) or (@contentType = descendant::dash:ContentComponent/@contentType) or (@par = descendant::dash:ContentComponent/@par)) then false() else true()">Attributes from the AdaptationSet shall not be repeated in the descendanding ContentComponent elements.</assert>
			<!-- R3.2 -->
			<assert test="if ((@profiles and descendant::dash:Representation/@profiles) or (@width and descendant::dash:Representation/@width) or (@height and descendant::dash:Representation/@height) or (@sar and descendant::dash:Representation/@sar) or (@frameRate and descendant::dash:Representation/@frameRate) or (@audioSamplingRate and descendant::dash:Representation/@audioSamplingRate) or (@mimeType and descendant::dash:Representation/@mimeType) or (@segmentProfiles and descendant::dash:Representation/@segmentProfiles) or (@codecs and descendant::dash:Representation/@codecs) or (@maximumSAPPeriod and descendant::dash:Representation/@maximumSAPPeriod) or (@startWithSAP and descendant::dash:Representation/@startWithSAP) or (@maxPlayoutRate and descendant::dash:Representation/@maxPlayoutRate) or (@codingDependency and descendant::dash:Representation/@codingDependency) or (@scanType and descendant::dash:Representation/@scanType)) then false() else true()">Common attributes for AdaptationSet and Representation shall either be in one of the elements but not in both.</assert>
			<!-- R3.3 -->
			<assert test="if ((xs:int(@minWidth) > xs:int(@maxWidth)) or (xs:int(@minHeight) > xs:int(@maxHeight)) or (xs:int(@minBandwidth) > xs:int(@maxBandwidth))) then false() else true()">Each minimum value (minWidth, minHeight, minBandwidth) shall be larger than the maximum value.</assert>
			<!-- R3.4 -->
			<assert test="if (descendant::dash:Representation/@bandwidth &lt; xs:int(@minBandwidth) or descendant::dash:Representation/@bandwidth > xs:int(@maxBandwidth)) then false() else true()">The value of the bandwidth attribute shall be in the range defined by the AdaptationSet.</assert>
			<!-- R3.5 -->
			<assert test="if (descendant::dash:Representation/@width > xs:int(@maxWidth)) then false() else true()">The value of the width attribute shall be in the range defined by the AdaptationSet.</assert>
			<!-- R3.6 -->
			<assert test="if (descendant::dash:Representation/@height > xs:int(@maxHeight)) then false() else true()">The value of the height attribute shall be in the range defined by the AdaptationSet.</assert>
			<!-- R3.7 -->
			<assert test="if (count(child::dash:Representation)=0) then false() else true()">An AdaptationSet shall have at least one Representation element.</assert>
			<!-- R3.8 -->
			<assert test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in AdaptationSet.</assert>
			<!-- R3.9 -->
			<assert test="if ((@minFrameRate and (some $fr in descendant::dash:Representation/@frameRate satisfies dlb:fractionalToFloat($fr) lt dlb:fractionalToFloat(@minFrameRate))) or (@maxFrameRate and (some $fr in descendant::dash:Representation/@frameRate satisfies dlb:fractionalToFloat($fr) gt dlb:fractionalToFloat(@maxFrameRate)))) then false() else true()">ISO/IEC 23009-1 Section 5.3.3.2: The value of the frameRate attribute shall be in the range defined by the AdaptationSet.</assert>
                        <!-- HbbTV profile checks -->
                        <assert test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentAlignment = 'true')) then false() else true()">HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentAlignment' as true</assert>
                        <assert test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '1' or @subsegmentStartsWithSAP = '2')) then false() else true()">HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 1 or 2</assert>
                        <assert test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '3') and not (count(child::dash:Representation) &gt; 1)) then false() else true()">HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 3 while not containing more than one Representation</assert>
		</rule>
	</pattern>

	<pattern>
		<title>ContentComponent element</title>
		<!-- R4.*: Check the conformance of ContentComponent -->
		<rule context="dash:ContentComponent">
			<!-- R4.0 -->
			<assert test="if (@id = preceding-sibling::dash:ContentComponent/@id) then false() else true()">The id of each ContentComponent within an AdaptationSet shall be unique.</assert>
		</rule>
	</pattern>
	<pattern>
		<title>Representation element</title>
		<!-- R5.*: Check the conformance of Representation -->
		<rule context="dash:Representation">
			<!-- R5.0 -->
			<assert test="if (not(@mimeType) and not(parent::dash:AdaptationSet/@mimeType)) then false() else true()">Either the Representation or the containing AdaptationSet shall have the mimeType attribute.</assert>
			
			<!-- R5.1 -->
			<assert test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()">For live profile, the SegmentTemplate element shall be present on at least one of the three levels, the Period level containing the Representation, the Adaptation Set containing the Representation, or on Representation level itself.</assert>
			<!-- R5.2 -->
			<assert test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in Representation.</assert>
                        
                        <!-- Unique ID check -->
			<assert test="if ((@id = preceding-sibling::dash:Representation/@id) or (@id=parent::dash:AdaptationSet/preceding-sibling::dash:AdaptationSet/dash:Representation/@id))then false() else true()">The id of each Representation within a Period shall be unique.</assert>
                        
                        
                        <!-- HbbTV profile checks -->
                        <assert test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (count(child::dash:BaseURL) &gt; 0)) then false() else true()">HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an element that is not part of the HbbTV profile', i.e., found 'BaseURL' element</assert>
                        <assert test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (parent::dash:AdaptationSet/@subsegmentStartsWithSAP = '3') and (@mediaStreamStructureId = following-sibling::dash:Representation/@mediaStreamStructureId)) then false() else true()">HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 3 with same value of mediaStreamStructureId in more than one Representation</assert>
                        <!-- HbbTV profile checks: extending the live profile checks -->
                        <assert test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) then false() else true()">HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - For HbbTV profile, the SegmentTemplate element shall be present on at least one of the three levels, the Period level containing the Representation, the Adaptation Set containing the Representation, or on Representation level itself as it is based on live profile</assert>
		</rule>
	</pattern>
	<pattern>
		<title>SubRepresentation element</title>
		<!-- R6.*: Check the conformance of SubRepresentation -->
		<rule context="dash:SubRepresentation">
			<!-- R6.0 -->
			<assert test="if (@level and not(@bandwidth)) then false() else true()">If the level attribute is defined for a SubRepresentation also the bandwidth attribute shall be defined.</assert>

		</rule>
	</pattern>
	<pattern>
		<title>SegmentTemplate element</title>
		<!-- R7.*: Check the conformance of SegmentTemplate -->
		<rule context="dash:SegmentTemplate">
			<!-- R7.0 -->
			<assert test="if (not(@duration) and not(child::dash:SegmentTimeline) and not(@initialization) ) then false() else true()">If more than one Media Segment is present the duration attribute or SegmentTimeline element shall be present.</assert>
			<!-- R7.1 -->
			<assert test="if (@duration and child::dash:SegmentTimeline) then false() else true()">Either the duration attribute or SegmentTimeline element shall be present but not both.</assert>
			<!-- R7.2 -->
			<assert test="if (not(@indexRange) and @indexRangeExact) then false() else true()">If indexRange is not present indexRangeExact shall not be present.</assert>
			<!-- R7.3 -->
			<assert test="if (@initialization and (matches(@initialization, '\$Number(%.[^\$]*)?\$') or matches(@initialization, '\$Time(%.[^\$]*)?\$'))) then false() else true()">Neither $Number$ nor the $Time$ identifier shall be included in the initialization attribute.</assert>
			<!-- R7.4 -->
			<assert test="if (@bitstreamSwitching and (matches(@bitstreamSwitching, '\$Number(%.[^\$]*)?\$') or matches(@bitstreamSwitching, '\$Time(%.[^\$]*)?\$'))) then false() else true()">Neither $Number$ nor the $Time$ identifier shall be included in the bitstreamSwitching attribute.</assert>
			<!-- R7.5-->
			<assert test="if (matches(@media, '\$.[^\$]*\$')) then every $y in (for $x in tokenize(@media, '\$(Bandwidth|Time|Number|RepresentationID)(%.[^\$]*)?\$') return matches($x, '\$.[^\$]*\$')) satisfies $y eq false() else true()">Only identifiers such as $Bandwidth$, $Time$, $RepresentationID$, or $Number$ shall be used.</assert>
			<!-- R7.6-->
			<assert test="if (matches(@media, '\$RepresentationID%.[^\$]*\$')) then false() else true()">$RepresentationID$ shall not have a format tag.</assert>
		</rule>
	</pattern>
	<pattern>
		<title>SegmentList element</title>
		<!-- R8.*: Check the conformance of SegmentList -->
		<rule context="dash:SegmentList">
			<!-- R8.0 -->
			<assert test="if (not(@duration) and not(child::dash:SegmentTimeline)) then if (count(child::dash:SegmentURL) > 1) then false() else true() else true()">If more than one Media Segment is present the duration attribute or SegmentTimeline element shall be present.</assert>
			<!-- R8.1 -->
			<assert test="if (@duration and child::dash:SegmentTimeline) then false() else true()">Either the duration attribute or SegmentTimeline element shall be present but not both.</assert>
			<!-- R8.2 -->
			<assert test="if (not(@indexRange) and @indexRangeExact) then false() else true()">If indexRange is not present indexRangeExact shall not be present.</assert>
		</rule>
	</pattern>
	<pattern>
		<title>SegmentBase element</title>
		<!-- R9.*: Check the conformance of SegmentBase -->
		<rule context="dash:SegmentBase">
			<!-- R9.0 -->
			<assert test="if (not(@indexRange) and @indexRangeExact) then false() else true()">If indexRange is not present indexRangeExact shall not be present.</assert>
                        <!-- R9.1 -->
			<assert test="if (@timeShiftBufferDepth) then if (xs:int(@timeShiftbuffer) &lt; xs:int(dash:MPD/@timeShiftBufferDepth)) then false() else true() else true()">The timeShiftBufferDepth shall not be smaller than timeShiftBufferDepth specified in the MPD element</assert>
		</rule>
	</pattern>
	<pattern>
		<title>SegmentTimeline element</title>
		<!-- R10.*: Check the conformance of SegmentTimeline -->
		<rule context="dash:SegmentTimeline">
			<!-- R10.0 -->
			<let name="timescale" value="if (ancestor::dash:*[1]/@timescale) then ancestor::dash:*[1]/@timescale else 1"/>
			<assert test="if (some $d in child::dash:S/@d satisfies $d div $timescale > (years-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + months-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + days-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + hours-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + minutes-from-duration(ancestor::dash:MPD/@maxSegmentDuration) +  seconds-from-duration(ancestor::dash:MPD/@maxSegmentDuration))) then false() else true()">The d attribute of a SegmentTimeline shall not exceed the value give by the MPD maxSegmentDuration attribute.</assert>
		</rule>
	</pattern>
	<pattern>
		<title>ProgramInformation element</title>
		<!-- R11.*: Check the conformance of ProgramInformation -->
		<rule context="dash:ProgramInformation">
			<!-- R11.0 -->
			<assert test="if (count(parent::dash:MPD/dash:ProgramInformation) > 1 and not(@lang)) then false() else true()">If more than one ProgramInformation element is given each ProgramInformation element shall have a lang attribute.</assert>
		</rule>
	</pattern>
	<pattern>
		<title>ContentProtection element</title>
		<!-- R12.*: Check the conformance of ContentProtection -->
		<rule context="dash:ContentProtection">
			<!-- R12.0 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(string-length(@value) = 4)) then false() else true()">The value of ContentProtection shall be the 4CC contained in the Scheme Type Box</assert>
			<!-- R12.1 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:CA_descriptor:2011') and not(string-length(@value) = 4)) then false() else true()">The value of ContentProtection shall be the 4-digit lower-case hexadecimal Representation.</assert>

		</rule>
	</pattern>
	<pattern>
		<title>Role element</title>
		<!-- R13.*: Check the conformance of Role -->
		<rule context="dash:Role">
			<!-- R13.0 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:role:2011') and not(@value = 'caption' or @value = 'subtitle' or @value = 'main' or @value = 'alternate' or @value = 'supplementary' or @value = 'commentary' or @value = 'dub')) then false() else true()">The value of Role (role) shall be caption, subtitle, main, alternate, supplementary, commentary or dub.</assert>
			<!-- R13.1 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:stereoid:2011') and not(starts-with(@value, 'l') or starts-with(@value, 'r'))) then false() else true()">The value of Role (stereoid) shall start with 'l' or 'r'.</assert>
		</rule>
	</pattern>	
	<pattern>
		<title>FramePacking element</title>
		<!-- R14.*: Check the conformance of FramePacking -->
		<rule context="dash:FramePacking">
			<!-- R14.0 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(contains(parent::dash:AdaptationSet/@codecs, 'avc') or contains(parent::dash:AdaptationSet/@codecs, 'svc') or contains(parent::dash:AdaptationSet/@codecs, 'mvc')) and not(contains(parent::dash:Representation/@codecs, 'avc') or contains(parent::dash:Representation/@codecs, 'svc') or contains(parent::dash:Representation/@codecs, 'mvc'))) then false() else true()">The URI urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011 is used for Adaptation Sets or Representations that contain a video component that conforms to ISO/IEC 14496-10.</assert>
			<!-- R14.1 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011') and not(parent::dash:AdaptationSet/@mimeType = 'video/mp2t') and not(parent::dash:Representation/@mimeType = 'video/mp2t')) then false() else true()">The URI urn:mpeg:dash:13818:1:stereo_video_format_type:2011 is used for Adaptation Sets or Representations that contain a video component that conforms to ISO/IEC 13818-1.</assert>
			<!-- R14.2 -->
			<assert test="if (not(@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011')) then false() else true()">schemeIdUri for FramePacking descriptor shall be urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011 or urn:mpeg:dash:13818:1:stereo_video_format_type:2011.</assert>
			<!-- R14.3 -->
			<assert test="if (not(@value = '0' or @value = '1' or @value = '2' or @value = '3' or @value = '4' or @value = '5' or @value = '6')) then false() else true()">The value of FramePacking shall be 0 to 6 as defined in ISO/IEC 23001-8.</assert>
		</rule>
	</pattern>
	<pattern>
		<title>AudioChannelConfiguration element</title>
		<!-- R15.*: Check the conformance of AudioChannelConfiguration -->
		<rule context="dash:AudioChannelConfiguration">
			<!-- R15.0 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:outputChannelPositionList:2012') and not(count(tokenize(@value, ' ')) > 1)) then false() else true()">If URI urn:mpeg:dash:outputChannelPositionList:2012 is used the value attribute shall be a space-delimited list as defined in ISO/IEC 23001-8.</assert>

		</rule>
	</pattern>
	
	<pattern>
		<title>EventStream element</title>
		<!-- R16.*: Check the conformance of SegmentList -->
		<rule context="dash:EventStream">
			<!-- R16.0 -->
			<assert test="if (@actuate and not(@href)) then false() else true()">If href is not present actuate shall not be present.</assert>
			<!-- R16.1 -->
			<assert test="if (not(@schemeIdUri)) then false() else true()">schemeIdUri shall be present.</assert>
		</rule>
	</pattern>
    
      <pattern>
		<title>Subset element</title>
        <rule context="dash:Subset">
            <!--R17.1-->
           <assert test="if (@id = preceding::dash:Subset/@id) then false() else true()">The id of each Subset shall be unique.</assert>
        </rule>
    </pattern>
	
	<pattern>
		<title>UTCTiming element</title>
		<!-- R18.*: Check the conformance of UTCTiming -->
		<rule context="dash:UTCTiming">
            <!-- R18.1 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:utc:ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:sntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-head:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-xsdate:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-iso:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:direct:2014')) then true() else false()">@schemeIdUri for UTCTiming is not one of the 7 different types specified.</assert>
		</rule>
	</pattern>			
		
	<pattern>
		<title>SupplementalProperty element</title>
		<!-- R19.*: Check the conformance of SupplementalProperty -->
		<rule context="dash:SupplementalProperty">
            <!-- R19.1 -->
			<assert test="if((@schemeIdUri= 'urn:mpeg:dash:chaining:2016') and not((count(tokenize(@value, ','))=1) or (count(tokenize(@value, ','))>1)) )then false() else true()">If schemeIdUri urn:mpeg:dash:chaining:2016 is used, then value attribute shall be composed of the comma separated parameters (no comma needed if only first parameter is present). </assert>
	    <!-- R19.2 -->
			<assert test="if(not(parent::dash:MPD) and (@schemeIdUri= 'urn:mpeg:dash:fallback:2016') )then false() else true()">MPD fallback chaining shall be signaled by Supplemental Descriptor on MPD level with schemeIdUri urn:mpeg:dash:fallback:2016. </assert>	
	    <!-- R19.3 -->
			<assert test="if((@schemeIdUri= 'urn:mpeg:dash:fallback:2016') and not((count(tokenize(@value, ' '))=1) or (count(tokenize(@value, ' '))>1)) )then false() else true()">If schemeIdUri urn:mpeg:dash:fallback:2016 is used, then value attribute shall be composed of one URL or whitespace separated URLs. </assert>		
		</rule>
	</pattern>
        
        <pattern>
                <title>SRD description element</title>
		<!-- R19.*: Check the conformance of SRD -->
		<rule context="dash:Period">
			<!-- R19.1 -->
			<assert test="if (every $AdaptationSet in child::dash:AdaptationSet satisfies $AdaptationSet/dash:EssentialProperty/@schemeIdUri = 'urn:mpeg:dash:srd:2014') then false() else true()">When every Adaptation Set in a MPD has a SRD descriptor, at least one of this descriptor shall be a SupplementalProperty.</assert>
			<!-- R19.7 -->
			<assert test="if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies (every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id] satisfies matches($srd/@value, '^(\d+,){4}\d+$') ) ) then false() else true()">For a given source_id of the @value attribute, at least one of the EssentialProperty or SupplementalProperty in the containing Period shall specify the optional parameters W and H.</assert>
			<!-- R19.8-->
			<assert test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) > 0) then if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( if (count(distinct-values(for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){6}\d+')] return concat(string(subsequence(tokenize($srd/@value,','),6,1)), string(subsequence(tokenize($srd/@value,','),7,1))) ) ) > 1 ) then every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id] satisfies matches($srd/@value, '^(\d+,){6}\d+') else true() ) ) then true() else false() else true()">For a given source_id of the @value attribute, if two SRD elements (indistinctively EssentialProperty or SupplementalProperty) explicitly specify a different pair of  values for the optional parameters (W,H) then all the remaining SRD element shall explicitly specify a pair of values for (W,H) too.</assert>
			<!-- R19.9a - W is present in the descriptor-->
			<assert test="every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and matches(@value, '^(\d+,){6}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),2,1)),number(subsequence(tokenize($srd/@value,','),4,1)) ) ) le number(subsequence(tokenize($srd/@value,','),6,1))">For a given source_id of the @value attribute, the values of x, w and W shall be such that, for each descriptor, the sum of x and w is smaller or equal to W.</assert>
			<!-- R19.9b - W is not present in the descriptor -->
			<assert test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) > 0) then if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){4}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),2,1)),number(subsequence(tokenize($srd/@value,','),4,1)) ) ) le number(subsequence(tokenize(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and (subsequence(tokenize(@value,','),1,1) = $source_id) and (matches(@value, '^(\d+,){6}\d+'))]/@value,','),6,1)) ) ) then true() else false() else true()">For a given source_id of the @value attribute, the values of x, w and W shall be such that, for each descriptor, the sum of x and w is smaller or equal to W.</assert>
			<!-- R19.10a - H is present in the descriptor-->
			<assert test="every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and matches(@value, '^(\d+,){6}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),3,1)),number(subsequence(tokenize($srd/@value,','),5,1)) ) ) le number(subsequence(tokenize($srd/@value,','),7,1))">For a given source_id of the @value attribute, the values of y, h and H shall be such that, for each descriptor, the sum of y and h is smaller or equal to H.</assert>
			<!-- R19.10b - H is not present in the descriptor -->
			<assert test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) > 0) then if (some $source_id in (for $srd in descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( every $srd in descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and (subsequence(tokenize(@value,','),1,1) = $source_id) and (matches(@value, '^(\d+,){4}\d+'))] satisfies sum((number(subsequence(tokenize($srd/@value,','),3,1)),number(subsequence(tokenize($srd/@value,','),5,1)) ) ) le number(subsequence(tokenize(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){6}\d+')]/@value,','),7,1)) ) ) then true() else false() else true()">For a given source_id of the @value attribute, the values of y, h and H shall be such that, for each descriptor, the sum of y and h is smaller or equal to H.</assert>
		</rule>
		<rule context="dash:SupplementalProperty | dash:EssentialProperty">
			<!-- R19.2 -->
			<assert test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (parent::dash:AdaptationSet or parent::dash:SubRepresentation) then true() else false() else true()">An EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” shall be the child element of an AdaptationSet or a SubRepresentation element.</assert>
			<!-- R19.3 -->
			<assert test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (@value) then true() else false() else true()">If an EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present, then the @value attribute must be present.</assert>
			<!-- R19.4 -->
			<assert test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (matches(@value, '^\d+,\d+,\d+,\d+,\d+')) then true() else false() else true()">If an EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present, then the @value attribute must contain at least the mandatory comma separated parameters, i.e. source_id, x, y, w, h.</assert>
			<!-- R19.5 -->
			<assert test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (matches(@value, '^(\d+,){4,7}\d+$')) then true() else false() else true()">If an EssentialProperty or a  SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present, then each parameter value has to match the expected type format i.e. non-negative integer in decimal representation.</assert>
			<!-- R19.6 -->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:srd:2014') and matches(@value, '^(\d+,){5}\d+$')) then false() else true()">If an EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present and the @value attribute contains the optional parameter W then the optional parameter H shall be present too. In addition, if the optional parameter spatial_set_id is present, then the optional parameters W and H shall be present.</assert>
		</rule>
	</pattern>
        
        
<!-->.................................................The DASH-IF schematron checks starts from here.................................................................</-->
	<pattern>
		<title>MPD element</title>
		<!-- R1.*: Check the conformance of MPD -->
		<rule context="dash:MPD">
                        <assert test="if (@type = 'static' and @timeShiftBufferDepth and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()">If MPD is of type "static" and if the profile contains a DASH-IF IOP profile, then the timeShiftBufferDepth shall not be defined.</assert>
			<!-- R1.6 -->
			<assert test="if (@type = 'static' and @minimumUpdatePeriod and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()">If MPD is of type "static" and if the profile contains a DASH-IF IOP profile, then the minimumUpdatePeriod shall not be defined.</assert>
                        <!-- RD1.0 -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @type='dynamic' and not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()">DASH-IF IOP Section 3.2.2.2: For dynamic MPD, the @profile shall include urn:mpeg:dash:profile:isoff-live:2011. </assert>
                </rule>
	</pattern>
        <pattern>
		<title>Period element</title>
		<!-- R2.*: Check the conformance of Period -->
		<rule context="dash:Period">
	
			<!-- RD2.0	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and dash:SegmentList) then false() else true()">DASH-IF IOP Section 3.2.2: "the Period.SegmentList element shall not be present" violated here </assert>
			<!-- RD2.1	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (count(child::dash:AdaptationSet[@contentType='video']) > 1) and (count(descendant::dash:Role[@value='main'])=0)) then false() else true()"> DASH-IF IOP Section 3.2.2: "If a Period contains multiple Adaptation Sets with value of the @contentType="video" then at least one Adaptation Set shall contain a Role el-ement $&lt;$Role scheme="urn:mpeg:dash:role:2011" value="main"&gt;" violated here</assert >
                      
		</rule>
	</pattern>
        <pattern>
		<title>AdaptationSet element</title>
		<!-- R3.*: Check the conformance of AdaptationSet -->
		<rule context="dash:AdaptationSet">
                        <!-- DASH-IF v 3.3 Section 3.8  -->
			<assert test="if ((descendant::dash:SupplementalProperty/@value = following-sibling::dash:AdaptationSet/@id) and (@segmentAlignment='true') and (following-sibling::dash:AdaptationSet/@segmentAlignment = 'false')) then false() else true()">If the content author signals the ability of Adaptation Set switching and as @segmentAlignment or @subsegmentAlignment are set to TRUE for one Adaptation Set, the (Sub)Segment alignment shall hold for all Representations in all Adaptation Sets for which the @id value is included in the @value attribute of the Supplemental descriptor.</assert>
			
			<!-- RD3.0	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@par)) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="video" the following attributes shall be present: @par" violated here</assert >
			<!-- RD3.1	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and (@scanType and not(@scanType='progressive'))) then false() else true()"> DASH-IF IOP Section 3.2.4: "For Adaptation Set or for any Representation within an Adaptation Set with value of the @contentType="video" the attribute @scanType shall either not be present or shall be set to 'progressive' ", violated here</assert >
			<!-- RD3.2	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='audio' and not(@lang)) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="audio" the following attributes shall be present: @lang" violated here</assert >
			<!-- RD3.3	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxWidth) and not(@width)) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxWidth or @width" violated here</assert >
			<!-- RD3.4	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxHeight) and not(@height)) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxHeight or @height" violated here</assert >
			<!-- RD3.5	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxFrameRate) and not(@frameRate)) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxFrameRate or @frameRate" violated here</assert >
			<!-- RD3.6	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()"> DASH-IF IOP Section 3.2.2.2: For Live Profile @segmentAlignment shall be set to true for all Adaptation Sets</assert >
                        <!-- RD3.7	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-ondemand:2011') and (not(@subSegmentAlignment) or @subSegmentAlignment='false')) then false() else true()"> DASH-IF IOP Section 3.2.2.2: For On-Demand Profile @subSegmentAlignment shall be set to true for all Adaptation Sets</assert >
			<!-- RD3.7	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @mimeType and not((@mimeType = 'video/mp4') or (@mimeType = 'audio/mp4') or (@mimeType = 'application/mp4') or (@mimeType = 'application/ttml+xml') or (@mimeType = 'text/vtt') or (@mimeType = 'image/jpeg'))) then false() else true()">If a DASH-IF profile identifier is present, for the Adaptation Sets the mimeType shall be one of the six following type: "video/mp4", "audio/mp4", "application/mp4", "application/ttml+xml", "text/vtt" or "image/jpeg"</assert >
                        <!-- DASH-IF Section 5.3.7.2 -->
                        <assert test="if (@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))) then false() else true()">The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</assert>
                        <!-- HbbTV profile checks: extending the live profile checks -->
                        <assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((@profiles and contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()"> HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - For HbbTV profile, @segmentAlignment shall be set to true for all Adaptation Sets as it is based on live profile</assert>
		</rule>
	</pattern>
        <pattern>
		<title>Representation element</title>
		<!-- R5.*: Check the conformance of Representation -->
		<rule context="dash:Representation">
			
			<!-- R5.0 -->
			<assert test="if (@mimeType and following-sibling::dash:Representation/@mimeType and not(following-sibling::dash:Representation/@mimeType = @mimeType)) then false() else true()">DASH-IF IOP (v3.3), Section 3.2.13 : "In contrast to MPEG-DASH which does not prohibit the use of multiplexed Representations, in the DASH-IF IOPs one Adaptation Set always contains exactly a single media type.".</assert>
			
			<!-- RD5.0	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (((@width != preceding-sibling::dash:Representation/@width) and not(parent::dash:AdaptationSet/@maxWidth)) or ((@height != preceding-sibling::dash:Representation/@height) and not(parent::dash:AdaptationSet/@maxHeight)) or ((@frameRate != preceding-sibling::dash:Representation/@frameRate) and not(parent::dash:AdaptationSet/@maxFrameRate)))) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="video" the following attributes shall be present: @maxWidth (or @width if all Representations have the same width), @maxHeight (or @height if all Representations have the same width), @maxFrameRate (or @frameRate if all Representations have the same width)" violated here</assert >
			<!-- RD5.1	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and ((not(@width) and not(parent::dash:AdaptationSet/@width)) or (not(@height) and not(parent::dash:AdaptationSet/@height)) or (not(@frameRate) and not(parent::dash:AdaptationSet/@frameRate)) or not(@sar))) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Representation within an Adaptation Set with value of the @contentType="video" the following attributes shall be present: @width, if not present in AdaptationSet element; @height, if not present in AdaptationSet element; @frameRate, if not present in AdaptationSet element; @sar" violated here</assert >
			<!-- RD5.2	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (@scanType and not(@scanType='progressive')))then false() else true()"> DASH-IF IOP Section 3.2.4: "For Adaptation Set or for any Representation within an Adaptation Set with value of the @contentType="video" the attribute @scanType shall either not be present or shall be set to 'progressive' ", violated here</assert >
			<!-- RD5.3	DASH-IF -->
			<assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='audio' and ((not(@audioSamplingRate) and not(parent::dash:AdaptationSet/@audioSamplingRate)) or (not(dash:AudioChannelConfiguration) and not(parent::dash:AdaptationSet/dash:AudioChannelConfiguration)))) then false() else true()"> DASH-IF IOP Section 3.2.4: "For any Representation within an Adaptation Set with value of the @contentType="audio" the following elements and attributes shall be present: @audioSamplingRate, if not present in AdaptationSet element; AudioChannelConfiguration, if not present in AdaptationSet element" violated here</assert >                        
                        <!-- RD5.4	DASH-IF -->
                        <assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((count(tokenize(@codecs, ',')) > 1) or (count(tokenize(parent::dash:AdaptationSet/@codecs, ',')) > 1))) then false() else true()"> If profiles contain dash-if profile identifier, "codecs" attribute on AdaptationSet level OR Representation level shall not contain more than one identifiers as a comma separated list</assert >
                        
                        <!--Dual-Stream Dolby Vision check DASHIF 10.4.3-->
                        <let name="codec" value="substring-before(ancestor-or-self::*/@codecs[1],'\.')"/>
			<assert test="if($codec = ('dvhe','dvav') and @dependencyId and not(@dependencyId = preceding-sibling::dash:Representation/@id))then false() else true()">The @dependencyId attribute on the Enhancement Layer Representation shall refer to the Base Layer Representation @id attribute.</assert>
<!--			<assert test="if(not($codec = ('dvhe','dvav')) or (@dependencyId and not(@dependencyId = preceding-sibling::dash:Representation/@id)))then false() else true()">The @dependencyId attribute on the Enhancement Layer Representation shall refer to the Base Layer Representation @id attribute.</assert>-->
			<!-- DASH-IF Section 5.3.7.2 -->
                        <assert test="if (@profiles and ((parent::dash:AdaptationSet/@profiles and not(contains(parent::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()">The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</assert>
                        
                        <!-- RD5.5	DASH-IF IOP 4.3 Section 3.2.1 -->
                        <assert test="if (((@profiles and contains(@profiles, 'http://dashif.org/guidelines/dash')) or (not(@profiles) and parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash'))) and ((@profiles and contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(@profiles) and parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011'))) and (not(dash:SegmentBase) and not(parent::dash:AdaptationSet/dash:SegmentBase) and not(ancestor::dash:Period/dash:SegmentBase))) then false() else true()">DASH-IF IOP 4.3 Section 3.2.1 - "For on-demand profiles, @indexRange attribute shall be present.</assert>
		</rule>
	</pattern>
        <pattern>
		<title>SubRepresentation element</title>          
		<!-- R6.*: Check the conformance of SubRepresentation -->
		<rule context="dash:SubRepresentation">
			
                        <!-- DASH-IF Section 5.3.7.2 -->
                        <assert test="if (@profiles and ((parent::dash:Representation/@profiles and not(contains(parent::dash:Representation/@profiles, @profiles))) or (ancestor::dash:AdaptationSet/@profiles and not(contains(ancestor::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()">The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</assert>
		</rule>
	</pattern>
        <pattern>
		<title>SegmentBase element</title>          
            <!-- R9.*: Check the conformance of SegmentBase -->
            <rule context="dash:SegmentBase">
                    <!-- R9.2	DASH-IF IOP 4.3 Section 3.2.1 -->
                    <assert test="if (((parent::dash:Representation and ((parent::dash:Representation/@profiles and contains(parent::dash:Representation/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:Representation/@profiles) and (ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:Representation/@profiles) and not(ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash')))) or (parent::dash:AdaptationSet and ((parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash')))) or (parent::dash:Period and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash'))) and ((parent::dash:Representation and ((parent::dash:Representation/@profiles and contains(parent::dash:Representation/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:Representation/@profiles) and (ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:Representation/@profiles) and not(ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')))) or (parent::dash:AdaptationSet and ((parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')))) or (parent::dash:Period and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011'))) and not(@indexRange)) then false() else true()">DASH-IF IOP 4.3 Section 3.2.1 - "For on-demand profiles, @indexRange attribute shall be present.</assert>
            </rule>
        </pattern>
        <pattern>
		<title>ContentProtection element</title>          
		<!-- R12.*: Check the conformance of ContentProtection -->
		<rule context="dash:ContentProtection">
			<!--  R12.2  DASH-IF IOP v4.0-->
			<assert test="if (not(parent::dash:AdaptationSet)) then false() else true()">The ContentProtection descriptors shall always be present in the AdaptationSet element and apply to all contained Representations.
			</assert>
			<!--  R12.3  DASH-IF IOP v4.0-->
			<assert test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and (@value= 'cenc') and not(parent::dash:AdaptationSet)) then false() else true()">The ContentProtection descriptor for the mp4 protection scheme with @schemeIdUri 'urn:mpeg:dash:mp4protection:2011' and @value 'cenc' shall be present in the AdaptationSet element.
			</assert>
                        <!--  R12.3  DASH-IF IOP v4.2-->
                        <assert test="if ( contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(@cenc:default_KID) ) then false() else true()">The ContentProtection Descriptor for the mp4protection scheme shall contain the attribute @cenc:default_KID.
                        </assert>
		</rule>
	</pattern>
        <pattern>
		<title>AudioChannelConfiguration element</title>          
		<!-- R15.*: Check the conformance of AudioChannelConfiguration -->
		<rule context="dash:AudioChannelConfiguration">
			
                        <assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#ac-4') and not(@schemeIdUri='tag:dolby.com,2014:dash:audio_channel_configuration:2011')) then false() else true()"> If profile http://dashif.org/guidelines/dashif#ac-4 is used, then schemeIdUri attribute shall be tag:dolby.com,2014:dash:audio_channel_configuration:2011.</assert >
                        <assert test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#mpeg-h-3da') and not(@schemeIdUri='urn:mpeg:mpegB:cicp:ChannelConfiguration')) then false() else true()"> If profile http://dashif.org/guidelines/dashif#mpeg-h-3da is used, then schemeIdUri attribute shall be urn:mpeg:mpegB:cicp:ChannelConfiguration.</assert >
		</rule>
	</pattern>
        <pattern>
		<title>EssentialProperty element</title>
		<!-- R20.*: Check the conformance of EssentialProperty -->
                <rule context="dash:EssentialProperty">
                <!-- R20.1 -->
                    <assert  test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not((count(tokenize(@value, 'x'))=2)))then false() else true()"> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then value shall provide horizontal and vertical number of tiles separated by an 'x'. </assert>
                <!-- R20.2 -->
                    <assert  test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@bandwidth))then false() else true()"> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then bandwidth shall be used to describe the tiling.'. </assert>
                <!-- R20.3 -->
                    <assert  test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@height) and not(ancestor::dash:AdaptationSet/@height))then false() else true()"> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then height shall be used to describe the tiling. </assert>
                <!-- R20.4 -->
                    <assert  test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@width) and not(ancestor::dash:AdaptationSet/@width))then false() else true()"> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then width shall be used to describe the tiling. </assert>
     
                </rule>
        </pattern>

	<!-- DVB DASH specific assertions -->
	<extends href="dvb-dash.sch"/>
</schema>
