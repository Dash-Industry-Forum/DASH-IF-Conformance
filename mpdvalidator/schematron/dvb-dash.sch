<?xml version="1.0" encoding="UTF-8"?>
<!-- Assertions to test DVB DASH audio, 2017 profile -->
<schema xmlns="http://purl.oclc.org/dsdl/schematron" xmlns:dash="urn:mpeg:dash:schema:mpd:2011" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  queryBinding='xslt2' schemaVersion='ISO19757-3'>
	<ns prefix="dash" uri="urn:mpeg:dash:schema:mpd:2011"/>
	<ns prefix="xlink" uri="http://www.w3.org/1999/xlink"/>
	<ns prefix="xsi" uri="http://www.w3.org/2001/XMLSchema-instance"/>
	<ns prefix="dlb" uri="http://www.dolby.com/ns/2019/dash-if"/>

	<let name="dvbdash-profile-2017" value="'dvbdash-profile-2017'"/>
	<pattern>
		<title>AdaptationSet element for DVB DASH 2017 profile</title>
		<!-- Check the conformance of AdaptationSet -->
		<rule context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][not(dlb:isAuxiliaryStream(.))]/dash:Representation">
			<!--  see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=28" -->
			<report test="@mimeType != ancestor::dash:AdaptationSet/dash:Representation/@mimeType">@mimeType shall be common between all Representations in an Adaptation Set</report>
			<!-- see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=28" -->
			<report test="@codecs   != ancestor::dash:AdaptationSet/dash:Representation/@codecs" role="warn">@codecs should be common between all Representations in an Adaptation Set</report>
			<!-- see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=28" -->
			<report test="@audioSamplingRate != ancestor::dash:AdaptationSet/dash:Representation/@audioSamplingRate" role="warn">@audioSamplingRate should be common between all Representations in an Adaptation Set</report>
		</rule>
		<rule context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][not(dlb:isAuxiliaryStream(.))]/dash:Representation/dash:AudioChannelConfiguration">
			<let name="siu" value="@schemeIdUri"/>
			<let name="val" value="@value"/>
			<!-- see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=28" -->
			<report test="$val != ancestor::dash:AdaptationSet/dash:Representation/dash:AudioChannelConfiguration[@schemeIdUri = $siu]/@value" role="warn">audioChannelConfiguration should be common between all Representations in an Adaptation Set</report>
		</rule>
		<rule context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][dlb:isAuxiliaryStream(.)]">
			<!-- see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=37" -->
			<report test="dash:AudioChannelConfiguration or dash:Role or dash:Accessibility or @lang">All Adaptation Sets that refer to Auxiliary Audio streams may not contain the @lang attribute and Role,
				Accessibility, AudioChannelConfiguration descriptors</report>
		</rule>
	</pattern>

	<pattern>
		<title>Preselection element for DVB DASH 2017 profile</title>
		<rule context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:Preselection[dlb:isAdaptationSetAudio(.)]">
			<assert test="dash:Role[@schemeIdUri='urn:mpeg:dash:role:2011']">Every AC-4 or MPEG-H Audio Preselection element shall include at least one Role element using the scheme
				"urn:mpeg:dash:role:2011" as defined in ISO/IEC 23009-1 [1].</assert>

			<!-- the ID of the bundle is that ID which points to an AdapationSet which is not an Auxiliary -->
			<let name="psc" value="tokenize(@preselectionComponents,' ')"/>
			<let name="bundleID" value="../dash:AdaptationSet[@id = $psc][not(dlb:isAuxiliaryStream(.))]/@id"/>

			<!-- If there is more than one preselection in this bundle, at least one must be main -->
			<!-- see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=37" -->
			<report test="count(../dash:Preselection[$bundleID = tokenize(@preselectionComponents,' ')]) &gt; 1 and not(../dash:Preselection[$bundleID = tokenize(@preselectionComponents,' ')]/dash:Role[@schemeIdUri='urn:mpeg:dash:role:2011'][@value='main'])">If there is more than one audio Preselection associated with an audio bundle, at least one of the Preselection
				elements shall be tagged with an @value set to "main".</report>

			<!-- ISO/IEC 23009-1, 3rd edition, clause 5.3.11.3 -->
			<report test="some $x in tokenize(@preselectionComponents,' ') satisfies not($x = preceding-sibling::dash:AdaptationSet/@id)"
				diagnostics="preselID">
				@preselectionComponents specifies the ids of the contained Adaptation Sets or Content Components that belong to this Preselection
				as white space separated list in processing order.
			</report>
		</rule>
	</pattern>
	<diagnostics>
		<diagnostic id="preselID">A preselectionComponent references a non existent AdaptationSet</diagnostic>
	</diagnostics>

	<pattern>
		<title>AdaptationSet and Preselection element for AC-4 for DVB DASH 2017 profile</title>
		<!-- Check the conformance of AdaptationSet -->
		<rule context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]//*[self::dash:AdaptationSet or self::dash:Preselection or self::Representation][dlb:isAdaptationSetAC4(.)]">
			<!-- TS 103190-1, F1.2.1 and TS 103190-2, G2.3 and E.13 -->
			<let name="cod" value="tokenize(dlb:getNearestCodecString(.),'\.')"/>
			<let name="bs_ver" value="$cod[2]"/>
			<let name="pres_ver" value="$cod[3]"/>
			<let name="md_compat" value="$cod[4]"/>

			<!-- see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=32" -->
			<assert test="$bs_ver = '02'">AC-4 audio should be encoded using bitstream_version = 2 (is <value-of select="$bs_ver"/>).</assert>
			<!-- see="https://www.etsi.org/deliver/etsi_ts/101100_101199/101154/02.04.01_60/ts_101154v020401p.pdf#page=161" -->
			<assert test="$pres_ver = '01'">The presentation_version field according to clause 6.2.1.3 of ETSI TS 103 190-2 [46] shall be set
				to the value 1.</assert>
			<!-- see="https://www.etsi.org/deliver/etsi_ts/101100_101199/101154/02.04.01_60/ts_101154v020401p.pdf#page=161" -->
			<assert test="$md_compat = ('00','01','02','03')">The md_compat field as defined by clause 6.3.2.2.3 of ETSI TS 103 190-2 [46] shall be less than or
				equal to three.</assert>
			<!-- see="https://www.etsi.org/deliver/etsi_ts/101100_101199/101154/02.04.01_60/ts_101154v020401p.pdf#page=161" -->
			<assert test="@audioSamplingRate = (48000,96000,192000)">An AC-4 elementary stream shall be encoded with a sampling rate of 48 kHz, 96 kHz or 192 kHz.</assert>

			<!-- Test frame rate -->
			<!-- see="https://www.etsi.org/deliver/etsi_ts/103200_103299/103285/01.02.01_60/ts_103285v010201p.pdf#page=32" -->
			<assert test="not(*[self::dash:SupplementalProperty or self::dash:EssentialProperty][@schemeIdUri = 'tag:dolby.com,2017:dash:audio_frame_rate:2017'])
				or *[self::dash:SupplementalProperty or self::dash:EssentialProperty][@schemeIdUri = 'tag:dolby.com,2017:dash:audio_frame_rate:2017']/@value &lt;= 60
				or not(ancestor::dash:Period/dash:AdaptationSet[dlb:isAdaptationSetVideo(.)]/@frameRate &lt;= 60)">An Adaptation Set shall not contain audio with a frame rate > 60 Hz unless all video adaptationSets in
				the Period contain only video with a frame rate > 60 Hz.</assert>
		</rule>
	</pattern>

	<!-- The normative requirements of datatypes in DVB DASH apply as well. Pull in generic AC-4 tests -->
	<extends href="ac4-generic.sch"/>
</schema>
