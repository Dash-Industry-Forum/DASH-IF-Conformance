<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<xsl:stylesheet xmlns:sch="http://www.ascc.net/xml/schematron"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:iso="http://purl.oclc.org/dsdl/schematron"
                xmlns:dash="urn:mpeg:dash:schema:mpd:2011"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                version="1.0"><!--Implementers: please note that overriding process-prolog or process-root is 
    the preferred method for meta-stylesheets to use where possible. -->
<xsl:param name="archiveDirParameter"/>
   <xsl:param name="archiveNameParameter"/>
   <xsl:param name="fileNameParameter"/>
   <xsl:param name="fileDirParameter"/>

   <!--PHASES-->


<!--PROLOG-->
<xsl:output xmlns:xs="http://www.w3.org/2001/XMLSchema"
               xmlns:schold="http://www.ascc.net/xml/schematron"
               xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
               method="xml"
               omit-xml-declaration="no"
               standalone="yes"
               indent="yes"/>

   <!--KEYS-->


<!--DEFAULT RULES-->


<!--MODE: SCHEMATRON-SELECT-FULL-PATH-->
<!--This mode can be used to generate an ugly though full XPath for locators-->
<xsl:template match="*" mode="schematron-select-full-path">
      <xsl:apply-templates select="." mode="schematron-get-full-path"/>
   </xsl:template>

   <!--MODE: SCHEMATRON-FULL-PATH-->
<!--This mode can be used to generate an ugly though full XPath for locators-->
<xsl:template match="*" mode="schematron-get-full-path">
      <xsl:apply-templates select="parent::*" mode="schematron-get-full-path"/>
      <xsl:text>/</xsl:text>
      <xsl:choose>
         <xsl:when test="namespace-uri()=''">
            <xsl:value-of select="name()"/>
            <xsl:variable name="p_1" select="1+    count(preceding-sibling::*[name()=name(current())])"/>
            <xsl:if test="$p_1&gt;1 or following-sibling::*[name()=name(current())]">[<xsl:value-of select="$p_1"/>]</xsl:if>
         </xsl:when>
         <xsl:otherwise>
            <xsl:text>*[local-name()='</xsl:text>
            <xsl:value-of select="local-name()"/>
            <xsl:text>' and namespace-uri()='</xsl:text>
            <xsl:value-of select="namespace-uri()"/>
            <xsl:text>']</xsl:text>
            <xsl:variable name="p_2"
                          select="1+   count(preceding-sibling::*[local-name()=local-name(current())])"/>
            <xsl:if test="$p_2&gt;1 or following-sibling::*[local-name()=local-name(current())]">[<xsl:value-of select="$p_2"/>]</xsl:if>
         </xsl:otherwise>
      </xsl:choose>
   </xsl:template>
   <xsl:template match="@*" mode="schematron-get-full-path">
      <xsl:text>/</xsl:text>
      <xsl:choose>
         <xsl:when test="namespace-uri()=''">@<xsl:value-of select="name()"/>
         </xsl:when>
         <xsl:otherwise>
            <xsl:text>@*[local-name()='</xsl:text>
            <xsl:value-of select="local-name()"/>
            <xsl:text>' and namespace-uri()='</xsl:text>
            <xsl:value-of select="namespace-uri()"/>
            <xsl:text>']</xsl:text>
         </xsl:otherwise>
      </xsl:choose>
   </xsl:template>

   <!--MODE: SCHEMATRON-FULL-PATH-2-->
<!--This mode can be used to generate prefixed XPath for humans-->
<xsl:template match="node() | @*" mode="schematron-get-full-path-2">
      <xsl:for-each select="ancestor-or-self::*">
         <xsl:text>/</xsl:text>
         <xsl:value-of select="name(.)"/>
         <xsl:if test="preceding-sibling::*[name(.)=name(current())]">
            <xsl:text>[</xsl:text>
            <xsl:value-of select="count(preceding-sibling::*[name(.)=name(current())])+1"/>
            <xsl:text>]</xsl:text>
         </xsl:if>
      </xsl:for-each>
      <xsl:if test="not(self::*)">
         <xsl:text/>/@<xsl:value-of select="name(.)"/>
      </xsl:if>
   </xsl:template>

   <!--MODE: GENERATE-ID-FROM-PATH -->
<xsl:template match="/" mode="generate-id-from-path"/>
   <xsl:template match="text()" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.text-', 1+count(preceding-sibling::text()), '-')"/>
   </xsl:template>
   <xsl:template match="comment()" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.comment-', 1+count(preceding-sibling::comment()), '-')"/>
   </xsl:template>
   <xsl:template match="processing-instruction()" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.processing-instruction-', 1+count(preceding-sibling::processing-instruction()), '-')"/>
   </xsl:template>
   <xsl:template match="@*" mode="generate-id-from-path">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:value-of select="concat('.@', name())"/>
   </xsl:template>
   <xsl:template match="*" mode="generate-id-from-path" priority="-0.5">
      <xsl:apply-templates select="parent::*" mode="generate-id-from-path"/>
      <xsl:text>.</xsl:text>
      <xsl:value-of select="concat('.',name(),'-',1+count(preceding-sibling::*[name()=name(current())]),'-')"/>
   </xsl:template>
   <!--MODE: SCHEMATRON-FULL-PATH-3-->
<!--This mode can be used to generate prefixed XPath for humans 
	(Top-level element has index)-->
<xsl:template match="node() | @*" mode="schematron-get-full-path-3">
      <xsl:for-each select="ancestor-or-self::*">
         <xsl:text>/</xsl:text>
         <xsl:value-of select="name(.)"/>
         <xsl:if test="parent::*">
            <xsl:text>[</xsl:text>
            <xsl:value-of select="count(preceding-sibling::*[name(.)=name(current())])+1"/>
            <xsl:text>]</xsl:text>
         </xsl:if>
      </xsl:for-each>
      <xsl:if test="not(self::*)">
         <xsl:text/>/@<xsl:value-of select="name(.)"/>
      </xsl:if>
   </xsl:template>

   <!--MODE: GENERATE-ID-2 -->
<xsl:template match="/" mode="generate-id-2">U</xsl:template>
   <xsl:template match="*" mode="generate-id-2" priority="2">
      <xsl:text>U</xsl:text>
      <xsl:number level="multiple" count="*"/>
   </xsl:template>
   <xsl:template match="node()" mode="generate-id-2">
      <xsl:text>U.</xsl:text>
      <xsl:number level="multiple" count="*"/>
      <xsl:text>n</xsl:text>
      <xsl:number count="node()"/>
   </xsl:template>
   <xsl:template match="@*" mode="generate-id-2">
      <xsl:text>U.</xsl:text>
      <xsl:number level="multiple" count="*"/>
      <xsl:text>_</xsl:text>
      <xsl:value-of select="string-length(local-name(.))"/>
      <xsl:text>_</xsl:text>
      <xsl:value-of select="translate(name(),':','.')"/>
   </xsl:template>
   <!--Strip characters--><xsl:template match="text()" priority="-1"/>

   <!--SCHEMA METADATA-->
<xsl:template match="/">
      <svrl:schematron-output xmlns:xs="http://www.w3.org/2001/XMLSchema"
                              xmlns:schold="http://www.ascc.net/xml/schematron"
                              xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                              title="Schema for validating MPDs"
                              schemaVersion="ISO19757-3">
         <xsl:comment>
            <xsl:value-of select="$archiveDirParameter"/>   
		 <xsl:value-of select="$archiveNameParameter"/>  
		 <xsl:value-of select="$fileNameParameter"/>  
		 <xsl:value-of select="$fileDirParameter"/>
         </xsl:comment>
         <svrl:ns-prefix-in-attribute-values uri="urn:mpeg:dash:schema:mpd:2011" prefix="dash"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/1999/xlink" prefix="xlink"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/2001/XMLSchema-instance" prefix="xsi"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M4"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M5"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M6"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M7"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M8"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M9"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M10"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M11"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M12"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M13"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M14"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M15"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M16"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M17"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M18"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M19"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M20"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M21"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M22"/>
         <svrl:active-pattern>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M23"/>
      </svrl:schematron-output>
   </xsl:template>

   <!--SCHEMATRON PATTERNS-->
<svrl:text xmlns:xs="http://www.w3.org/2001/XMLSchema"
              xmlns:schold="http://www.ascc.net/xml/schematron"
              xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Schema for validating MPDs</svrl:text>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:MPD" priority="1000" mode="M4">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:MPD"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@type = 'dynamic' and not(@availabilityStartTime)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'dynamic' and not(@availabilityStartTime)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "dynamic" availabilityStartTime shall be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@type = 'dynamic' and not(@publishTime)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'dynamic' and not(@publishTime)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "dynamic" publishTime shall be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@type = 'static' and @timeShiftBufferDepth and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'static' and @timeShiftBufferDepth and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "static" and if the profile contains a DASH-IF IOP profile, then the timeShiftBufferDepth shall not be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@type = 'static' and descendant::dash:Period[1]/@start and (years-from-duration(descendant::dash:Period[1]/@start) + months-from-duration(descendant::dash:Period[1]/@start) + days-from-duration(descendant::dash:Period[1]/@start) + hours-from-duration(descendant::dash:Period[1]/@start) + minutes-from-duration(descendant::dash:Period[1]/@start) +  seconds-from-duration(descendant::dash:Period[1]/@start)) &gt; 0) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'static' and descendant::dash:Period[1]/@start and (years-from-duration(descendant::dash:Period[1]/@start) + months-from-duration(descendant::dash:Period[1]/@start) + days-from-duration(descendant::dash:Period[1]/@start) + hours-from-duration(descendant::dash:Period[1]/@start) + minutes-from-duration(descendant::dash:Period[1]/@start) + seconds-from-duration(descendant::dash:Period[1]/@start)) &gt; 0) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "static" and the first period has a start attribute the start attribute shall be zero.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If mediaPresentationDuration is not defined for the MPD minimumUpdatePeriod shall be defined or vice versa.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@type = 'static' and @minimumUpdatePeriod and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'static' and @minimumUpdatePeriod and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "static" and if the profile contains a DASH-IF IOP profile, then the minimumUpdatePeriod shall not be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@profiles) or (contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:full:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-simple:2011') or contains (@profiles, 'http://dashif.org/guidelines/dashif#ac-4') or contains (@profiles, 'http://dashif.org/guidelines/dashif#mha1') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9-hdr') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9-hdr') or contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(@profiles, 'urn:dvb:dash:profile:dvb-dash:2014') or contains(@profiles, 'http://dashif.org/guidelines/dashif#ec-3'))) then true() else false()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@profiles) or (contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:full:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-simple:2011') or contains (@profiles, 'http://dashif.org/guidelines/dashif#ac-4') or contains (@profiles, 'http://dashif.org/guidelines/dashif#mha1') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9-hdr') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9-hdr') or contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(@profiles, 'urn:dvb:dash:profile:dvb-dash:2014') or contains(@profiles, 'http://dashif.org/guidelines/dashif#ec-3'))) then true() else false()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The On-Demand profile shall be identified by the URN "urn:mpeg:dash:profile:isoff-on-demand:2011". The live profile shall be identified by the URN "urn:mpeg:dash:profile:isoff-live:2011". The main profile shall be identified by the URN "urn:mpeg:dash:profile:isoff-main:2011". The full profile shall be identified by the URN "urn:mpeg:dash:profile:full:2011". The mp2t-main profile shall be identified by the URN "urn:mpeg:dash:profile:mp2t-main:2011". The mp2t-simple profile shall be identified by the URN "urn:mpeg:dash:profile:mp2t-simple:2011".The Dolby AC-4 profile shall be identified by "http://dashif.org/guidelines/dashif#ac-4". The multichannel audio extension with MPEG-H 3D Audio profile shall be identified by "http://dashif.org/guidelines/dashif#mha1". The VP9-HD profile shall be identified by "http://dashif.org/guidelines/dashif#vp9". The VP9-UHD profile shall be identified by "http://dashif.org/guidelines/dash-if-uhd#vp9". The VP9-HDR profile is identified by "http://dashif.org/guidelines/dashif#vp9-hdr" or "http://dashif.org/guidelines/dash-if-uhd#vp9-hdr". The DVB-DASH profile shall be identified by "urn:dvb:dash:profile:dvb-dash:2014". The HbbTV 1.5 profile shall be identified by "urn:hbbtv:dash:profile:isoff-live:2012". The DASH-IF multchannel audio extension with Enhanced AC-3 shall be identified by "http://dashif.org/guidelines/dashif#ec-3".</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or not(@type) or @type='static') then true() else false()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or not(@type) or @type='static') then true() else false()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>For On-Demand profile, the MPD @type shall be "static".</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod) and not(dash:Period[last()]/@duration)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod) and not(dash:Period[last()]/@duration)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If minimumUpdatePeriod is not present and the last period does not include the duration attribute the mediaPresentationDuration must be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @type='dynamic' and not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @type='dynamic' and not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP Section 3.2.2.2: For dynamic MPD, the @profile shall include urn:mpeg:dash:profile:isoff-live:2011. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M4"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M4"/>
   <xsl:template match="@*|node()" priority="-2" mode="M4">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M4"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:Period" priority="1000" mode="M5">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:Period"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (string(@bitstreamSwitching) = 'true' and string(child::dash:AdaptationSet/@bitstreamSwitching) = 'false') then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (string(@bitstreamSwitching) = 'true' and string(child::dash:AdaptationSet/@bitstreamSwitching) = 'false') then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If bitstreamSwitching is set to true all bitstreamSwitching declarations for AdaptationSet within this Period shall not be set to false.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@id = preceding::dash:Period/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding::dash:Period/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each Period shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in Period.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@id) and ancestor::dash:MPD/@type = 'dynamic') then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@id) and ancestor::dash:MPD/@type = 'dynamic') then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If the MPD is dynamic the Period element shall have an id.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(descendant-or-self::dash:BaseURL) and not(descendant-or-self::dash:SegmentTemplate) and not(descendant-or-self::dash:SegmentList) and not(@xlink:href = 'urn:mpeg:dash:resolve-to-zero:2013')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(descendant-or-self::dash:BaseURL) and not(descendant-or-self::dash:SegmentTemplate) and not(descendant-or-self::dash:SegmentList) and not(@xlink:href = 'urn:mpeg:dash:resolve-to-zero:2013')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>At least one BaseURL, SegmentTemplate or SegmentList shall be defined in Period, AdaptationSet or Representation.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@duration = 0 and count(child::dash:AdaptationSet)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@duration = 0 and count(child::dash:AdaptationSet)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If the duration attribute is set to zero, there should only be a single AdaptationSet present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and dash:SegmentList) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and dash:SegmentList) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP Section 3.2.2: "the Period.SegmentList element shall not be present" violated here </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (count(child::dash:AdaptationSet[@contentType='video']) &gt; 1) and (count(descendant::dash:Role[@value='main'])=0)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (count(child::dash:AdaptationSet[@contentType='video']) &gt; 1) and (count(descendant::dash:Role[@value='main'])=0)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.2: "If a Period contains multiple Adaptation Sets with value of the @contentType="video" then at least one Adaptation Set shall contain a Role el-ement $&lt;$Role scheme="urn:mpeg:dash:role:2011" value="main"&gt;" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') and (child::dash:SegmentList or child::dash:SegmentTemplate)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') and (child::dash:SegmentList or child::dash:SegmentTemplate)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Neither the Period.SegmentList element nor the Period.SegmentTemplate element shall be present for On-Demand profile, violated here. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M5"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M5"/>
   <xsl:template match="@*|node()" priority="-2" mode="M5">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M5"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:AdaptationSet" priority="1000" mode="M6">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AdaptationSet"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@id = preceding-sibling::dash:AdaptationSet/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding-sibling::dash:AdaptationSet/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each AdaptationSet within a Period shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((descendant::dash:SupplementalProperty/@value = following-sibling::dash:AdaptationSet/@id) and (@segmentAlignment='true') and (following-sibling::dash:AdaptationSet/@segmentAlignment = 'false')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((descendant::dash:SupplementalProperty/@value = following-sibling::dash:AdaptationSet/@id) and (@segmentAlignment='true') and (following-sibling::dash:AdaptationSet/@segmentAlignment = 'false')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If the content author signals the ability of Adaptation Set switching and as @segmentAlignment or @subsegmentAlignment are set to TRUE for one Adaptation Set, the (Sub)Segment alignment shall hold for all Representations in all Adaptation Sets for which the @id value is included in the @value attribute of the Supplemental descriptor.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@lang = descendant::dash:ContentComponent/@lang) or (@contentType = descendant::dash:ContentComponent/@contentType) or (@par = descendant::dash:ContentComponent/@par)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@lang = descendant::dash:ContentComponent/@lang) or (@contentType = descendant::dash:ContentComponent/@contentType) or (@par = descendant::dash:ContentComponent/@par)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Attributes from the AdaptationSet shall not be repeated in the descendanding ContentComponent elements.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@profiles and descendant::dash:Representation/@profiles) or (@width and descendant::dash:Representation/@width) or (@height and descendant::dash:Representation/@height) or (@sar and descendant::dash:Representation/@sar) or (@frameRate and descendant::dash:Representation/@frameRate) or (@audioSamplingRate and descendant::dash:Representation/@audioSamplingRate) or (@mimeType and descendant::dash:Representation/@mimeType) or (@segmentProfiles and descendant::dash:Representation/@segmentProfiles) or (@codecs and descendant::dash:Representation/@codecs) or (@maximumSAPPeriod and descendant::dash:Representation/@maximumSAPPeriod) or (@startWithSAP and descendant::dash:Representation/@startWithSAP) or (@maxPlayoutRate and descendant::dash:Representation/@maxPlayoutRate) or (@codingDependency and descendant::dash:Representation/@codingDependency) or (@scanType and descendant::dash:Representation/@scanType)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@profiles and descendant::dash:Representation/@profiles) or (@width and descendant::dash:Representation/@width) or (@height and descendant::dash:Representation/@height) or (@sar and descendant::dash:Representation/@sar) or (@frameRate and descendant::dash:Representation/@frameRate) or (@audioSamplingRate and descendant::dash:Representation/@audioSamplingRate) or (@mimeType and descendant::dash:Representation/@mimeType) or (@segmentProfiles and descendant::dash:Representation/@segmentProfiles) or (@codecs and descendant::dash:Representation/@codecs) or (@maximumSAPPeriod and descendant::dash:Representation/@maximumSAPPeriod) or (@startWithSAP and descendant::dash:Representation/@startWithSAP) or (@maxPlayoutRate and descendant::dash:Representation/@maxPlayoutRate) or (@codingDependency and descendant::dash:Representation/@codingDependency) or (@scanType and descendant::dash:Representation/@scanType)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Common attributes for AdaptationSet and Representation shall either be in one of the elements but not in both.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@minWidth &gt; @maxWidth) or (@minHeight &gt; @maxHeight) or (@minBandwidth &gt; @maxBandwidth)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@minWidth &gt; @maxWidth) or (@minHeight &gt; @maxHeight) or (@minBandwidth &gt; @maxBandwidth)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Each minimum value (minWidth, minHeight, minBandwidth) shall be larger than the maximum value.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (descendant::dash:Representation/@bandwidth &lt; @minBandwidth or descendant::dash:Representation/@bandwidth &gt; @maxBandwidth) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (descendant::dash:Representation/@bandwidth &lt; @minBandwidth or descendant::dash:Representation/@bandwidth &gt; @maxBandwidth) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of the bandwidth attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (descendant::dash:Representation/@width &gt; @maxWidth) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (descendant::dash:Representation/@width &gt; @maxWidth) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of the width attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (descendant::dash:Representation/@height &gt; @maxHeight) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (descendant::dash:Representation/@height &gt; @maxHeight) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of the height attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (count(child::dash:Representation)=0) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (count(child::dash:Representation)=0) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>An AdaptationSet shall have at least one Representation element.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@minFrameRate and (descendant::dash:Representation/@frameRate &lt; @minFrameRate)) or (@maxFrameRate and (descendant::dash:Representation/@frameRate &gt; @maxFrameRate))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@minFrameRate and (descendant::dash:Representation/@frameRate &lt; @minFrameRate)) or (@maxFrameRate and (descendant::dash:Representation/@frameRate &gt; @maxFrameRate))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>ISO/IEC 23009-1 Section 5.3.3.2: The value of the frameRate attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@par)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@par)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="video" the following attributes shall be present: @par" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and (@scanType and not(@scanType='progressive'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and (@scanType and not(@scanType='progressive'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For Adaptation Set or for any Representation within an Adaptation Set with value of the @contentType="video" the attribute @scanType shall either not be present or shall be set to 'progressive' ", violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='audio' and not(@lang)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='audio' and not(@lang)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="audio" the following attributes shall be present: @lang" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxWidth) and not(@width)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxWidth) and not(@width)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxWidth or @width" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxHeight) and not(@height)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxHeight) and not(@height)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxHeight or @height" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxFrameRate) and not(@frameRate)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxFrameRate) and not(@frameRate)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxFrameRate or @frameRate" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011' and (not(@segmentAlignment) or @segmentAlignment='false'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011' and (not(@segmentAlignment) or @segmentAlignment='false'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.2.2: For Live Profile @segmentAlignment shall be set to true for all Adaptation Sets</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-ondemand:2011' and (not(@subSegmentAlignment) or @subSegmentAlignment='false'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-ondemand:2011' and (not(@subSegmentAlignment) or @subSegmentAlignment='false'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.2.2: For On-Demand Profile @subSegmentAlignment shall be set to true for all Adaptation Sets</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@mimeType and not((@mimeType = 'video/mp4') or (@mimeType = 'audio/mp4') or (@mimeType = 'application/mp4') or (@mimeType = 'application/ttml+xml') or (@mimeType = 'text/vtt') or (@mimeType = 'image/jpeg'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@mimeType and not((@mimeType = 'video/mp4') or (@mimeType = 'audio/mp4') or (@mimeType = 'application/mp4') or (@mimeType = 'application/ttml+xml') or (@mimeType = 'text/vtt') or (@mimeType = 'image/jpeg'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If a DASH-IF profile identifier is present, for the Adaptation Sets the mimeType shall be one of the six following type: "video/mp4", "audio/mp4", "application/mp4", "application/ttml+xml", "text/vtt" or "image/jpeg"</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentAlignment = 'true')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentAlignment = 'true')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentAlignment' as true</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '1' or @subsegmentStartsWithSAP = '2')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '1' or @subsegmentStartsWithSAP = '2')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 1 or 2</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '3') and not (count(child::dash:Representation) &gt; 1)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '3') and not (count(child::dash:Representation) &gt; 1)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 3 while not containing more than one Representation</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((@profiles and contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((@profiles and contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - For HbbTV profile, @segmentAlignment shall be set to true for all Adaptation Sets as it is based on live profile</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M6"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M6"/>
   <xsl:template match="@*|node()" priority="-2" mode="M6">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M6"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:ContentComponent" priority="1000" mode="M7">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:ContentComponent"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@id = preceding-sibling::dash:ContentComponent/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding-sibling::dash:ContentComponent/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each ContentComponent within an AdaptationSet shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M7"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M7"/>
   <xsl:template match="@*|node()" priority="-2" mode="M7">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M7"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:Representation" priority="1000" mode="M8">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:Representation"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@mimeType) and not(parent::dash:AdaptationSet/@mimeType)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@mimeType) and not(parent::dash:AdaptationSet/@mimeType)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Either the Representation or the containing AdaptationSet shall have the mimeType attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@mimeType and following-sibling::dash:Representation/@mimeType and not(following-sibling::dash:Representation/@mimeType = @mimeType)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@mimeType and following-sibling::dash:Representation/@mimeType and not(following-sibling::dash:Representation/@mimeType = @mimeType)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP (v3.3), Section 3.2.13 : "In contrast to MPEG-DASH which does not prohibit the use of multiplexed Representations, in the DASH-IF IOPs one Adaptation Set always contains exactly a single media type.".</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>For live profile, the SegmentTemplate element shall be present on at least one of the three levels, the Period level containing the Representation, the Adaptation Set containing the Representation, or on Representation level itself.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in Representation.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (((@width != preceding-sibling::dash:Representation/@width) and not(parent::dash:AdaptationSet/@maxWidth)) or ((@height != preceding-sibling::dash:Representation/@height) and not(parent::dash:AdaptationSet/@maxHeight)) or ((@frameRate != preceding-sibling::dash:Representation/@frameRate) and not(parent::dash:AdaptationSet/@maxFrameRate)))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (((@width != preceding-sibling::dash:Representation/@width) and not(parent::dash:AdaptationSet/@maxWidth)) or ((@height != preceding-sibling::dash:Representation/@height) and not(parent::dash:AdaptationSet/@maxHeight)) or ((@frameRate != preceding-sibling::dash:Representation/@frameRate) and not(parent::dash:AdaptationSet/@maxFrameRate)))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="video" the following attributes shall be present: @maxWidth (or @width if all Representations have the same width), @maxHeight (or @height if all Representations have the same width), @maxFrameRate (or @frameRate if all Representations have the same width)" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and ((not(@width) and not(parent::dash:AdaptationSet/@width)) or (not(@height) and not(parent::dash:AdaptationSet/@height)) or (not(@frameRate) and not(parent::dash:AdaptationSet/@frameRate)) or not(@sar))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and ((not(@width) and not(parent::dash:AdaptationSet/@width)) or (not(@height) and not(parent::dash:AdaptationSet/@height)) or (not(@frameRate) and not(parent::dash:AdaptationSet/@frameRate)) or not(@sar))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Representation within an Adaptation Set with value of the @contentType="video" the following attributes shall be present: @width, if not present in AdaptationSet element; @height, if not present in AdaptationSet element; @frameRate, if not present in AdaptationSet element; @sar" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (@scanType and not(@scanType='progressive')))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (@scanType and not(@scanType='progressive')))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For Adaptation Set or for any Representation within an Adaptation Set with value of the @contentType="video" the attribute @scanType shall either not be present or shall be set to 'progressive' ", violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='audio' and ((not(@audioSamplingRate) and not(parent::dash:AdaptationSet/@audioSamplingRate)) or (not(dash:AudioChannelConfiguration) and not(parent::dash:AdaptationSet/dash:AudioChannelConfiguration)))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='audio' and ((not(@audioSamplingRate) and not(parent::dash:AdaptationSet/@audioSamplingRate)) or (not(dash:AudioChannelConfiguration) and not(parent::dash:AdaptationSet/dash:AudioChannelConfiguration)))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Representation within an Adaptation Set with value of the @contentType="audio" the following elements and attributes shall be present: @audioSamplingRate, if not present in AdaptationSet element; AudioChannelConfiguration, if not present in AdaptationSet element" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((count(tokenize(@codecs, ',')) &gt; 1) or (count(tokenize(parent::dash:AdaptationSet/@codecs, ',')) &gt; 1))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((count(tokenize(@codecs, ',')) &gt; 1) or (count(tokenize(parent::dash:AdaptationSet/@codecs, ',')) &gt; 1))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> If profiles contain dash-if profile identifier, "codecs" attribute on AdaptationSet level OR Representation level shall not contain more than one identifiers as a comma separated list</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@id = preceding-sibling::dash:Representation/@id) or (@id=parent::dash:AdaptationSet/preceding-sibling::dash:AdaptationSet/dash:Representation/@id))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@id = preceding-sibling::dash:Representation/@id) or (@id=parent::dash:AdaptationSet/preceding-sibling::dash:AdaptationSet/dash:Representation/@id))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each Representation within a Period shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if(@dependencyId and not(@dependencyId = preceding-sibling::dash:Representation/@id))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if(@dependencyId and not(@dependencyId = preceding-sibling::dash:Representation/@id))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The @dependencyId attribute on the Enhancement Layer Representation shall refer to the Base Layer Representation @id attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@profiles and ((parent::dash:AdaptationSet/@profiles and not(contains(parent::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@profiles and ((parent::dash:AdaptationSet/@profiles and not(contains(parent::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (count(child::dash:BaseURL) &gt; 0)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (count(child::dash:BaseURL) &gt; 0)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an element that is not part of the HbbTV profile', i.e., found 'BaseURL' element</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (parent::dash:AdaptationSet/@subsegmentStartsWithSAP = '3') and (@mediaStreamStructureId = following-sibling::dash:Representation/@mediaStreamStructureId)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (parent::dash:AdaptationSet/@subsegmentStartsWithSAP = '3') and (@mediaStreamStructureId = following-sibling::dash:Representation/@mediaStreamStructureId)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 3 with same value of mediaStreamStructureId in more than one Representation</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - For HbbTV profile, the SegmentTemplate element shall be present on at least one of the three levels, the Period level containing the Representation, the Adaptation Set containing the Representation, or on Representation level itself as it is based on live profile</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M8"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M8"/>
   <xsl:template match="@*|node()" priority="-2" mode="M8">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M8"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:SubRepresentation" priority="1000" mode="M9">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SubRepresentation"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@level and not(@bandwidth)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@level and not(@bandwidth)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If the level attribute is defined for a SubRepresentation also the bandwidth attribute shall be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@profiles and ((parent::dash:Representation/@profiles and not(contains(parent::dash:Representation/@profiles, @profiles))) or (ancestor::dash:AdaptationSet/@profiles and not(contains(ancestor::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@profiles and ((parent::dash:Representation/@profiles and not(contains(parent::dash:Representation/@profiles, @profiles))) or (ancestor::dash:AdaptationSet/@profiles and not(contains(ancestor::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M9"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M9"/>
   <xsl:template match="@*|node()" priority="-2" mode="M9">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M9"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:SegmentTemplate" priority="1000" mode="M10">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SegmentTemplate"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@duration) and not(child::dash:SegmentTimeline) and not(@initialization) ) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@duration) and not(child::dash:SegmentTimeline) and not(@initialization) ) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If more than one Media Segment is present the duration attribute or SegmentTimeline element shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@duration and child::dash:SegmentTimeline) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@duration and child::dash:SegmentTimeline) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Either the duration attribute or SegmentTimeline element shall be present but not both.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@indexRange) and @indexRangeExact) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@indexRange) and @indexRangeExact) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If indexRange is not present indexRangeExact shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@initialization and (matches(@initialization, '\$Number(%.[^\$]*)?\$') or matches(@initialization, '\$Time(%.[^\$]*)?\$'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@initialization and (matches(@initialization, '\$Number(%.[^\$]*)?\$') or matches(@initialization, '\$Time(%.[^\$]*)?\$'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Neither $Number$ nor the $Time$ identifier shall be included in the initialization attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@bitstreamSwitching and (matches(@bitstreamSwitching, '\$Number(%.[^\$]*)?\$') or matches(@bitstreamSwitching, '\$Time(%.[^\$]*)?\$'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@bitstreamSwitching and (matches(@bitstreamSwitching, '\$Number(%.[^\$]*)?\$') or matches(@bitstreamSwitching, '\$Time(%.[^\$]*)?\$'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Neither $Number$ nor the $Time$ identifier shall be included in the bitstreamSwitching attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (matches(@media, '\$.[^\$]*\$')) then every $y in (for $x in tokenize(@media, '\$(Bandwidth|Time|Number|RepresentationID)(%.[^\$]*)?\$') return matches($x, '\$.[^\$]*\$')) satisfies $y eq false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (matches(@media, '\$.[^\$]*\$')) then every $y in (for $x in tokenize(@media, '\$(Bandwidth|Time|Number|RepresentationID)(%.[^\$]*)?\$') return matches($x, '\$.[^\$]*\$')) satisfies $y eq false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Only identifiers such as $Bandwidth$, $Time$, $RepresentationID$, or $Number$ shall be used.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (matches(@media, '\$RepresentationID%.[^\$]*\$')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (matches(@media, '\$RepresentationID%.[^\$]*\$')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>$RepresentationID$ shall not have a format tag.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M10"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M10"/>
   <xsl:template match="@*|node()" priority="-2" mode="M10">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M10"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:SegmentList" priority="1000" mode="M11">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SegmentList"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@duration) and not(child::dash:SegmentTimeline)) then if (count(child::dash:SegmentURL) &gt; 1) then false() else true() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@duration) and not(child::dash:SegmentTimeline)) then if (count(child::dash:SegmentURL) &gt; 1) then false() else true() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If more than one Media Segment is present the duration attribute or SegmentTimeline element shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@duration and child::dash:SegmentTimeline) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@duration and child::dash:SegmentTimeline) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>Either the duration attribute or SegmentTimeline element shall be present but not both.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@indexRange) and @indexRangeExact) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@indexRange) and @indexRangeExact) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If indexRange is not present indexRangeExact shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M11"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M11"/>
   <xsl:template match="@*|node()" priority="-2" mode="M11">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M11"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:SegmentBase" priority="1000" mode="M12">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SegmentBase"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@indexRange) and @indexRangeExact) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@indexRange) and @indexRangeExact) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If indexRange is not present indexRangeExact shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@timeShiftBufferDepth) then if (@timeShiftbuffer &lt; dash:MPD/@timeShiftBufferDepth) then false() else true() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@timeShiftBufferDepth) then if (@timeShiftbuffer &lt; dash:MPD/@timeShiftBufferDepth) then false() else true() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The timeShiftBufferDepth shall not be smaller than timeShiftBufferDepth specified in the MPD element</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M12"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M12"/>
   <xsl:template match="@*|node()" priority="-2" mode="M12">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M12"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:SegmentTimeline" priority="1000" mode="M13">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SegmentTimeline"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((if (ancestor::dash:*[1]/@timescale) then (child::dash:S/@d div ancestor::dash:*[1]/@timescale) else child::dash:S/@d) &gt; (years-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + months-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + days-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + hours-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + minutes-from-duration(ancestor::dash:MPD/@maxSegmentDuration) +  seconds-from-duration(ancestor::dash:MPD/@maxSegmentDuration))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((if (ancestor::dash:*[1]/@timescale) then (child::dash:S/@d div ancestor::dash:*[1]/@timescale) else child::dash:S/@d) &gt; (years-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + months-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + days-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + hours-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + minutes-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + seconds-from-duration(ancestor::dash:MPD/@maxSegmentDuration))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The d attribute of a SegmentTimeline shall not exceed the value give bei the MPD maxSegmentDuration attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M13"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M13"/>
   <xsl:template match="@*|node()" priority="-2" mode="M13">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M13"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:ProgramInformation" priority="1000" mode="M14">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:ProgramInformation"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (count(parent::dash:MPD/dash:ProgramInformation) &gt; 1 and not(@lang)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (count(parent::dash:MPD/dash:ProgramInformation) &gt; 1 and not(@lang)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If more than one ProgramInformation element is given each ProgramInformation element shall have a lang attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M14"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M14"/>
   <xsl:template match="@*|node()" priority="-2" mode="M14">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M14"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:ContentProtection" priority="1000" mode="M15">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:ContentProtection"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(string-length(@value) = 4)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(string-length(@value) = 4)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of ContentProtection shall be the 4CC contained in the Scheme Type Box</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:CA_descriptor:2011') and not(string-length(@value) = 4)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:CA_descriptor:2011') and not(string-length(@value) = 4)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of ContentProtection shall be the 4-digit lower-case hexadecimal Representation.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(parent::dash:AdaptationSet)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(parent::dash:AdaptationSet)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The ContentProtection descriptors shall always be present in the AdaptationSet element and apply to all contained Representations.
			</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and (@value= 'cenc') and not(parent::dash:AdaptationSet)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and (@value= 'cenc') and not(parent::dash:AdaptationSet)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The ContentProtection descriptor for the mp4 protection scheme with @schemeIdUri 'urn:mpeg:dash:mp4protection:2011' and @value 'cenc' shall be present in the AdaptationSet element.
			</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M15"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M15"/>
   <xsl:template match="@*|node()" priority="-2" mode="M15">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M15"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:Role" priority="1000" mode="M16">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:Role"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:role:2011') and not(@value = 'caption' or @value = 'subtitle' or @value = 'main' or @value = 'alternate' or @value = 'supplementary' or @value = 'commentary' or @value = 'dub')) then false else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:role:2011') and not(@value = 'caption' or @value = 'subtitle' or @value = 'main' or @value = 'alternate' or @value = 'supplementary' or @value = 'commentary' or @value = 'dub')) then false else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of Role (role) shall be caption, subtitle, main, alternate, supplementary, commentary or dub.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:stereoid:2011') and not(starts-with(@value, 'l') or starts-with(@value, 'r'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:stereoid:2011') and not(starts-with(@value, 'l') or starts-with(@value, 'r'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of Role (stereoid) shall start with 'l' or 'r'.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M16"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M16"/>
   <xsl:template match="@*|node()" priority="-2" mode="M16">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M16"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:FramePacking" priority="1000" mode="M17">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:FramePacking"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(contains(parent::dash:AdaptationSet/@codecs, 'avc') or contains(parent::dash:AdaptationSet/@codecs, 'svc') or contains(parent::dash:AdaptationSet/@codecs, 'mvc')) and not(contains(parent::dash:Representation/@codecs, 'avc') or contains(parent::dash:Representation/@codecs, 'svc') or contains(parent::dash:Representation/@codecs, 'mvc'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(contains(parent::dash:AdaptationSet/@codecs, 'avc') or contains(parent::dash:AdaptationSet/@codecs, 'svc') or contains(parent::dash:AdaptationSet/@codecs, 'mvc')) and not(contains(parent::dash:Representation/@codecs, 'avc') or contains(parent::dash:Representation/@codecs, 'svc') or contains(parent::dash:Representation/@codecs, 'mvc'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The URI urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011 is used for Adaptation Sets or Representations that contain a video component that conforms to ISO/IEC 14496-10.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011') and not(parent::dash:AdaptationSet/@mimeType = 'video/mp2t') and not(parent::dash:Representation/@mimeType = 'video/mp2t')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011') and not(parent::dash:AdaptationSet/@mimeType = 'video/mp2t') and not(parent::dash:Representation/@mimeType = 'video/mp2t')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The URI urn:mpeg:dash:13818:1:stereo_video_format_type:2011 is used for Adaptation Sets or Representations that contain a video component that conforms to ISO/IEC 13818-1.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>schemeIdUri for FramePacking descriptor shall be urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011 or urn:mpeg:dash:13818:1:stereo_video_format_type:2011.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@value = '0' or @value = '1' or @value = '2' or @value = '3' or @value = '4' or @value = '5' or @value = '6')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@value = '0' or @value = '1' or @value = '2' or @value = '3' or @value = '4' or @value = '5' or @value = '6')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of FramePacking shall be 0 to 6 as defined in ISO/IEC 23001-8.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M17"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M17"/>
   <xsl:template match="@*|node()" priority="-2" mode="M17">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M17"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:AudioChannelConfiguration" priority="1000" mode="M18">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AudioChannelConfiguration"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:outputChannelPositionList:2012') and not(count(tokenize(@value, ' ')) &gt; 1)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:outputChannelPositionList:2012') and not(count(tokenize(@value, ' ')) &gt; 1)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If URI urn:mpeg:dash:outputChannelPositionList:2012 is used the value attribute shall be a space-delimited list as defined in ISO/IEC 23001-8.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#ac-4') and not(@schemeIdUri='tag:dolby.com,2014:dash:audio_channel_configuration:2011')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#ac-4') and not(@schemeIdUri='tag:dolby.com,2014:dash:audio_channel_configuration:2011')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> If profile http://dashif.org/guidelines/dashif#ac-4 is used, then schemeIdUri attribute shall be tag:dolby.com,2014:dash:audio_channel_configuration:2011.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#mha1') and not(@schemeIdUri='urn:mpeg:mpegB:cicp:ChannelConfiguration')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#mha1') and not(@schemeIdUri='urn:mpeg:mpegB:cicp:ChannelConfiguration')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> If profile http://dashif.org/guidelines/dashif#mha1 is used, then schemeIdUri attribute shall be urn:mpeg:mpegB:cicp:ChannelConfiguration.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M18"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M18"/>
   <xsl:template match="@*|node()" priority="-2" mode="M18">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M18"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:EventStream" priority="1000" mode="M19">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:EventStream"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@actuate and not(@href)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@actuate and not(@href)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If href is not present actuate shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (not(@schemeIdUri)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@schemeIdUri)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>schemeIdUri shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M19"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M19"/>
   <xsl:template match="@*|node()" priority="-2" mode="M19">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M19"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:Subset" priority="1000" mode="M20">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:Subset"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if (@id = preceding::dash:Subset/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding::dash:Subset/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each Subset shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M20"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M20"/>
   <xsl:template match="@*|node()" priority="-2" mode="M20">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M20"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:UTCTiming" priority="1000" mode="M21">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:UTCTiming"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:utc:ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:sntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-head:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-xsdate:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-iso:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:direct:2014')) then true() else false()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:utc:ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:sntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-head:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-xsdate:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-iso:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:direct:2014')) then true() else false()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>@schemeIdUri for UTCTiming is not one of the 7 different types specified.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M21"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M21"/>
   <xsl:template match="@*|node()" priority="-2" mode="M21">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M21"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:SupplementalProperty" priority="1000" mode="M22">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SupplementalProperty"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'urn:mpeg:dash:chaining:2016') and not((count(tokenize(@value, ','))=1) or (count(tokenize(@value, ','))&gt;1)) )then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'urn:mpeg:dash:chaining:2016') and not((count(tokenize(@value, ','))=1) or (count(tokenize(@value, ','))&gt;1)) )then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If schemeIdUri urn:mpeg:dash:chaining:2016 is used, then value attribute shall be composed of the comma separated parameters (no comma needed if only first parameter is present). </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if(not(parent::dash:MPD) and (@schemeIdUri= 'urn:mpeg:dash:fallback:2016') )then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if(not(parent::dash:MPD) and (@schemeIdUri= 'urn:mpeg:dash:fallback:2016') )then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>MPD fallback chaining shall be signaled by Supplemental Descriptor on MPD level with schemeIdUri urn:mpeg:dash:fallback:2016. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'urn:mpeg:dash:fallback:2016') and not((count(tokenize(@value, ' '))=1) or (count(tokenize(@value, ' '))&gt;1)) )then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'urn:mpeg:dash:fallback:2016') and not((count(tokenize(@value, ' '))=1) or (count(tokenize(@value, ' '))&gt;1)) )then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text>If schemeIdUri urn:mpeg:dash:fallback:2016 is used, then value attribute shall be composed of one URL or whitespace separated URLs. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M22"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M22"/>
   <xsl:template match="@*|node()" priority="-2" mode="M22">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M22"/>
   </xsl:template>

   <!--PATTERN -->


	<!--RULE -->
<xsl:template match="dash:EssentialProperty" priority="1000" mode="M23">
      <svrl:fired-rule xmlns:xs="http://www.w3.org/2001/XMLSchema"
                       xmlns:schold="http://www.ascc.net/xml/schematron"
                       xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:EssentialProperty"/>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not((count(tokenize(@value, 'x'))=2)))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not((count(tokenize(@value, 'x'))=2)))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then value shall provide horizontal and vertical number of tiles separated by an 'x'. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@bandwidth))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@bandwidth))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then bandwidth shall be used to describe the tiling.'. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@height) and not(ancestor::dash:AdaptationSet/@height))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@height) and not(ancestor::dash:AdaptationSet/@height))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then height shall be used to describe the tiling. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>

		    <!--ASSERT -->
<xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@width) and not(ancestor::dash:AdaptationSet/@width))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:xs="http://www.w3.org/2001/XMLSchema"
                                xmlns:schold="http://www.ascc.net/xml/schematron"
                                xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@width) and not(ancestor::dash:AdaptationSet/@width))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-get-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then width shall be used to describe the tiling. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M23"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M23"/>
   <xsl:template match="@*|node()" priority="-2" mode="M23">
      <xsl:apply-templates select="*|comment()|processing-instruction()" mode="M23"/>
   </xsl:template>
</xsl:stylesheet>