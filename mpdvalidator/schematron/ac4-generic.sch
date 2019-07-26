<?xml version="1.0" encoding="UTF-8"?>
<schema xmlns="http://purl.oclc.org/dsdl/schematron" xmlns:dash="urn:mpeg:dash:schema:mpd:2011" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  queryBinding='xslt2' schemaVersion='ISO19757-3'>
	<ns prefix="dash" uri="urn:mpeg:dash:schema:mpd:2011"/>
	<ns prefix="xlink" uri="http://www.w3.org/1999/xlink"/>
	<ns prefix="xsi" uri="http://www.w3.org/2001/XMLSchema-instance"/>
	<ns prefix="dlb" uri="http://www.dolby.com/ns/2019/dash-if"/>

	<let name="NSDLB_acc2014" value="'tag:dolby.com,2014:dash:audio_channel_configuration:2011'"/>
	<let name="NSDLB_acc2015" value="'tag:dolby.com,2015:dash:audio_channel_configuration:2015'"/>
	<let name="NSMPEG_acc" value="'urn:mpeg:mpegB:cicp:ChannelConfiguration'"/>
	<let name="AC4_MIME" value="'(ac-4((\.02\.01\.0[0-3])|(\.00\.00\.0[0-3])))'"/>
	<let name="delim" value="'(^|\s+)'"/>
	<pattern>
		<title>AdaptationSet and Preselection element for AC-4</title>
		<!-- Check the conformance of AdaptationSet -->
		<rule context="*[self::dash:AdaptationSet or self::dash:Preselection][dlb:isAdaptationSetAC4(.)]">
			<let name="codecs" value="dlb:getNearestCodecString(.)"/>
			<let name="mstring" value="concat('^\s*(',$AC4_MIME,$delim,')+$')"/>

			<!-- TS 103190-1, F.1.2.1 and TS 103190-2, G.2.3 and E.13 -->
			<assert test="matches($codecs,concat('(',$delim,$AC4_MIME,')+$'),'i')">The @codecs attribute shall conform to the syntax described in IETF RFC 6381. The value of the parameter
				shall be set to a dot-separated list of four parts of which the last three are two-digit hexadecimal numbers.</assert>

			<let name="cod" value="tokenize($codecs,'\.')"/>
			<let name="bs_ver" value="$cod[2]"/>
			<let name="pres_ver" value="$cod[3]"/>
			<let name="md_compat" value="$cod[4]"/>

			<!-- TS 103190-1, F.1.2.1 -->
			<report test="$bs_ver = '00' and @mimeType != ('audio/mp4','video/mp4')">The value of the mimeType attribute shall be set to 'audio/mp4' or 'video/mp4'.</report>
			<!-- TS 103190-2, G.2.6 -->
			<report test="matches($codecs, 'ac-4\.(01|02)','i') and not(@audioSamplingRate)">@audioSamplingRate shall be set to the sampling frequency derived from the parameters fs_index and
				dsi_sf_multiplier, contained in ac4_dsi_v1.</report>
			<!-- TS 103190-2, G.2.7 -->
			<report test="$bs_ver = ('01','02') and @mimeType != 'audio/mp4'">The value of the mimeType attribute shall be set to 'audio/mp4'.</report>
			<!-- TS 103190-2, G.2.8 -->
			<report test="$bs_ver = ('01','02') and @startWithSAP != '1'">The @startWithSAP value shall be set to '1'.</report>
		</rule>
	</pattern>

	<pattern>
		<title>Representation for AC-4</title>
		<rule context="dash:Representation[matches(dlb:getNearestCodecString(.), 'ac-4\.00','i')]">
			<!-- TS 103190-1, F.1.2.3 -->
			<assert test="dash:AudioChannelConfiguration">The representation DASH element shall include an AudioChannelConfiguration DASH descriptor.</assert>
		</rule>
	</pattern>

	<pattern>
		<title>Role element for AC-4</title>
		<rule context="dash:Role[matches(dlb:getNearestCodecString(.), 'ac-4\.00')]">
			<!-- TS 103 190-1 F.1.3.1 -->
			<report test="@schemeIdUri = 'urn:mpeg:dash:role:2011' and
				not(@value = ('main','alternate','commentary','dub'))">The value of Role (role) shall be main, alternate, commentary.</report>
		</rule>
	</pattern>

	<pattern>
		<title>AC-4 supplemental property descriptors</title>
		<!-- TS 103 190-2 G.2.12.1 -->
		<rule context="dash:SupplementalProperty[@schemeIdUri = 'tag:dolby.com,2016:dash:virtualized_content:2016'][matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')]">
			<assert test="@value='1'">The @value attribute of the immersive audio for headphones descriptor shall be 1</assert>
		</rule>
	</pattern>

	<pattern>
		<title>AudioChannelConfiguration element for AC-4 part 1</title>

		<rule context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.00')][@schemeIdUri eq $NSDLB_acc2014]">
			
			<!-- AC-4 Representations should contain or inherit exactly one AudioChannelConfiguration descriptor -->
			<!-- TS 103 190-1, F.1.2.3 -->
			<report test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2014,$NSMPEG_acc)]) &gt; 1" role="warn">
				<xsl:text>It is recommended to use exactly one AudioChannelConfiguration element with a schemeIdURI of </xsl:text>
				<value-of select="$NSDLB_acc2014"/> or <value-of select="$NSMPEG_acc"/>
			</report>

			<!-- TS 103 190-1, F.1.4.1 -->
			<assert test="matches(@value,'^[0-9a-fA-F]{4}$')">The value element shall contain a four-digit hexadecimal representation of the 16-bit field which describes
				the channel assignment of the referenced AC-4 elementary stream</assert>
			<let name="val6" value="concat('00',@value)"/>
			<let name="x" value="dlb:dlb2mpg($val6)"/>
			<report test="$x != 0">Use &lt;<name/> schemeIdUri="<value-of select="$NSMPEG_acc"/>" value="<value-of select="$x"/>"/&gt;</report>
		</rule>
	</pattern>

	<pattern>
		<title>AudioChannelConfiguration element for AC-4 part 2</title>
		
		<!-- check that only specified schemeIdUris are used -->
		<!-- TS 103 190-2, G.2.5 -->
		<rule context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][not(@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc))]">
			<report test="true()" role="warn">
				Unspecified schemeIdUri in AudioChannelConfiguration element
			</report>
		</rule>

		<rule context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][@schemeIdUri eq $NSDLB_acc2015]">
			<!-- TS 103 190-2, G.2.5 -->
			<!-- AC-4 Representations should contain or inherit exactly one AudioChannelConfiguration descriptor -->
			<report test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc)]) &gt; 1" role="warn">
				<xsl:text>It is recommended to use exactly one AudioChannelConfiguration element with a schemeIdURI of </xsl:text>
				<xsl:text></xsl:text>
				<value-of select="$NSDLB_acc2015"/> or <value-of select="$NSMPEG_acc"/>
			</report>

			<!-- TS 103 190-2, G.3.1 -->
			<!-- AudioChannelConfiguration descriptors with schemeIDUri tag:dolby.com,2014:dash:audio_channel_configuration:2011 shall be of certain format -->
			<assert test="matches(@value,'^[0-9a-fA-F]{6}$')">The value element shall contain a six-digit hexadecimal representation of the 24-bit field which describes
				the channel assignment of the referenced AC-4 elementary stream</assert>

			<!-- TS 103 190-2, G.2.5 -->
			<let name="x" value="dlb:dlb2mpg(@value)"/>
			<report test="$x != 0">For all AC-4 channel configurations that are mappable to the MPEG channel configuration scheme, the scheme described by
				@schemeIdUri="urn:mpeg:mpegB:cicp:ChannelConfiguration" shall be used</report>
		</rule>

		<rule context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][@schemeIdUri eq $NSMPEG_acc]">
			<!-- TS 103 190-2, G.2.5 -->
			<!-- AC-4 Representations should contain or inherit exactly one AudioChannelConfiguration descriptor -->
			<report test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc)]) &gt; 1" role="warn">
				<xsl:text>It is recommended to use exactly one AudioChannelConfiguration element with a schemeIdURI of </xsl:text>
				<xsl:text></xsl:text>
				<value-of select="$NSDLB_acc2015"/> or <value-of select="$NSMPEG_acc"/>
			</report>

			<!-- TS 103 190-2, G.3.2 -->
			<!-- AudioChannelConfiguration descriptors with schemeIDUri urn:mpeg:mpegB:cicp:ChannelConfiguration shall be of certain format -->
			<assert test="matches(@value,'^[0-9]+$') and @value = (1 to 7,9 to 12,14,16 to 17,19)">Valid values are 1-7, 9-12, 14, 16-17, and 19</assert>
		</rule>
	</pattern>
</schema>
