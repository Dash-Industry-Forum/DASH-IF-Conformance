<?xml version="1.0" encoding="UTF-8"?>

<schema xmlns="http://purl.oclc.org/dsdl/schematron" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" queryBinding='xslt2' schemaVersion='ISO19757-3'>
    <ns prefix="dash" uri="urn:mpeg:dash:schema:mpd:2011"/>
    <ns prefix="dlb" uri="http://www.dolby.com/ns/2019/dash-if"/>

    <!-- checks if this stream is an auxiliary stream (signaled by the presence of a preselection property) -->
    <xsl:function name="dlb:isAuxiliaryStream" as="xs:boolean">
        <xsl:param name="x"/>
        <xsl:sequence select="exists($x/dash:EssentialProperty[@schemeIdUri='urn:mpeg:dash:preselection:2016'])"/>
    </xsl:function>

    <!-- retrieve the @codecs parameter of either this or any containing element, whichever comes first -->
    <xsl:function name="dlb:getNearestCodecString" as="xs:string">
        <xsl:param name="c"/>
        <xsl:value-of select="$c/ancestor-or-self::*/@codecs[1]"/>
    </xsl:function>

    <!-- check if the applicable @codecs parameter contains a certain string -->
    <xsl:function name="dlb:isAdaptationSetType" as="xs:boolean">
        <xsl:param name="x" as="element()"/>
        <xsl:param name="t" as="xs:string"/>
        <xsl:sequence select="matches(dlb:getNearestCodecString($x),$t)"/>
    </xsl:function>

    <xsl:function name="dlb:isAdaptationSetEC3" as="xs:boolean">
        <xsl:param name="x"/>
        <xsl:sequence select="dlb:isAdaptationSetType($x,'ec-3')"/>
    </xsl:function>

    <xsl:function name="dlb:isAdaptationSetAC4" as="xs:boolean">
        <xsl:param name="x"/>
        <xsl:sequence select="dlb:isAdaptationSetType($x,'ac-4')"/>
    </xsl:function>

    <xsl:function name="dlb:isAdaptationSetAudio" as="xs:boolean">
        <xsl:param name="x"/>
        <xsl:sequence select="dlb:isAdaptationSetType($x,'mp4a|ac-3|ec-3|ac-4|dtsc|dtsh|dtse|dtsl')"/>
    </xsl:function>

    <xsl:function name="dlb:isAdaptationSetVideo" as="xs:boolean">
        <xsl:param name="x"/>
        <xsl:sequence select="dlb:isAdaptationSetType($x,'avc|hvc|hev')"/>
    </xsl:function>

    <xsl:function name="dlb:fractionalToFloat" as="xs:float">
        <xsl:param name="x"/>
        <xsl:variable name="numden" select="tokenize($x,'/')"/>
        <xsl:value-of select="if (count($numden) eq 1) then $x else xs:float($numden[1]) div xs:float($numden[2])"/>
    </xsl:function>

    <!-- map from "Dolby style" channel configurations to MPEG channel configurations -->
    <xsl:function name="dlb:dlb2mpg" as="xs:integer">
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
</schema>

