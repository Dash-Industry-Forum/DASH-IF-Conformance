/** ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla  Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and  limitations under the
 * License.
 *
 * The Initial Developer of the Original Code is Alpen-Adria-Universitaet Klagenfurt, Austria
 * Portions created by the Initial Developer are Copyright (C) 2011
 * All Rights Reserved.
 *
 * The Initial Developer: Markus Waltl 
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */
/**
 * Class storing definitions.
 * 
 * This class provides the only place for modifications if schemas, schematron
 * or anything else changes in DASH.
 * 
 * @author Markus Waltl <markus.waltl@itec.uni-klu.ac.at>
 */
public class Definitions {
	public static boolean debug_ = false;
	
	// Needed for Step 1: XLink Resolver
	public final static String XLINK_NAMESPACE = "http://www.w3.org/1999/xlink";
	public final static String HREF = "href";
	public final static String PROTOCOL = "http://";
	public final static String SECURE_PROTOCOL = "https://";
	public final static String RESOLVE_TO_ZERO = "urn:mpeg:dash:resolve-to-zero:2013";
	public static String tmpOutputFile_ = "";
	public static String mpdResultFile_ = "";
	
	// needed for Step 2 and 3: Schema validation and Schematron validation
	//TODO: will maybe be replaced with remote schema!
	public static String DASHXSDNAME = "schemas/DASH-MPD.xsd";	
	public static String XSLTFILE = "schematron/output/val_schema.xsl";	
}
