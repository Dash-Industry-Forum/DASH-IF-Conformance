Schemator validation for DASH
-----------------------------

created by Markus Waltl (Alpen-Adria-Universitaet Klagenfurt)

The validation currently uses XSLT1.0. The structure of the package is as
following:

|- examples/			<- all test files
|- output/			<- contains results and validation schema
|- schematron/			<- files needed for schematron transformation and validation
|- tmp/				<- temporary files (files are deleted after validation schema creation)
|- clean.bat			<- deletes the content of output (Windows)
|- clean.sh			<- deletes the content of output (Linux/Unix)
|- create_val_schema.bat	<- transforms and generates the validation schema (Windows)
|- create_val_schema.sh		<- transforms and generates the validation schema (Linux/Unix)
|- run_samples.bat		<- validates all files in examples and write the results to output (Windows)
|- run_samples.sh		<- validates all files in examples and write the results to output (Linux/Unix)
|- schematron.xsd		<- schematron validation file for DASH
|- reamde.txt			<- this file

