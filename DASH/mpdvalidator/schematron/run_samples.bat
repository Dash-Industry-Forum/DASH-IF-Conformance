@echo off

SET NUMBER_OF_MPDS=58

FOR /l %%i IN (1, 1, %NUMBER_OF_MPDS%) DO (
	echo Processing MPD %%i
	IF %%i lss 10 ( 
		java -jar ../saxon9he.jar -versionmsg:off -s:examples/ex0%%i.mpd -o:output/result_ex0%%i.xml -xsl:output/val_schema.xsl
	) else (
		java -jar ../saxon9he.jar -versionmsg:off -s:examples/ex%%i.mpd -o:output/result_ex%%i.xml -xsl:output/val_schema.xsl
	)
)

