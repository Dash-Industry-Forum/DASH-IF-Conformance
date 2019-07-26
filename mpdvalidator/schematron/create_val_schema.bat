java -jar ../saxon9he.jar -versionmsg:off -s:schematron.sch -o:tmp/new_schema1.sch -xsl:schematron/iso_dsdl_include.xsl
java -jar ../saxon9he.jar -versionmsg:off -s:tmp/new_schema1.sch -o:tmp/new_schema2.sch -xsl:schematron/iso_abstract_expand.xsl
java -jar ../saxon9he.jar -versionmsg:off -s:tmp/new_schema2.sch -o:output/val_schema.xsl -xsl:schematron/iso_svrl_for_xslt2.xsl

del tmp\*.sch
