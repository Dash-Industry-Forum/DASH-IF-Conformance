<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<xsl:stylesheet xmlns:xs="http://www.w3.org/2001/XMLSchema"
                xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                xmlns:saxon="http://saxon.sf.net/"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:schold="http://www.ascc.net/xml/schematron"
                xmlns:iso="http://purl.oclc.org/dsdl/schematron"
                xmlns:xhtml="http://www.w3.org/1999/xhtml"
                xmlns:dash="urn:mpeg:dash:schema:mpd:2011"
                xmlns:xlink="http://www.w3.org/1999/xlink"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xmlns:cenc="urn:mpeg:cenc:2013"
                xmlns:dlb="http://www.dolby.com/ns/2019/dash-if"
                version="2.0"><!--Implementers: please note that overriding process-prolog or process-root is 
    the preferred method for meta-stylesheets to use where possible. -->
   <xsl:param name="archiveDirParameter"/>
   <xsl:param name="archiveNameParameter"/>
   <xsl:param name="fileNameParameter"/>
   <xsl:param name="fileDirParameter"/>
   <xsl:variable name="document-uri">
      <xsl:value-of select="document-uri(/)"/>
   </xsl:variable>
   <!--PHASES-->
   <!--PROLOG-->
   <xsl:output xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
               method="xml"
               omit-xml-declaration="no"
               standalone="yes"
               indent="yes"/>
   <!--XSD TYPES FOR XSLT2-->
   <!--KEYS AND FUNCTIONS-->
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:isAuxiliaryStream"
                 as="xs:boolean">
      <xsl:param name="x"/>
      <xsl:sequence select="exists($x/dash:EssentialProperty[@schemeIdUri='urn:mpeg:dash:preselection:2016'])"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:getNearestCodecString"
                 as="xs:string">
      <xsl:param name="c"/>
      <xsl:value-of select="$c/ancestor-or-self::*/@codecs[1]"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:isAdaptationSetType"
                 as="xs:boolean">
      <xsl:param name="x" as="element()"/>
      <xsl:param name="t" as="xs:string"/>
      <xsl:sequence select="matches(dlb:getNearestCodecString($x),$t)"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:isAdaptationSetEC3"
                 as="xs:boolean">
      <xsl:param name="x"/>
      <xsl:sequence select="dlb:isAdaptationSetType($x,'ec-3')"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:isAdaptationSetAC4"
                 as="xs:boolean">
      <xsl:param name="x"/>
      <xsl:sequence select="dlb:isAdaptationSetType($x,'ac-4')"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:isAdaptationSetAudio"
                 as="xs:boolean">
      <xsl:param name="x"/>
      <xsl:sequence select="dlb:isAdaptationSetType($x,'mp4a|ac-3|ec-3|ac-4|dtsc|dtsh|dtse|dtsl')"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:isAdaptationSetVideo"
                 as="xs:boolean">
      <xsl:param name="x"/>
      <xsl:sequence select="dlb:isAdaptationSetType($x,'avc|hvc|hev')"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:fractionalToFloat"
                 as="xs:float">
      <xsl:param name="x"/>
      <xsl:variable name="numden" select="tokenize($x,'/')"/>
      <xsl:value-of select="if (count($numden) eq 1) then $x else xs:float($numden[1]) div xs:float($numden[2])"/>
   </xsl:function>
   <xsl:function xmlns="http://purl.oclc.org/dsdl/schematron"
                 name="dlb:dlb2mpg"
                 as="xs:integer">
      <xsl:param name="from"/>
      <xsl:choose>
         <xsl:when test="$from='000002'">1</xsl:when>
         <xsl:when test="$from='000001'">2</xsl:when>
         <xsl:when test="$from='000003'">3</xsl:when>
         <xsl:when test="$from='008003'">4</xsl:when>
         <xsl:when test="$from='000007'">5</xsl:when>
         <xsl:when test="$from='000047'">6</xsl:when>
         <xsl:when test="$from='020047'">7</xsl:when>
         <xsl:when test="$from='008001'">9</xsl:when>
         <xsl:when test="$from='000005'">10</xsl:when>
         <xsl:when test="$from='008047'">11</xsl:when>
         <xsl:when test="$from='00004F'">12</xsl:when>
         <xsl:when test="$from='02FF7F'">13</xsl:when>
         <xsl:when test="$from='06FF6F'">13</xsl:when>
         <xsl:when test="$from='000057'">14</xsl:when>
         <xsl:when test="$from='040047'">14</xsl:when>
         <xsl:when test="$from='00145F'">15</xsl:when>
         <xsl:when test="$from='04144F'">15</xsl:when>
         <xsl:when test="$from='000077'">16</xsl:when>
         <xsl:when test="$from='040067'">16</xsl:when>
         <xsl:when test="$from='000A77'">17</xsl:when>
         <xsl:when test="$from='040A67'">17</xsl:when>
         <xsl:when test="$from='000A7F'">18</xsl:when>
         <xsl:when test="$from='040A6F'">18</xsl:when>
         <xsl:when test="$from='00007F'">19</xsl:when>
         <xsl:when test="$from='04006F'">19</xsl:when>
         <xsl:when test="$from='01007F'">20</xsl:when>
         <xsl:when test="$from='05006F'">20</xsl:when>
         <xsl:otherwise>0</xsl:otherwise>
      </xsl:choose>
   </xsl:function>
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
         </xsl:when>
         <xsl:otherwise>
            <xsl:text>*:</xsl:text>
            <xsl:value-of select="local-name()"/>
            <xsl:text>[namespace-uri()='</xsl:text>
            <xsl:value-of select="namespace-uri()"/>
            <xsl:text>']</xsl:text>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:variable name="preceding"
                    select="count(preceding-sibling::*[local-name()=local-name(current())                                   and namespace-uri() = namespace-uri(current())])"/>
      <xsl:text>[</xsl:text>
      <xsl:value-of select="1+ $preceding"/>
      <xsl:text>]</xsl:text>
   </xsl:template>
   <xsl:template match="@*" mode="schematron-get-full-path">
      <xsl:apply-templates select="parent::*" mode="schematron-get-full-path"/>
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
   <!--Strip characters-->
   <xsl:template match="text()" priority="-1"/>
   <!--SCHEMA SETUP-->
   <xsl:template match="/">
      <svrl:schematron-output xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
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
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/2001/XMLSchema" prefix="xs"/>
         <svrl:ns-prefix-in-attribute-values uri="urn:mpeg:cenc:2013" prefix="cenc"/>
         <svrl:ns-prefix-in-attribute-values uri="urn:mpeg:dash:schema:mpd:2011" prefix="dash"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.dolby.com/ns/2019/dash-if" prefix="dlb"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">MPD element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M17"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Period element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M18"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AdaptationSet element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M19"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">ContentComponent element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M20"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Representation element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M21"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SubRepresentation element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M22"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SegmentTemplate element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M23"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SegmentList element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M24"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SegmentBase element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M25"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SegmentTimeline element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M26"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">ProgramInformation element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M27"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">ContentProtection element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M28"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Role element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M29"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">FramePacking element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M30"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AudioChannelConfiguration element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M31"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">EventStream element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M32"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Subset element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M33"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">UTCTiming element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M34"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SupplementalProperty element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M35"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SRD description element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M36"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">MPD element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M37"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Period element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M38"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AdaptationSet element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M39"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Representation element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M40"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SubRepresentation element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M41"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">SegmentBase element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M42"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">ContentProtection element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M43"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AudioChannelConfiguration element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M44"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">EssentialProperty element</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M45"/>
         <svrl:ns-prefix-in-attribute-values uri="urn:mpeg:dash:schema:mpd:2011" prefix="dash"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/1999/xlink" prefix="xlink"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/2001/XMLSchema-instance" prefix="xsi"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.dolby.com/ns/2019/dash-if" prefix="dlb"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AdaptationSet element for DVB DASH 2017 profile</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M51"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Preselection element for DVB DASH 2017 profile</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M52"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AdaptationSet and Preselection element for AC-4 for DVB DASH 2017 profile</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M54"/>
         <svrl:ns-prefix-in-attribute-values uri="urn:mpeg:dash:schema:mpd:2011" prefix="dash"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/1999/xlink" prefix="xlink"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.w3.org/2001/XMLSchema-instance" prefix="xsi"/>
         <svrl:ns-prefix-in-attribute-values uri="http://www.dolby.com/ns/2019/dash-if" prefix="dlb"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AdaptationSet and Preselection element for AC-4</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M64"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Representation for AC-4</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M65"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">Role element for AC-4</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M66"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AC-4 supplemental property descriptors</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M67"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AudioChannelConfiguration element for AC-4 part 1</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M68"/>
         <svrl:active-pattern>
            <xsl:attribute name="document">
               <xsl:value-of select="document-uri(/)"/>
            </xsl:attribute>
            <xsl:attribute name="name">AudioChannelConfiguration element for AC-4 part 2</xsl:attribute>
            <xsl:apply-templates/>
         </svrl:active-pattern>
         <xsl:apply-templates select="/" mode="M69"/>
      </svrl:schematron-output>
   </xsl:template>
   <!--SCHEMATRON PATTERNS-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Schema for validating MPDs</svrl:text>
   <!--PATTERN MPD element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">MPD element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:MPD" priority="1000" mode="M17">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:MPD"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@type = 'dynamic' and not(@availabilityStartTime)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'dynamic' and not(@availabilityStartTime)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "dynamic" availabilityStartTime shall be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@type = 'dynamic' and not(@publishTime)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'dynamic' and not(@publishTime)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "dynamic" publishTime shall be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@type = 'static' and descendant::dash:Period[1]/@start and (years-from-duration(descendant::dash:Period[1]/@start) + months-from-duration(descendant::dash:Period[1]/@start) + days-from-duration(descendant::dash:Period[1]/@start) + hours-from-duration(descendant::dash:Period[1]/@start) + minutes-from-duration(descendant::dash:Period[1]/@start) +  seconds-from-duration(descendant::dash:Period[1]/@start)) &gt; 0) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'static' and descendant::dash:Period[1]/@start and (years-from-duration(descendant::dash:Period[1]/@start) + months-from-duration(descendant::dash:Period[1]/@start) + days-from-duration(descendant::dash:Period[1]/@start) + hours-from-duration(descendant::dash:Period[1]/@start) + minutes-from-duration(descendant::dash:Period[1]/@start) + seconds-from-duration(descendant::dash:Period[1]/@start)) &gt; 0) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "static" and the first period has a start attribute the start attribute shall be zero.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If mediaPresentationDuration is not defined for the MPD minimumUpdatePeriod shall be defined or vice versa.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@profiles) or (contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:full:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-simple:2011') or contains (@profiles, 'http://dashif.org/guidelines/dashif#ac-4') or contains (@profiles, 'http://dashif.org/guidelines/dashif#mpeg-h-3da') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9-hdr') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9-hdr') or contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(@profiles, 'urn:dvb:dash:profile:dvb-dash:2014') or contains(@profiles, 'http://dashif.org/guidelines/dashif#ec-3'))) then true() else false()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@profiles) or (contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(@profiles, 'urn:mpeg:dash:profile:isoff-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:full:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-main:2011') or contains(@profiles, 'urn:mpeg:dash:profile:mp2t-simple:2011') or contains (@profiles, 'http://dashif.org/guidelines/dashif#ac-4') or contains (@profiles, 'http://dashif.org/guidelines/dashif#mpeg-h-3da') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9') or contains(@profiles, 'http://dashif.org/guidelines/dashif#vp9-hdr') or contains(@profiles, 'http://dashif.org/guidelines/dash-if-uhd#vp9-hdr') or contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(@profiles, 'urn:dvb:dash:profile:dvb-dash:2014') or contains(@profiles, 'http://dashif.org/guidelines/dashif#ec-3'))) then true() else false()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>An unknown profile string (other than the On-Demand profile -"urn:mpeg:dash:profile:isoff-on-demand:2011", the live profile -"urn:mpeg:dash:profile:isoff-live:2011", the main profile- "urn:mpeg:dash:profile:isoff-main:2011", the full profile "urn:mpeg:dash:profile:full:2011", the mp2t-main profile -"urn:mpeg:dash:profile:mp2t-main:2011", the mp2t-simple profile -"urn:mpeg:dash:profile:mp2t-simple:2011", the Dolby AC-4 profile -"http://dashif.org/guidelines/dashif#ac-4", -the multichannel audio extension with MPEG-H 3D Audio profile -"http://dashif.org/guidelines/dashif#mpeg-h-3da", the VP9-HD profile -"http://dashif.org/guidelines/dashif#vp9", the VP9-UHD profile -"http://dashif.org/guidelines/dash-if-uhd#vp9", the VP9-HDR profile -"http://dashif.org/guidelines/dashif#vp9-hdr" or "http://dashif.org/guidelines/dash-if-uhd#vp9-hdr", the DVB-DASH profile -"urn:dvb:dash:profile:dvb-dash:2014", the HbbTV 1.5 profile -"urn:hbbtv:dash:profile:isoff-live:2012", the DASH-IF multchannel audio extension with Enhanced AC-3 -"http://dashif.org/guidelines/dashif#ec-3")found.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or not(@type) or @type='static') then true() else false()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or not(@type) or @type='static') then true() else false()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For On-Demand profile, the MPD @type shall be "static".</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod) and not(dash:Period[last()]/@duration)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@mediaPresentationDuration) and not(@minimumUpdatePeriod) and not(dash:Period[last()]/@duration)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If minimumUpdatePeriod is not present and the last period does not include the duration attribute the mediaPresentationDuration must be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M17"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M17"/>
   <xsl:template match="@*|node()" priority="-2" mode="M17">
      <xsl:apply-templates select="*" mode="M17"/>
   </xsl:template>
   <!--PATTERN Period element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Period element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Period" priority="1000" mode="M18">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:Period"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (string(@bitstreamSwitching) = 'true' and string(child::dash:AdaptationSet/@bitstreamSwitching) = 'false') then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (string(@bitstreamSwitching) = 'true' and string(child::dash:AdaptationSet/@bitstreamSwitching) = 'false') then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If bitstreamSwitching is set to true all bitstreamSwitching declarations for AdaptationSet within this Period shall not be set to false.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@id = preceding::dash:Period/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding::dash:Period/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each Period shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in Period.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@id) and ancestor::dash:MPD/@type = 'dynamic') then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@id) and ancestor::dash:MPD/@type = 'dynamic') then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If the MPD is dynamic the Period element shall have an id.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(descendant-or-self::dash:BaseURL) and not(descendant-or-self::dash:SegmentTemplate) and not(descendant-or-self::dash:SegmentList) and not(@xlink:href = 'urn:mpeg:dash:resolve-to-zero:2013')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(descendant-or-self::dash:BaseURL) and not(descendant-or-self::dash:SegmentTemplate) and not(descendant-or-self::dash:SegmentList) and not(@xlink:href = 'urn:mpeg:dash:resolve-to-zero:2013')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>At least one BaseURL, SegmentTemplate or SegmentList shall be defined in Period, AdaptationSet or Representation.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@duration = xs:duration(PT0S) and count(child::dash:AdaptationSet)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@duration = xs:duration(PT0S) and count(child::dash:AdaptationSet)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If the duration attribute is set to zero, there should only be a single AdaptationSet present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') and (child::dash:SegmentList or child::dash:SegmentTemplate)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011') and (child::dash:SegmentList or child::dash:SegmentTemplate)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Neither the Period.SegmentList element nor the Period.SegmentTemplate element shall be present for On-Demand profile, violated here. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M18"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M18"/>
   <xsl:template match="@*|node()" priority="-2" mode="M18">
      <xsl:apply-templates select="*" mode="M18"/>
   </xsl:template>
   <!--PATTERN AdaptationSet element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AdaptationSet element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:AdaptationSet" priority="1000" mode="M19">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:AdaptationSet"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@id = preceding-sibling::dash:AdaptationSet/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding-sibling::dash:AdaptationSet/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each AdaptationSet within a Period shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@lang = descendant::dash:ContentComponent/@lang) or (@contentType = descendant::dash:ContentComponent/@contentType) or (@par = descendant::dash:ContentComponent/@par)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@lang = descendant::dash:ContentComponent/@lang) or (@contentType = descendant::dash:ContentComponent/@contentType) or (@par = descendant::dash:ContentComponent/@par)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Attributes from the AdaptationSet shall not be repeated in the descendanding ContentComponent elements.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@profiles and descendant::dash:Representation/@profiles) or (@width and descendant::dash:Representation/@width) or (@height and descendant::dash:Representation/@height) or (@sar and descendant::dash:Representation/@sar) or (@frameRate and descendant::dash:Representation/@frameRate) or (@audioSamplingRate and descendant::dash:Representation/@audioSamplingRate) or (@mimeType and descendant::dash:Representation/@mimeType) or (@segmentProfiles and descendant::dash:Representation/@segmentProfiles) or (@codecs and descendant::dash:Representation/@codecs) or (@maximumSAPPeriod and descendant::dash:Representation/@maximumSAPPeriod) or (@startWithSAP and descendant::dash:Representation/@startWithSAP) or (@maxPlayoutRate and descendant::dash:Representation/@maxPlayoutRate) or (@codingDependency and descendant::dash:Representation/@codingDependency) or (@scanType and descendant::dash:Representation/@scanType)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@profiles and descendant::dash:Representation/@profiles) or (@width and descendant::dash:Representation/@width) or (@height and descendant::dash:Representation/@height) or (@sar and descendant::dash:Representation/@sar) or (@frameRate and descendant::dash:Representation/@frameRate) or (@audioSamplingRate and descendant::dash:Representation/@audioSamplingRate) or (@mimeType and descendant::dash:Representation/@mimeType) or (@segmentProfiles and descendant::dash:Representation/@segmentProfiles) or (@codecs and descendant::dash:Representation/@codecs) or (@maximumSAPPeriod and descendant::dash:Representation/@maximumSAPPeriod) or (@startWithSAP and descendant::dash:Representation/@startWithSAP) or (@maxPlayoutRate and descendant::dash:Representation/@maxPlayoutRate) or (@codingDependency and descendant::dash:Representation/@codingDependency) or (@scanType and descendant::dash:Representation/@scanType)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Common attributes for AdaptationSet and Representation shall either be in one of the elements but not in both.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((xs:int(@minWidth) &gt; xs:int(@maxWidth)) or (xs:int(@minHeight) &gt; xs:int(@maxHeight)) or (xs:int(@minBandwidth) &gt; xs:int(@maxBandwidth))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((xs:int(@minWidth) &gt; xs:int(@maxWidth)) or (xs:int(@minHeight) &gt; xs:int(@maxHeight)) or (xs:int(@minBandwidth) &gt; xs:int(@maxBandwidth))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Each minimum value (minWidth, minHeight, minBandwidth) shall be larger than the maximum value.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (descendant::dash:Representation/@bandwidth &lt; xs:int(@minBandwidth) or descendant::dash:Representation/@bandwidth &gt; xs:int(@maxBandwidth)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (descendant::dash:Representation/@bandwidth &lt; xs:int(@minBandwidth) or descendant::dash:Representation/@bandwidth &gt; xs:int(@maxBandwidth)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of the bandwidth attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (descendant::dash:Representation/@width &gt; xs:int(@maxWidth)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (descendant::dash:Representation/@width &gt; xs:int(@maxWidth)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of the width attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (descendant::dash:Representation/@height &gt; xs:int(@maxHeight)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (descendant::dash:Representation/@height &gt; xs:int(@maxHeight)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of the height attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (count(child::dash:Representation)=0) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (count(child::dash:Representation)=0) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>An AdaptationSet shall have at least one Representation element.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@minFrameRate and (some $fr in descendant::dash:Representation/@frameRate satisfies dlb:fractionalToFloat($fr) lt dlb:fractionalToFloat(@minFrameRate))) or (@maxFrameRate and (some $fr in descendant::dash:Representation/@frameRate satisfies dlb:fractionalToFloat($fr) gt dlb:fractionalToFloat(@maxFrameRate)))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@minFrameRate and (some $fr in descendant::dash:Representation/@frameRate satisfies dlb:fractionalToFloat($fr) lt dlb:fractionalToFloat(@minFrameRate))) or (@maxFrameRate and (some $fr in descendant::dash:Representation/@frameRate satisfies dlb:fractionalToFloat($fr) gt dlb:fractionalToFloat(@maxFrameRate)))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>ISO/IEC 23009-1 Section 5.3.3.2: The value of the frameRate attribute shall be in the range defined by the AdaptationSet.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentAlignment = 'true')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentAlignment = 'true')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentAlignment' as true</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '1' or @subsegmentStartsWithSAP = '2')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '1' or @subsegmentStartsWithSAP = '2')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 1 or 2</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '3') and not (count(child::dash:Representation) &gt; 1)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '3') and not (count(child::dash:Representation) &gt; 1)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 3 while not containing more than one Representation</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M19"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M19"/>
   <xsl:template match="@*|node()" priority="-2" mode="M19">
      <xsl:apply-templates select="*" mode="M19"/>
   </xsl:template>
   <!--PATTERN ContentComponent element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">ContentComponent element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:ContentComponent" priority="1000" mode="M20">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:ContentComponent"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@id = preceding-sibling::dash:ContentComponent/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding-sibling::dash:ContentComponent/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each ContentComponent within an AdaptationSet shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M20"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M20"/>
   <xsl:template match="@*|node()" priority="-2" mode="M20">
      <xsl:apply-templates select="*" mode="M20"/>
   </xsl:template>
   <!--PATTERN Representation element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Representation element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Representation" priority="1000" mode="M21">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:Representation"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@mimeType) and not(parent::dash:AdaptationSet/@mimeType)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@mimeType) and not(parent::dash:AdaptationSet/@mimeType)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Either the Representation or the containing AdaptationSet shall have the mimeType attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') or contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For live profile, the SegmentTemplate element shall be present on at least one of the three levels, the Period level containing the Representation, the Adaptation Set containing the Representation, or on Representation level itself.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((child::dash:SegmentBase and child::dash:SegmentTemplate and child::dash:SegmentList) or (child::dash:SegmentBase and child::dash:SegmentTemplate) or (child::dash:SegmentBase and child::dash:SegmentList) or (child::dash:SegmentTemplate and child::dash:SegmentList)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>At most one of SegmentBase, SegmentTemplate and SegmentList shall be defined in Representation.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@id = preceding-sibling::dash:Representation/@id) or (@id=parent::dash:AdaptationSet/preceding-sibling::dash:AdaptationSet/dash:Representation/@id))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@id = preceding-sibling::dash:Representation/@id) or (@id=parent::dash:AdaptationSet/preceding-sibling::dash:AdaptationSet/dash:Representation/@id))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each Representation within a Period shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (count(child::dash:BaseURL) &gt; 0)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (count(child::dash:BaseURL) &gt; 0)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an element that is not part of the HbbTV profile', i.e., found 'BaseURL' element</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (parent::dash:AdaptationSet/@subsegmentStartsWithSAP = '3') and (@mediaStreamStructureId = following-sibling::dash:Representation/@mediaStreamStructureId)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (parent::dash:AdaptationSet/@subsegmentStartsWithSAP = '3') and (@mediaStreamStructureId = following-sibling::dash:Representation/@mediaStreamStructureId)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 3 with same value of mediaStreamStructureId in more than one Representation</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(child::dash:SegmentTemplate or parent::dash:AdaptationSet/dash:SegmentTemplate or ancestor::dash:Period/dash:SegmentTemplate) and (contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(parent::dash:AdaptationSet/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - For HbbTV profile, the SegmentTemplate element shall be present on at least one of the three levels, the Period level containing the Representation, the Adaptation Set containing the Representation, or on Representation level itself as it is based on live profile</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M21"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M21"/>
   <xsl:template match="@*|node()" priority="-2" mode="M21">
      <xsl:apply-templates select="*" mode="M21"/>
   </xsl:template>
   <!--PATTERN SubRepresentation element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SubRepresentation element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SubRepresentation" priority="1000" mode="M22">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SubRepresentation"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@level and not(@bandwidth)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@level and not(@bandwidth)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If the level attribute is defined for a SubRepresentation also the bandwidth attribute shall be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M22"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M22"/>
   <xsl:template match="@*|node()" priority="-2" mode="M22">
      <xsl:apply-templates select="*" mode="M22"/>
   </xsl:template>
   <!--PATTERN SegmentTemplate element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SegmentTemplate element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SegmentTemplate" priority="1000" mode="M23">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:SegmentTemplate"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@duration) and not(child::dash:SegmentTimeline) and not(@initialization) ) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@duration) and not(child::dash:SegmentTimeline) and not(@initialization) ) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If more than one Media Segment is present the duration attribute or SegmentTimeline element shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@duration and child::dash:SegmentTimeline) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@duration and child::dash:SegmentTimeline) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Either the duration attribute or SegmentTimeline element shall be present but not both.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@indexRange) and @indexRangeExact) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@indexRange) and @indexRangeExact) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If indexRange is not present indexRangeExact shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@initialization and (matches(@initialization, '\$Number(%.[^\$]*)?\$') or matches(@initialization, '\$Time(%.[^\$]*)?\$'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@initialization and (matches(@initialization, '\$Number(%.[^\$]*)?\$') or matches(@initialization, '\$Time(%.[^\$]*)?\$'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Neither $Number$ nor the $Time$ identifier shall be included in the initialization attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@bitstreamSwitching and (matches(@bitstreamSwitching, '\$Number(%.[^\$]*)?\$') or matches(@bitstreamSwitching, '\$Time(%.[^\$]*)?\$'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@bitstreamSwitching and (matches(@bitstreamSwitching, '\$Number(%.[^\$]*)?\$') or matches(@bitstreamSwitching, '\$Time(%.[^\$]*)?\$'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Neither $Number$ nor the $Time$ identifier shall be included in the bitstreamSwitching attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (matches(@media, '\$.[^\$]*\$')) then every $y in (for $x in tokenize(@media, '\$(Bandwidth|Time|Number|RepresentationID)(%.[^\$]*)?\$') return matches($x, '\$.[^\$]*\$')) satisfies $y eq false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (matches(@media, '\$.[^\$]*\$')) then every $y in (for $x in tokenize(@media, '\$(Bandwidth|Time|Number|RepresentationID)(%.[^\$]*)?\$') return matches($x, '\$.[^\$]*\$')) satisfies $y eq false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Only identifiers such as $Bandwidth$, $Time$, $RepresentationID$, or $Number$ shall be used.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (matches(@media, '\$RepresentationID%.[^\$]*\$')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (matches(@media, '\$RepresentationID%.[^\$]*\$')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>$RepresentationID$ shall not have a format tag.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M23"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M23"/>
   <xsl:template match="@*|node()" priority="-2" mode="M23">
      <xsl:apply-templates select="*" mode="M23"/>
   </xsl:template>
   <!--PATTERN SegmentList element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SegmentList element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SegmentList" priority="1000" mode="M24">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:SegmentList"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@duration) and not(child::dash:SegmentTimeline)) then if (count(child::dash:SegmentURL) &gt; 1) then false() else true() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@duration) and not(child::dash:SegmentTimeline)) then if (count(child::dash:SegmentURL) &gt; 1) then false() else true() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If more than one Media Segment is present the duration attribute or SegmentTimeline element shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@duration and child::dash:SegmentTimeline) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@duration and child::dash:SegmentTimeline) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Either the duration attribute or SegmentTimeline element shall be present but not both.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@indexRange) and @indexRangeExact) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@indexRange) and @indexRangeExact) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If indexRange is not present indexRangeExact shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M24"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M24"/>
   <xsl:template match="@*|node()" priority="-2" mode="M24">
      <xsl:apply-templates select="*" mode="M24"/>
   </xsl:template>
   <!--PATTERN SegmentBase element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SegmentBase element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SegmentBase" priority="1000" mode="M25">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:SegmentBase"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@indexRange) and @indexRangeExact) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@indexRange) and @indexRangeExact) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If indexRange is not present indexRangeExact shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@timeShiftBufferDepth) then if (xs:int(@timeShiftbuffer) &lt; xs:int(dash:MPD/@timeShiftBufferDepth)) then false() else true() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@timeShiftBufferDepth) then if (xs:int(@timeShiftbuffer) &lt; xs:int(dash:MPD/@timeShiftBufferDepth)) then false() else true() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The timeShiftBufferDepth shall not be smaller than timeShiftBufferDepth specified in the MPD element</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M25"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M25"/>
   <xsl:template match="@*|node()" priority="-2" mode="M25">
      <xsl:apply-templates select="*" mode="M25"/>
   </xsl:template>
   <!--PATTERN SegmentTimeline element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SegmentTimeline element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SegmentTimeline" priority="1000" mode="M26">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:SegmentTimeline"/>
      <xsl:variable name="timescale"
                    select="if (ancestor::dash:*[1]/@timescale) then ancestor::dash:*[1]/@timescale else 1"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (some $d in child::dash:S/@d satisfies $d div $timescale &gt; (years-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + months-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + days-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + hours-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + minutes-from-duration(ancestor::dash:MPD/@maxSegmentDuration) +  seconds-from-duration(ancestor::dash:MPD/@maxSegmentDuration))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (some $d in child::dash:S/@d satisfies $d div $timescale &gt; (years-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + months-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + days-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + hours-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + minutes-from-duration(ancestor::dash:MPD/@maxSegmentDuration) + seconds-from-duration(ancestor::dash:MPD/@maxSegmentDuration))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The d attribute of a SegmentTimeline shall not exceed the value give by the MPD maxSegmentDuration attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M26"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M26"/>
   <xsl:template match="@*|node()" priority="-2" mode="M26">
      <xsl:apply-templates select="*" mode="M26"/>
   </xsl:template>
   <!--PATTERN ProgramInformation element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">ProgramInformation element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:ProgramInformation" priority="1000" mode="M27">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:ProgramInformation"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (count(parent::dash:MPD/dash:ProgramInformation) &gt; 1 and not(@lang)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (count(parent::dash:MPD/dash:ProgramInformation) &gt; 1 and not(@lang)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If more than one ProgramInformation element is given each ProgramInformation element shall have a lang attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M27"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M27"/>
   <xsl:template match="@*|node()" priority="-2" mode="M27">
      <xsl:apply-templates select="*" mode="M27"/>
   </xsl:template>
   <!--PATTERN ContentProtection element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">ContentProtection element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:ContentProtection" priority="1000" mode="M28">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:ContentProtection"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(string-length(@value) = 4)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(string-length(@value) = 4)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of ContentProtection shall be the 4CC contained in the Scheme Type Box</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:CA_descriptor:2011') and not(string-length(@value) = 4)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:CA_descriptor:2011') and not(string-length(@value) = 4)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of ContentProtection shall be the 4-digit lower-case hexadecimal Representation.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M28"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M28"/>
   <xsl:template match="@*|node()" priority="-2" mode="M28">
      <xsl:apply-templates select="*" mode="M28"/>
   </xsl:template>
   <!--PATTERN Role element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Role element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Role" priority="1000" mode="M29">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:Role"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:role:2011') and not(@value = 'caption' or @value = 'subtitle' or @value = 'main' or @value = 'alternate' or @value = 'supplementary' or @value = 'commentary' or @value = 'dub')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:role:2011') and not(@value = 'caption' or @value = 'subtitle' or @value = 'main' or @value = 'alternate' or @value = 'supplementary' or @value = 'commentary' or @value = 'dub')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of Role (role) shall be caption, subtitle, main, alternate, supplementary, commentary or dub.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:stereoid:2011') and not(starts-with(@value, 'l') or starts-with(@value, 'r'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:stereoid:2011') and not(starts-with(@value, 'l') or starts-with(@value, 'r'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of Role (stereoid) shall start with 'l' or 'r'.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M29"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M29"/>
   <xsl:template match="@*|node()" priority="-2" mode="M29">
      <xsl:apply-templates select="*" mode="M29"/>
   </xsl:template>
   <!--PATTERN FramePacking element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">FramePacking element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:FramePacking" priority="1000" mode="M30">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:FramePacking"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(contains(parent::dash:AdaptationSet/@codecs, 'avc') or contains(parent::dash:AdaptationSet/@codecs, 'svc') or contains(parent::dash:AdaptationSet/@codecs, 'mvc')) and not(contains(parent::dash:Representation/@codecs, 'avc') or contains(parent::dash:Representation/@codecs, 'svc') or contains(parent::dash:Representation/@codecs, 'mvc'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(contains(parent::dash:AdaptationSet/@codecs, 'avc') or contains(parent::dash:AdaptationSet/@codecs, 'svc') or contains(parent::dash:AdaptationSet/@codecs, 'mvc')) and not(contains(parent::dash:Representation/@codecs, 'avc') or contains(parent::dash:Representation/@codecs, 'svc') or contains(parent::dash:Representation/@codecs, 'mvc'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The URI urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011 is used for Adaptation Sets or Representations that contain a video component that conforms to ISO/IEC 14496-10.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011') and not(parent::dash:AdaptationSet/@mimeType = 'video/mp2t') and not(parent::dash:Representation/@mimeType = 'video/mp2t')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011') and not(parent::dash:AdaptationSet/@mimeType = 'video/mp2t') and not(parent::dash:Representation/@mimeType = 'video/mp2t')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The URI urn:mpeg:dash:13818:1:stereo_video_format_type:2011 is used for Adaptation Sets or Representations that contain a video component that conforms to ISO/IEC 13818-1.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@schemeIdUri = 'urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011') and not(@schemeIdUri = 'urn:mpeg:dash:13818:1:stereo_video_format_type:2011')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>schemeIdUri for FramePacking descriptor shall be urn:mpeg:dash:14496:10:frame_packing_arrangement_type:2011 or urn:mpeg:dash:13818:1:stereo_video_format_type:2011.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@value = '0' or @value = '1' or @value = '2' or @value = '3' or @value = '4' or @value = '5' or @value = '6')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@value = '0' or @value = '1' or @value = '2' or @value = '3' or @value = '4' or @value = '5' or @value = '6')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of FramePacking shall be 0 to 6 as defined in ISO/IEC 23001-8.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M30"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M30"/>
   <xsl:template match="@*|node()" priority="-2" mode="M30">
      <xsl:apply-templates select="*" mode="M30"/>
   </xsl:template>
   <!--PATTERN AudioChannelConfiguration element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AudioChannelConfiguration element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:AudioChannelConfiguration" priority="1000" mode="M31">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AudioChannelConfiguration"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:outputChannelPositionList:2012') and not(count(tokenize(@value, ' ')) &gt; 1)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:outputChannelPositionList:2012') and not(count(tokenize(@value, ' ')) &gt; 1)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If URI urn:mpeg:dash:outputChannelPositionList:2012 is used the value attribute shall be a space-delimited list as defined in ISO/IEC 23001-8.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M31"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M31"/>
   <xsl:template match="@*|node()" priority="-2" mode="M31">
      <xsl:apply-templates select="*" mode="M31"/>
   </xsl:template>
   <!--PATTERN EventStream element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">EventStream element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:EventStream" priority="1000" mode="M32">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:EventStream"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@actuate and not(@href)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@actuate and not(@href)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If href is not present actuate shall not be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(@schemeIdUri)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(@schemeIdUri)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>schemeIdUri shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M32"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M32"/>
   <xsl:template match="@*|node()" priority="-2" mode="M32">
      <xsl:apply-templates select="*" mode="M32"/>
   </xsl:template>
   <!--PATTERN Subset element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Subset element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Subset" priority="1000" mode="M33">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:Subset"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@id = preceding::dash:Subset/@id) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@id = preceding::dash:Subset/@id) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The id of each Subset shall be unique.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M33"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M33"/>
   <xsl:template match="@*|node()" priority="-2" mode="M33">
      <xsl:apply-templates select="*" mode="M33"/>
   </xsl:template>
   <!--PATTERN UTCTiming element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">UTCTiming element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:UTCTiming" priority="1000" mode="M34">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:UTCTiming"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:utc:ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:sntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-head:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-xsdate:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-iso:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:direct:2014')) then true() else false()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:utc:ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:sntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-head:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-xsdate:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-iso:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:http-ntp:2014') or (@schemeIdUri = 'urn:mpeg:dash:utc:direct:2014')) then true() else false()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>@schemeIdUri for UTCTiming is not one of the 7 different types specified.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M34"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M34"/>
   <xsl:template match="@*|node()" priority="-2" mode="M34">
      <xsl:apply-templates select="*" mode="M34"/>
   </xsl:template>
   <!--PATTERN SupplementalProperty element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SupplementalProperty element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SupplementalProperty" priority="1000" mode="M35">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SupplementalProperty"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'urn:mpeg:dash:chaining:2016') and not((count(tokenize(@value, ','))=1) or (count(tokenize(@value, ','))&gt;1)) )then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'urn:mpeg:dash:chaining:2016') and not((count(tokenize(@value, ','))=1) or (count(tokenize(@value, ','))&gt;1)) )then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If schemeIdUri urn:mpeg:dash:chaining:2016 is used, then value attribute shall be composed of the comma separated parameters (no comma needed if only first parameter is present). </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if(not(parent::dash:MPD) and (@schemeIdUri= 'urn:mpeg:dash:fallback:2016') )then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if(not(parent::dash:MPD) and (@schemeIdUri= 'urn:mpeg:dash:fallback:2016') )then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>MPD fallback chaining shall be signaled by Supplemental Descriptor on MPD level with schemeIdUri urn:mpeg:dash:fallback:2016. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'urn:mpeg:dash:fallback:2016') and not((count(tokenize(@value, ' '))=1) or (count(tokenize(@value, ' '))&gt;1)) )then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'urn:mpeg:dash:fallback:2016') and not((count(tokenize(@value, ' '))=1) or (count(tokenize(@value, ' '))&gt;1)) )then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If schemeIdUri urn:mpeg:dash:fallback:2016 is used, then value attribute shall be composed of one URL or whitespace separated URLs. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M35"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M35"/>
   <xsl:template match="@*|node()" priority="-2" mode="M35">
      <xsl:apply-templates select="*" mode="M35"/>
   </xsl:template>
   <!--PATTERN SRD description element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SRD description element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Period" priority="1001" mode="M36">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:Period"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (every $AdaptationSet in child::dash:AdaptationSet satisfies $AdaptationSet/dash:EssentialProperty/@schemeIdUri = 'urn:mpeg:dash:srd:2014') then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (every $AdaptationSet in child::dash:AdaptationSet satisfies $AdaptationSet/dash:EssentialProperty/@schemeIdUri = 'urn:mpeg:dash:srd:2014') then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>When every Adaptation Set in a MPD has a SRD descriptor, at least one of this descriptor shall be a SupplementalProperty.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies (every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id] satisfies matches($srd/@value, '^(\d+,){4}\d+$') ) ) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies (every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id] satisfies matches($srd/@value, '^(\d+,){4}\d+$') ) ) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For a given source_id of the @value attribute, at least one of the EssentialProperty or SupplementalProperty in the containing Period shall specify the optional parameters W and H.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) &gt; 0) then if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( if (count(distinct-values(for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){6}\d+')] return concat(string(subsequence(tokenize($srd/@value,','),6,1)), string(subsequence(tokenize($srd/@value,','),7,1))) ) ) &gt; 1 ) then every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id] satisfies matches($srd/@value, '^(\d+,){6}\d+') else true() ) ) then true() else false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) &gt; 0) then if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( if (count(distinct-values(for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){6}\d+')] return concat(string(subsequence(tokenize($srd/@value,','),6,1)), string(subsequence(tokenize($srd/@value,','),7,1))) ) ) &gt; 1 ) then every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id] satisfies matches($srd/@value, '^(\d+,){6}\d+') else true() ) ) then true() else false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For a given source_id of the @value attribute, if two SRD elements (indistinctively EssentialProperty or SupplementalProperty) explicitly specify a different pair of  values for the optional parameters (W,H) then all the remaining SRD element shall explicitly specify a pair of values for (W,H) too.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and matches(@value, '^(\d+,){6}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),2,1)),number(subsequence(tokenize($srd/@value,','),4,1)) ) ) le number(subsequence(tokenize($srd/@value,','),6,1))"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and matches(@value, '^(\d+,){6}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),2,1)),number(subsequence(tokenize($srd/@value,','),4,1)) ) ) le number(subsequence(tokenize($srd/@value,','),6,1))">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For a given source_id of the @value attribute, the values of x, w and W shall be such that, for each descriptor, the sum of x and w is smaller or equal to W.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) &gt; 0) then if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){4}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),2,1)),number(subsequence(tokenize($srd/@value,','),4,1)) ) ) le number(subsequence(tokenize(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and (subsequence(tokenize(@value,','),1,1) = $source_id) and (matches(@value, '^(\d+,){6}\d+'))]/@value,','),6,1)) ) ) then true() else false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) &gt; 0) then if (some $source_id in (for $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014'] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){4}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),2,1)),number(subsequence(tokenize($srd/@value,','),4,1)) ) ) le number(subsequence(tokenize(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and (subsequence(tokenize(@value,','),1,1) = $source_id) and (matches(@value, '^(\d+,){6}\d+'))]/@value,','),6,1)) ) ) then true() else false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For a given source_id of the @value attribute, the values of x, w and W shall be such that, for each descriptor, the sum of x and w is smaller or equal to W.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and matches(@value, '^(\d+,){6}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),3,1)),number(subsequence(tokenize($srd/@value,','),5,1)) ) ) le number(subsequence(tokenize($srd/@value,','),7,1))"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="every $srd in descendant::dash:*[@schemeIdUri = 'urn:mpeg:dash:srd:2014' and matches(@value, '^(\d+,){6}\d+')] satisfies sum((number(subsequence(tokenize($srd/@value,','),3,1)),number(subsequence(tokenize($srd/@value,','),5,1)) ) ) le number(subsequence(tokenize($srd/@value,','),7,1))">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For a given source_id of the @value attribute, the values of y, h and H shall be such that, for each descriptor, the sum of y and h is smaller or equal to H.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) &gt; 0) then if (some $source_id in (for $srd in descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( every $srd in descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and (subsequence(tokenize(@value,','),1,1) = $source_id) and (matches(@value, '^(\d+,){4}\d+'))] satisfies sum((number(subsequence(tokenize($srd/@value,','),3,1)),number(subsequence(tokenize($srd/@value,','),5,1)) ) ) le number(subsequence(tokenize(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){6}\d+')]/@value,','),7,1)) ) ) then true() else false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (count(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')]) &gt; 0) then if (some $source_id in (for $srd in descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014')] return subsequence(tokenize($srd/@value,','),1,1)) satisfies ( every $srd in descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and (subsequence(tokenize(@value,','),1,1) = $source_id) and (matches(@value, '^(\d+,){4}\d+'))] satisfies sum((number(subsequence(tokenize($srd/@value,','),3,1)),number(subsequence(tokenize($srd/@value,','),5,1)) ) ) le number(subsequence(tokenize(descendant::dash:*[(@schemeIdUri = 'urn:mpeg:dash:srd:2014') and subsequence(tokenize(@value,','),1,1) = $source_id and matches(@value, '^(\d+,){6}\d+')]/@value,','),7,1)) ) ) then true() else false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>For a given source_id of the @value attribute, the values of y, h and H shall be such that, for each descriptor, the sum of y and h is smaller or equal to H.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M36"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="dash:SupplementalProperty | dash:EssentialProperty"
                 priority="1000"
                 mode="M36">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SupplementalProperty | dash:EssentialProperty"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (parent::dash:AdaptationSet or parent::dash:SubRepresentation) then true() else false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (parent::dash:AdaptationSet or parent::dash:SubRepresentation) then true() else false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>An EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” shall be the child element of an AdaptationSet or a SubRepresentation element.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (@value) then true() else false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (@value) then true() else false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If an EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present, then the @value attribute must be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (matches(@value, '^\d+,\d+,\d+,\d+,\d+')) then true() else false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (matches(@value, '^\d+,\d+,\d+,\d+,\d+')) then true() else false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If an EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present, then the @value attribute must contain at least the mandatory comma separated parameters, i.e. source_id, x, y, w, h.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (matches(@value, '^(\d+,){4,7}\d+$')) then true() else false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@schemeIdUri = 'urn:mpeg:dash:srd:2014') then if (matches(@value, '^(\d+,){4,7}\d+$')) then true() else false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If an EssentialProperty or a  SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present, then each parameter value has to match the expected type format i.e. non-negative integer in decimal representation.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((@schemeIdUri = 'urn:mpeg:dash:srd:2014') and matches(@value, '^(\d+,){5}\d+$')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:srd:2014') and matches(@value, '^(\d+,){5}\d+$')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If an EssentialProperty or a SupplementalProperty descriptor with @schemeIdUri equal to “urn:mpeg:dash:srd:2014” is present and the @value attribute contains the optional parameter W then the optional parameter H shall be present too. In addition, if the optional parameter spatial_set_id is present, then the optional parameters W and H shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M36"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M36"/>
   <xsl:template match="@*|node()" priority="-2" mode="M36">
      <xsl:apply-templates select="*" mode="M36"/>
   </xsl:template>
   <!--PATTERN MPD element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">MPD element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:MPD" priority="1000" mode="M37">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:MPD"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@type = 'static' and @timeShiftBufferDepth and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'static' and @timeShiftBufferDepth and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "static" and if the profile contains a DASH-IF IOP profile, then the timeShiftBufferDepth shall not be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@type = 'static' and @minimumUpdatePeriod and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@type = 'static' and @minimumUpdatePeriod and contains(@profiles, 'http://dashif.org/guidelines/dash')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If MPD is of type "static" and if the profile contains a DASH-IF IOP profile, then the minimumUpdatePeriod shall not be defined.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @type='dynamic' and not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @type='dynamic' and not(contains(@profiles, 'urn:mpeg:dash:profile:isoff-live:2011'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP Section 3.2.2.2: For dynamic MPD, the @profile shall include urn:mpeg:dash:profile:isoff-live:2011. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M37"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M37"/>
   <xsl:template match="@*|node()" priority="-2" mode="M37">
      <xsl:apply-templates select="*" mode="M37"/>
   </xsl:template>
   <!--PATTERN Period element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Period element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Period" priority="1000" mode="M38">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:Period"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and dash:SegmentList) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and dash:SegmentList) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP Section 3.2.2: "the Period.SegmentList element shall not be present" violated here </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (count(child::dash:AdaptationSet[@contentType='video']) &gt; 1) and (count(descendant::dash:Role[@value='main'])=0)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (count(child::dash:AdaptationSet[@contentType='video']) &gt; 1) and (count(descendant::dash:Role[@value='main'])=0)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.2: "If a Period contains multiple Adaptation Sets with value of the @contentType="video" then at least one Adaptation Set shall contain a Role el-ement $&lt;$Role scheme="urn:mpeg:dash:role:2011" value="main"&gt;" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M38"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M38"/>
   <xsl:template match="@*|node()" priority="-2" mode="M38">
      <xsl:apply-templates select="*" mode="M38"/>
   </xsl:template>
   <!--PATTERN AdaptationSet element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AdaptationSet element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:AdaptationSet" priority="1000" mode="M39">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:AdaptationSet"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ((descendant::dash:SupplementalProperty/@value = following-sibling::dash:AdaptationSet/@id) and (@segmentAlignment='true') and (following-sibling::dash:AdaptationSet/@segmentAlignment = 'false')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((descendant::dash:SupplementalProperty/@value = following-sibling::dash:AdaptationSet/@id) and (@segmentAlignment='true') and (following-sibling::dash:AdaptationSet/@segmentAlignment = 'false')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If the content author signals the ability of Adaptation Set switching and as @segmentAlignment or @subsegmentAlignment are set to TRUE for one Adaptation Set, the (Sub)Segment alignment shall hold for all Representations in all Adaptation Sets for which the @id value is included in the @value attribute of the Supplemental descriptor.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@par)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@par)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="video" the following attributes shall be present: @par" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and (@scanType and not(@scanType='progressive'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and (@scanType and not(@scanType='progressive'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For Adaptation Set or for any Representation within an Adaptation Set with value of the @contentType="video" the attribute @scanType shall either not be present or shall be set to 'progressive' ", violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='audio' and not(@lang)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='audio' and not(@lang)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="audio" the following attributes shall be present: @lang" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxWidth) and not(@width)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxWidth) and not(@width)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxWidth or @width" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxHeight) and not(@height)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxHeight) and not(@height)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxHeight or @height" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxFrameRate) and not(@frameRate)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @contentType='video' and not(@maxFrameRate) and not(@frameRate)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with @contentType="video" the following attributes shall be present: @maxFrameRate or @frameRate" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-live:2011') and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.2.2: For Live Profile @segmentAlignment shall be set to true for all Adaptation Sets</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-ondemand:2011') and (not(@subSegmentAlignment) or @subSegmentAlignment='false')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-ondemand:2011') and (not(@subSegmentAlignment) or @subSegmentAlignment='false')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.2.2: For On-Demand Profile @subSegmentAlignment shall be set to true for all Adaptation Sets</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @mimeType and not((@mimeType = 'video/mp4') or (@mimeType = 'audio/mp4') or (@mimeType = 'application/mp4') or (@mimeType = 'application/ttml+xml') or (@mimeType = 'text/vtt') or (@mimeType = 'image/jpeg'))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and @mimeType and not((@mimeType = 'video/mp4') or (@mimeType = 'audio/mp4') or (@mimeType = 'application/mp4') or (@mimeType = 'application/ttml+xml') or (@mimeType = 'text/vtt') or (@mimeType = 'image/jpeg'))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>If a DASH-IF profile identifier is present, for the Adaptation Sets the mimeType shall be one of the six following type: "video/mp4", "audio/mp4", "application/mp4", "application/ttml+xml", "text/vtt" or "image/jpeg"</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((@profiles and contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((@profiles and contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012')) or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (not(@segmentAlignment) or @segmentAlignment='false')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - For HbbTV profile, @segmentAlignment shall be set to true for all Adaptation Sets as it is based on live profile</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M39"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M39"/>
   <xsl:template match="@*|node()" priority="-2" mode="M39">
      <xsl:apply-templates select="*" mode="M39"/>
   </xsl:template>
   <!--PATTERN Representation element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Representation element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Representation" priority="1000" mode="M40">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:Representation"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@mimeType and following-sibling::dash:Representation/@mimeType and not(following-sibling::dash:Representation/@mimeType = @mimeType)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@mimeType and following-sibling::dash:Representation/@mimeType and not(following-sibling::dash:Representation/@mimeType = @mimeType)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP (v3.3), Section 3.2.13 : "In contrast to MPEG-DASH which does not prohibit the use of multiplexed Representations, in the DASH-IF IOPs one Adaptation Set always contains exactly a single media type.".</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (((@width != preceding-sibling::dash:Representation/@width) and not(parent::dash:AdaptationSet/@maxWidth)) or ((@height != preceding-sibling::dash:Representation/@height) and not(parent::dash:AdaptationSet/@maxHeight)) or ((@frameRate != preceding-sibling::dash:Representation/@frameRate) and not(parent::dash:AdaptationSet/@maxFrameRate)))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (((@width != preceding-sibling::dash:Representation/@width) and not(parent::dash:AdaptationSet/@maxWidth)) or ((@height != preceding-sibling::dash:Representation/@height) and not(parent::dash:AdaptationSet/@maxHeight)) or ((@frameRate != preceding-sibling::dash:Representation/@frameRate) and not(parent::dash:AdaptationSet/@maxFrameRate)))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Adaptation Sets with value of the @contentType="video" the following attributes shall be present: @maxWidth (or @width if all Representations have the same width), @maxHeight (or @height if all Representations have the same width), @maxFrameRate (or @frameRate if all Representations have the same width)" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and ((not(@width) and not(parent::dash:AdaptationSet/@width)) or (not(@height) and not(parent::dash:AdaptationSet/@height)) or (not(@frameRate) and not(parent::dash:AdaptationSet/@frameRate)) or not(@sar))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and ((not(@width) and not(parent::dash:AdaptationSet/@width)) or (not(@height) and not(parent::dash:AdaptationSet/@height)) or (not(@frameRate) and not(parent::dash:AdaptationSet/@frameRate)) or not(@sar))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Representation within an Adaptation Set with value of the @contentType="video" the following attributes shall be present: @width, if not present in AdaptationSet element; @height, if not present in AdaptationSet element; @frameRate, if not present in AdaptationSet element; @sar" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (@scanType and not(@scanType='progressive')))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='video' and (@scanType and not(@scanType='progressive')))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For Adaptation Set or for any Representation within an Adaptation Set with value of the @contentType="video" the attribute @scanType shall either not be present or shall be set to 'progressive' ", violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='audio' and ((not(@audioSamplingRate) and not(parent::dash:AdaptationSet/@audioSamplingRate)) or (not(dash:AudioChannelConfiguration) and not(parent::dash:AdaptationSet/dash:AudioChannelConfiguration)))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and parent::dash:AdaptationSet/@contentType='audio' and ((not(@audioSamplingRate) and not(parent::dash:AdaptationSet/@audioSamplingRate)) or (not(dash:AudioChannelConfiguration) and not(parent::dash:AdaptationSet/dash:AudioChannelConfiguration)))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> DASH-IF IOP Section 3.2.4: "For any Representation within an Adaptation Set with value of the @contentType="audio" the following elements and attributes shall be present: @audioSamplingRate, if not present in AdaptationSet element; AudioChannelConfiguration, if not present in AdaptationSet element" violated here</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((count(tokenize(@codecs, ',')) &gt; 1) or (count(tokenize(parent::dash:AdaptationSet/@codecs, ',')) &gt; 1))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and ((count(tokenize(@codecs, ',')) &gt; 1) or (count(tokenize(parent::dash:AdaptationSet/@codecs, ',')) &gt; 1))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> If profiles contain dash-if profile identifier, "codecs" attribute on AdaptationSet level OR Representation level shall not contain more than one identifiers as a comma separated list</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:variable name="codec"
                    select="substring-before(ancestor-or-self::*/@codecs[1],'\.')"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if($codec = ('dvhe','dvav') and @dependencyId and not(@dependencyId = preceding-sibling::dash:Representation/@id))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if($codec = ('dvhe','dvav') and @dependencyId and not(@dependencyId = preceding-sibling::dash:Representation/@id))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The @dependencyId attribute on the Enhancement Layer Representation shall refer to the Base Layer Representation @id attribute.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@profiles and ((parent::dash:AdaptationSet/@profiles and not(contains(parent::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@profiles and ((parent::dash:AdaptationSet/@profiles and not(contains(parent::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (((@profiles and contains(@profiles, 'http://dashif.org/guidelines/dash')) or (not(@profiles) and parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash'))) and ((@profiles and contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(@profiles) and parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011'))) and (not(dash:SegmentBase) and not(parent::dash:AdaptationSet/dash:SegmentBase) and not(ancestor::dash:Period/dash:SegmentBase))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (((@profiles and contains(@profiles, 'http://dashif.org/guidelines/dash')) or (not(@profiles) and parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash'))) and ((@profiles and contains(@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(@profiles) and parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(@profiles) and not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011'))) and (not(dash:SegmentBase) and not(parent::dash:AdaptationSet/dash:SegmentBase) and not(ancestor::dash:Period/dash:SegmentBase))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP 4.3 Section 3.2.1 - "For on-demand profiles, @indexRange attribute shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M40"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M40"/>
   <xsl:template match="@*|node()" priority="-2" mode="M40">
      <xsl:apply-templates select="*" mode="M40"/>
   </xsl:template>
   <!--PATTERN SubRepresentation element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SubRepresentation element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SubRepresentation" priority="1000" mode="M41">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SubRepresentation"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (@profiles and ((parent::dash:Representation/@profiles and not(contains(parent::dash:Representation/@profiles, @profiles))) or (ancestor::dash:AdaptationSet/@profiles and not(contains(ancestor::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (@profiles and ((parent::dash:Representation/@profiles and not(contains(parent::dash:Representation/@profiles, @profiles))) or (ancestor::dash:AdaptationSet/@profiles and not(contains(ancestor::dash:AdaptationSet/@profiles, @profiles))) or (ancestor::dash:MPD/@profiles and not(contains(ancestor::dash:MPD/@profiles, @profiles))))) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value of @profiles shall be a subset of the respective value in any higher level of the document hierarchy</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M41"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M41"/>
   <xsl:template match="@*|node()" priority="-2" mode="M41">
      <xsl:apply-templates select="*" mode="M41"/>
   </xsl:template>
   <!--PATTERN SegmentBase element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">SegmentBase element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SegmentBase" priority="1000" mode="M42">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl" context="dash:SegmentBase"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (((parent::dash:Representation and ((parent::dash:Representation/@profiles and contains(parent::dash:Representation/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:Representation/@profiles) and (ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:Representation/@profiles) and not(ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash')))) or (parent::dash:AdaptationSet and ((parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash')))) or (parent::dash:Period and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash'))) and ((parent::dash:Representation and ((parent::dash:Representation/@profiles and contains(parent::dash:Representation/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:Representation/@profiles) and (ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:Representation/@profiles) and not(ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')))) or (parent::dash:AdaptationSet and ((parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')))) or (parent::dash:Period and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011'))) and not(@indexRange)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (((parent::dash:Representation and ((parent::dash:Representation/@profiles and contains(parent::dash:Representation/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:Representation/@profiles) and (ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:Representation/@profiles) and not(ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash')))) or (parent::dash:AdaptationSet and ((parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'http://dashif.org/guidelines/dash')) or (not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash')))) or (parent::dash:Period and contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash'))) and ((parent::dash:Representation and ((parent::dash:Representation/@profiles and contains(parent::dash:Representation/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:Representation/@profiles) and (ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:Representation/@profiles) and not(ancestor::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')))) or (parent::dash:AdaptationSet and ((parent::dash:AdaptationSet/@profiles and contains(parent::dash:AdaptationSet/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')) or (not(parent::dash:AdaptationSet/@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011')))) or (parent::dash:Period and contains(ancestor::dash:MPD/@profiles, 'urn:mpeg:dash:profile:isoff-on-demand:2011'))) and not(@indexRange)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>DASH-IF IOP 4.3 Section 3.2.1 - "For on-demand profiles, @indexRange attribute shall be present.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M42"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M42"/>
   <xsl:template match="@*|node()" priority="-2" mode="M42">
      <xsl:apply-templates select="*" mode="M42"/>
   </xsl:template>
   <!--PATTERN ContentProtection element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">ContentProtection element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:ContentProtection" priority="1000" mode="M43">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:ContentProtection"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (not(parent::dash:AdaptationSet)) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (not(parent::dash:AdaptationSet)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
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
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ((@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and (@value= 'cenc') and not(parent::dash:AdaptationSet)) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The ContentProtection descriptor for the mp4 protection scheme with @schemeIdUri 'urn:mpeg:dash:mp4protection:2011' and @value 'cenc' shall be present in the AdaptationSet element.
			</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if ( contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(@cenc:default_KID) ) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if ( contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dash') and (@schemeIdUri = 'urn:mpeg:dash:mp4protection:2011') and not(@cenc:default_KID) ) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The ContentProtection Descriptor for the mp4protection scheme shall contain the attribute @cenc:default_KID.
                        </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M43"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M43"/>
   <xsl:template match="@*|node()" priority="-2" mode="M43">
      <xsl:apply-templates select="*" mode="M43"/>
   </xsl:template>
   <!--PATTERN AudioChannelConfiguration element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AudioChannelConfiguration element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:AudioChannelConfiguration" priority="1000" mode="M44">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AudioChannelConfiguration"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#ac-4') and not(@schemeIdUri='tag:dolby.com,2014:dash:audio_channel_configuration:2011')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#ac-4') and not(@schemeIdUri='tag:dolby.com,2014:dash:audio_channel_configuration:2011')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> If profile http://dashif.org/guidelines/dashif#ac-4 is used, then schemeIdUri attribute shall be tag:dolby.com,2014:dash:audio_channel_configuration:2011.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#mpeg-h-3da') and not(@schemeIdUri='urn:mpeg:mpegB:cicp:ChannelConfiguration')) then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if (contains(ancestor::dash:MPD/@profiles, 'http://dashif.org/guidelines/dashif#mpeg-h-3da') and not(@schemeIdUri='urn:mpeg:mpegB:cicp:ChannelConfiguration')) then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> If profile http://dashif.org/guidelines/dashif#mpeg-h-3da is used, then schemeIdUri attribute shall be urn:mpeg:mpegB:cicp:ChannelConfiguration.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M44"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M44"/>
   <xsl:template match="@*|node()" priority="-2" mode="M44">
      <xsl:apply-templates select="*" mode="M44"/>
   </xsl:template>
   <!--PATTERN EssentialProperty element-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">EssentialProperty element</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:EssentialProperty" priority="1000" mode="M45">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:EssentialProperty"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not((count(tokenize(@value, 'x'))=2)))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not((count(tokenize(@value, 'x'))=2)))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then value shall provide horizontal and vertical number of tiles separated by an 'x'. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@bandwidth))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@bandwidth))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then bandwidth shall be used to describe the tiling.'. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@height) and not(ancestor::dash:AdaptationSet/@height))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@height) and not(ancestor::dash:AdaptationSet/@height))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then height shall be used to describe the tiling. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@width) and not(ancestor::dash:AdaptationSet/@width))then false() else true()"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="if((@schemeIdUri= 'http://dashif.org/guidelines/thumbnail_tile') and not(parent::dash:Representation/@width) and not(ancestor::dash:AdaptationSet/@width))then false() else true()">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text> If EssentialProperty descriptor with @schemeIdUri set to http://dashif.org/guidelines/thumbnail_tile is present, then width shall be used to describe the tiling. </svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M45"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M45"/>
   <xsl:template match="@*|node()" priority="-2" mode="M45">
      <xsl:apply-templates select="*" mode="M45"/>
   </xsl:template>
   <xsl:param name="dvbdash-profile-2017" select="'dvbdash-profile-2017'"/>
   <!--PATTERN AdaptationSet element for DVB DASH 2017 profile-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AdaptationSet element for DVB DASH 2017 profile</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][not(dlb:isAuxiliaryStream(.))]/dash:Representation"
                 priority="1002"
                 mode="M51">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][not(dlb:isAuxiliaryStream(.))]/dash:Representation"/>
      <!--REPORT -->
      <xsl:if test="@mimeType != ancestor::dash:AdaptationSet/dash:Representation/@mimeType">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="@mimeType != ancestor::dash:AdaptationSet/dash:Representation/@mimeType">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>@mimeType shall be common between all Representations in an Adaptation Set</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--REPORT warn-->
      <xsl:if test="@codecs   != ancestor::dash:AdaptationSet/dash:Representation/@codecs">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="@codecs != ancestor::dash:AdaptationSet/dash:Representation/@codecs">
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>@codecs should be common between all Representations in an Adaptation Set</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--REPORT warn-->
      <xsl:if test="@audioSamplingRate != ancestor::dash:AdaptationSet/dash:Representation/@audioSamplingRate">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="@audioSamplingRate != ancestor::dash:AdaptationSet/dash:Representation/@audioSamplingRate">
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>@audioSamplingRate should be common between all Representations in an Adaptation Set</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M51"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][not(dlb:isAuxiliaryStream(.))]/dash:Representation/dash:AudioChannelConfiguration"
                 priority="1001"
                 mode="M51">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][not(dlb:isAuxiliaryStream(.))]/dash:Representation/dash:AudioChannelConfiguration"/>
      <xsl:variable name="siu" select="@schemeIdUri"/>
      <xsl:variable name="val" select="@value"/>
      <!--REPORT warn-->
      <xsl:if test="$val != ancestor::dash:AdaptationSet/dash:Representation/dash:AudioChannelConfiguration[@schemeIdUri = $siu]/@value">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="$val != ancestor::dash:AdaptationSet/dash:Representation/dash:AudioChannelConfiguration[@schemeIdUri = $siu]/@value">
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>audioChannelConfiguration should be common between all Representations in an Adaptation Set</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M51"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][dlb:isAuxiliaryStream(.)]"
                 priority="1000"
                 mode="M51">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:AdaptationSet[dlb:isAdaptationSetAudio(.)][dlb:isAuxiliaryStream(.)]"/>
      <!--REPORT -->
      <xsl:if test="dash:AudioChannelConfiguration or dash:Role or dash:Accessibility or @lang">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="dash:AudioChannelConfiguration or dash:Role or dash:Accessibility or @lang">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>All Adaptation Sets that refer to Auxiliary Audio streams may not contain the @lang attribute and Role,
				Accessibility, AudioChannelConfiguration descriptors</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M51"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M51"/>
   <xsl:template match="@*|node()" priority="-2" mode="M51">
      <xsl:apply-templates select="*" mode="M51"/>
   </xsl:template>
   <!--PATTERN Preselection element for DVB DASH 2017 profile-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Preselection element for DVB DASH 2017 profile</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:Preselection[dlb:isAdaptationSetAudio(.)]"
                 priority="1000"
                 mode="M52">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]/dash:Period/dash:Preselection[dlb:isAdaptationSetAudio(.)]"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="dash:Role[@schemeIdUri='urn:mpeg:dash:role:2011']"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="dash:Role[@schemeIdUri='urn:mpeg:dash:role:2011']">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Every AC-4 or MPEG-H Audio Preselection element shall include at least one Role element using the scheme
				"urn:mpeg:dash:role:2011" as defined in ISO/IEC 23009-1 [1].</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:variable name="psc" select="tokenize(@preselectionComponents,' ')"/>
      <xsl:variable name="bundleID"
                    select="../dash:AdaptationSet[@id = $psc][not(dlb:isAuxiliaryStream(.))]/@id"/>
      <!--REPORT -->
      <xsl:if test="count(../dash:Preselection[$bundleID = tokenize(@preselectionComponents,' ')]) &gt; 1 and not(../dash:Preselection[$bundleID = tokenize(@preselectionComponents,' ')]/dash:Role[@schemeIdUri='urn:mpeg:dash:role:2011'][@value='main'])">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="count(../dash:Preselection[$bundleID = tokenize(@preselectionComponents,' ')]) &gt; 1 and not(../dash:Preselection[$bundleID = tokenize(@preselectionComponents,' ')]/dash:Role[@schemeIdUri='urn:mpeg:dash:role:2011'][@value='main'])">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>If there is more than one audio Preselection associated with an audio bundle, at least one of the Preselection
				elements shall be tagged with an @value set to "main".</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--REPORT -->
      <xsl:if test="some $x in tokenize(@preselectionComponents,' ') satisfies not($x = preceding-sibling::dash:AdaptationSet/@id)">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="some $x in tokenize(@preselectionComponents,' ') satisfies not($x = preceding-sibling::dash:AdaptationSet/@id)">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>
				@preselectionComponents specifies the ids of the contained Adaptation Sets or Content Components that belong to this Preselection
				as white space separated list in processing order.
			</svrl:text>
            <svrl:diagnostic-reference diagnostic="preselID">
A preselectionComponent references a non existent AdaptationSet</svrl:diagnostic-reference>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M52"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M52"/>
   <xsl:template match="@*|node()" priority="-2" mode="M52">
      <xsl:apply-templates select="*" mode="M52"/>
   </xsl:template>
   <!--PATTERN AdaptationSet and Preselection element for AC-4 for DVB DASH 2017 profile-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AdaptationSet and Preselection element for AC-4 for DVB DASH 2017 profile</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]//*[self::dash:AdaptationSet or self::dash:Preselection or self::Representation][dlb:isAdaptationSetAC4(.)]"
                 priority="1000"
                 mode="M54">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:MPD[$dvbdash-profile-2017 = tokenize(@profiles,' ')]//*[self::dash:AdaptationSet or self::dash:Preselection or self::Representation][dlb:isAdaptationSetAC4(.)]"/>
      <xsl:variable name="cod" select="tokenize(dlb:getNearestCodecString(.),'\.')"/>
      <xsl:variable name="bs_ver" select="$cod[2]"/>
      <xsl:variable name="pres_ver" select="$cod[3]"/>
      <xsl:variable name="md_compat" select="$cod[4]"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="$bs_ver = '02'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="$bs_ver = '02'">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>AC-4 audio should be encoded using bitstream_version = 2 (is <xsl:text/>
                  <xsl:value-of select="$bs_ver"/>
                  <xsl:text/>).</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="$pres_ver = '01'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="$pres_ver = '01'">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The presentation_version field according to clause 6.2.1.3 of ETSI TS 103 190-2 [46] shall be set
				to the value 1.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="$md_compat = ('00','01','02','03')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="$md_compat = ('00','01','02','03')">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The md_compat field as defined by clause 6.3.2.2.3 of ETSI TS 103 190-2 [46] shall be less than or
				equal to three.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="@audioSamplingRate = (48000,96000,192000)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="@audioSamplingRate = (48000,96000,192000)">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>An AC-4 elementary stream shall be encoded with a sampling rate of 48 kHz, 96 kHz or 192 kHz.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="not(*[self::dash:SupplementalProperty or self::dash:EssentialProperty][@schemeIdUri = 'tag:dolby.com,2017:dash:audio_frame_rate:2017'])     or *[self::dash:SupplementalProperty or self::dash:EssentialProperty][@schemeIdUri = 'tag:dolby.com,2017:dash:audio_frame_rate:2017']/@value &lt;= 60     or not(ancestor::dash:Period/dash:AdaptationSet[dlb:isAdaptationSetVideo(.)]/@frameRate &lt;= 60)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="not(*[self::dash:SupplementalProperty or self::dash:EssentialProperty][@schemeIdUri = 'tag:dolby.com,2017:dash:audio_frame_rate:2017']) or *[self::dash:SupplementalProperty or self::dash:EssentialProperty][@schemeIdUri = 'tag:dolby.com,2017:dash:audio_frame_rate:2017']/@value &lt;= 60 or not(ancestor::dash:Period/dash:AdaptationSet[dlb:isAdaptationSetVideo(.)]/@frameRate &lt;= 60)">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>An Adaptation Set shall not contain audio with a frame rate &gt; 60 Hz unless all video adaptationSets in
				the Period contain only video with a frame rate &gt; 60 Hz.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M54"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M54"/>
   <xsl:template match="@*|node()" priority="-2" mode="M54">
      <xsl:apply-templates select="*" mode="M54"/>
   </xsl:template>
   <xsl:param name="NSDLB_acc2014"
              select="'tag:dolby.com,2014:dash:audio_channel_configuration:2011'"/>
   <xsl:param name="NSDLB_acc2015"
              select="'tag:dolby.com,2015:dash:audio_channel_configuration:2015'"/>
   <xsl:param name="NSMPEG_acc" select="'urn:mpeg:mpegB:cicp:ChannelConfiguration'"/>
   <xsl:param name="AC4_MIME"
              select="'(ac-4((\.02\.01\.0[0-3])|(\.00\.00\.0[0-3])))'"/>
   <xsl:param name="delim" select="'(^|\s+)'"/>
   <!--PATTERN AdaptationSet and Preselection element for AC-4-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AdaptationSet and Preselection element for AC-4</svrl:text>
   <!--RULE -->
   <xsl:template match="*[self::dash:AdaptationSet or self::dash:Preselection][dlb:isAdaptationSetAC4(.)]"
                 priority="1000"
                 mode="M64">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="*[self::dash:AdaptationSet or self::dash:Preselection][dlb:isAdaptationSetAC4(.)]"/>
      <xsl:variable name="codecs" select="dlb:getNearestCodecString(.)"/>
      <xsl:variable name="mstring" select="concat('^\s*(',$AC4_MIME,$delim,')+$')"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="matches($codecs,concat('(',$delim,$AC4_MIME,')+$'),'i')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches($codecs,concat('(',$delim,$AC4_MIME,')+$'),'i')">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The @codecs attribute shall conform to the syntax described in IETF RFC 6381. The value of the parameter
				shall be set to a dot-separated list of four parts of which the last three are two-digit hexadecimal numbers.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:variable name="cod" select="tokenize($codecs,'\.')"/>
      <xsl:variable name="bs_ver" select="$cod[2]"/>
      <xsl:variable name="pres_ver" select="$cod[3]"/>
      <xsl:variable name="md_compat" select="$cod[4]"/>
      <!--REPORT -->
      <xsl:if test="$bs_ver = '00' and @mimeType != ('audio/mp4','video/mp4')">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="$bs_ver = '00' and @mimeType != ('audio/mp4','video/mp4')">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>The value of the mimeType attribute shall be set to 'audio/mp4' or 'video/mp4'.</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--REPORT -->
      <xsl:if test="matches($codecs, 'ac-4\.(01|02)','i') and not(@audioSamplingRate)">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="matches($codecs, 'ac-4\.(01|02)','i') and not(@audioSamplingRate)">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>@audioSamplingRate shall be set to the sampling frequency derived from the parameters fs_index and
				dsi_sf_multiplier, contained in ac4_dsi_v1.</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--REPORT -->
      <xsl:if test="$bs_ver = ('01','02') and @mimeType != 'audio/mp4'">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="$bs_ver = ('01','02') and @mimeType != 'audio/mp4'">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>The value of the mimeType attribute shall be set to 'audio/mp4'.</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--REPORT -->
      <xsl:if test="$bs_ver = ('01','02') and @startWithSAP != '1'">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="$bs_ver = ('01','02') and @startWithSAP != '1'">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>The @startWithSAP value shall be set to '1'.</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M64"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M64"/>
   <xsl:template match="@*|node()" priority="-2" mode="M64">
      <xsl:apply-templates select="*" mode="M64"/>
   </xsl:template>
   <!--PATTERN Representation for AC-4-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Representation for AC-4</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Representation[matches(dlb:getNearestCodecString(.), 'ac-4\.00','i')]"
                 priority="1000"
                 mode="M65">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:Representation[matches(dlb:getNearestCodecString(.), 'ac-4\.00','i')]"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="dash:AudioChannelConfiguration"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="dash:AudioChannelConfiguration">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The representation DASH element shall include an AudioChannelConfiguration DASH descriptor.</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M65"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M65"/>
   <xsl:template match="@*|node()" priority="-2" mode="M65">
      <xsl:apply-templates select="*" mode="M65"/>
   </xsl:template>
   <!--PATTERN Role element for AC-4-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">Role element for AC-4</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:Role[matches(dlb:getNearestCodecString(.), 'ac-4\.00')]"
                 priority="1000"
                 mode="M66">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:Role[matches(dlb:getNearestCodecString(.), 'ac-4\.00')]"/>
      <!--REPORT -->
      <xsl:if test="@schemeIdUri = 'urn:mpeg:dash:role:2011' and     not(@value = ('main','alternate','commentary','dub'))">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="@schemeIdUri = 'urn:mpeg:dash:role:2011' and not(@value = ('main','alternate','commentary','dub'))">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>The value of Role (role) shall be main, alternate, commentary.</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M66"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M66"/>
   <xsl:template match="@*|node()" priority="-2" mode="M66">
      <xsl:apply-templates select="*" mode="M66"/>
   </xsl:template>
   <!--PATTERN AC-4 supplemental property descriptors-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AC-4 supplemental property descriptors</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:SupplementalProperty[@schemeIdUri = 'tag:dolby.com,2016:dash:virtualized_content:2016'][matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')]"
                 priority="1000"
                 mode="M67">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:SupplementalProperty[@schemeIdUri = 'tag:dolby.com,2016:dash:virtualized_content:2016'][matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')]"/>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="@value='1'"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="@value='1'">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The @value attribute of the immersive audio for headphones descriptor shall be 1</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M67"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M67"/>
   <xsl:template match="@*|node()" priority="-2" mode="M67">
      <xsl:apply-templates select="*" mode="M67"/>
   </xsl:template>
   <!--PATTERN AudioChannelConfiguration element for AC-4 part 1-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AudioChannelConfiguration element for AC-4 part 1</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.00')][@schemeIdUri eq $NSDLB_acc2014]"
                 priority="1000"
                 mode="M68">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.00')][@schemeIdUri eq $NSDLB_acc2014]"/>
      <!--REPORT warn-->
      <xsl:if test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2014,$NSMPEG_acc)]) &gt; 1">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2014,$NSMPEG_acc)]) &gt; 1">
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>
               <xsl:text/>
               <xsl:value-of select="$NSDLB_acc2014"/>
               <xsl:text/> or <xsl:text/>
               <xsl:value-of select="$NSMPEG_acc"/>
               <xsl:text/>
            </svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="matches(@value,'^[0-9a-fA-F]{4}$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(@value,'^[0-9a-fA-F]{4}$')">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value element shall contain a four-digit hexadecimal representation of the 16-bit field which describes
				the channel assignment of the referenced AC-4 elementary stream</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:variable name="val6" select="concat('00',@value)"/>
      <xsl:variable name="x" select="dlb:dlb2mpg($val6)"/>
      <!--REPORT -->
      <xsl:if test="$x != 0">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="$x != 0">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>Use &lt;<xsl:text/>
               <xsl:value-of select="name(.)"/>
               <xsl:text/> schemeIdUri="<xsl:text/>
               <xsl:value-of select="$NSMPEG_acc"/>
               <xsl:text/>" value="<xsl:text/>
               <xsl:value-of select="$x"/>
               <xsl:text/>"/&gt;</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M68"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M68"/>
   <xsl:template match="@*|node()" priority="-2" mode="M68">
      <xsl:apply-templates select="*" mode="M68"/>
   </xsl:template>
   <!--PATTERN AudioChannelConfiguration element for AC-4 part 2-->
   <svrl:text xmlns:svrl="http://purl.oclc.org/dsdl/svrl">AudioChannelConfiguration element for AC-4 part 2</svrl:text>
   <!--RULE -->
   <xsl:template match="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][not(@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc))]"
                 priority="1002"
                 mode="M69">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][not(@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc))]"/>
      <!--REPORT warn-->
      <xsl:if test="true()">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="true()">
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>
				Unspecified schemeIdUri in AudioChannelConfiguration element
			</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M69"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][@schemeIdUri eq $NSDLB_acc2015]"
                 priority="1001"
                 mode="M69">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][@schemeIdUri eq $NSDLB_acc2015]"/>
      <!--REPORT warn-->
      <xsl:if test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc)]) &gt; 1">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc)]) &gt; 1">
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>
               <xsl:text/>
               <xsl:value-of select="$NSDLB_acc2015"/>
               <xsl:text/> or <xsl:text/>
               <xsl:value-of select="$NSMPEG_acc"/>
               <xsl:text/>
            </svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="matches(@value,'^[0-9a-fA-F]{6}$')"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(@value,'^[0-9a-fA-F]{6}$')">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>The value element shall contain a six-digit hexadecimal representation of the 24-bit field which describes
				the channel assignment of the referenced AC-4 elementary stream</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:variable name="x" select="dlb:dlb2mpg(@value)"/>
      <!--REPORT -->
      <xsl:if test="$x != 0">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl" test="$x != 0">
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>For all AC-4 channel configurations that are mappable to the MPEG channel configuration scheme, the scheme described by
				@schemeIdUri="urn:mpeg:mpegB:cicp:ChannelConfiguration" shall be used</svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <xsl:apply-templates select="*" mode="M69"/>
   </xsl:template>
   <!--RULE -->
   <xsl:template match="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][@schemeIdUri eq $NSMPEG_acc]"
                 priority="1000"
                 mode="M69">
      <svrl:fired-rule xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                       context="dash:AudioChannelConfiguration[matches(dlb:getNearestCodecString(.),'ac-4\.(01|02)')][@schemeIdUri eq $NSMPEG_acc]"/>
      <!--REPORT warn-->
      <xsl:if test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc)]) &gt; 1">
         <svrl:successful-report xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                 test="count(ancestor::*/dash:AudioChannelConfiguration[@schemeIdUri = ($NSDLB_acc2015,$NSMPEG_acc)]) &gt; 1">
            <xsl:attribute name="role">warn</xsl:attribute>
            <xsl:attribute name="location">
               <xsl:apply-templates select="." mode="schematron-select-full-path"/>
            </xsl:attribute>
            <svrl:text>
               <xsl:text/>
               <xsl:value-of select="$NSDLB_acc2015"/>
               <xsl:text/> or <xsl:text/>
               <xsl:value-of select="$NSMPEG_acc"/>
               <xsl:text/>
            </svrl:text>
         </svrl:successful-report>
      </xsl:if>
      <!--ASSERT -->
      <xsl:choose>
         <xsl:when test="matches(@value,'^[0-9]+$') and @value = (1 to 7,9 to 12,14,16 to 17,19)"/>
         <xsl:otherwise>
            <svrl:failed-assert xmlns:svrl="http://purl.oclc.org/dsdl/svrl"
                                test="matches(@value,'^[0-9]+$') and @value = (1 to 7,9 to 12,14,16 to 17,19)">
               <xsl:attribute name="location">
                  <xsl:apply-templates select="." mode="schematron-select-full-path"/>
               </xsl:attribute>
               <svrl:text>Valid values are 1-7, 9-12, 14, 16-17, and 19</svrl:text>
            </svrl:failed-assert>
         </xsl:otherwise>
      </xsl:choose>
      <xsl:apply-templates select="*" mode="M69"/>
   </xsl:template>
   <xsl:template match="text()" priority="-1" mode="M69"/>
   <xsl:template match="@*|node()" priority="-2" mode="M69">
      <xsl:apply-templates select="*" mode="M69"/>
   </xsl:template>
</xsl:stylesheet>
