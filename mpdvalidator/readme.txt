Validator for DASH
------------------

created by Markus Waltl (Alpen-Adria-Universitaet Klagenfurt)

The structure of the package is as following:

|- schemas/			<- schema files for DASH
|- schematron/			<- Schematron Rule based validator (stand-alone version included)
|- src/				<- source file of the validator

|- validator_examples/		<- examples for schema validation tests
|- xlink_examples/		<- examples for testing the XLinkResolver
|- build.xml			<- ant build file
|- changelog.txt		<- file for indicating changes
|- readme.txt			<- this file
|- saxon9he.jar			<- file needed by Saxon XSLT
|- xercesImpl.jar		<- file needed by Xerces



Note: you need at least Java 1.6.0 Update 17 because JAXB is already added in this version.

Note: Links for the XLinkResolver examples are currently set to samples on Alpen-Adria-Universitaet Klagenfurt servers! (will be changed in the final version!)
