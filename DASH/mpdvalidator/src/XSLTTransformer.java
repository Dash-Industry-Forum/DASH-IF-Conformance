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
import javax.xml.transform.Source;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.stream.StreamSource;
import javax.xml.transform.stream.StreamResult;
import java.io.*;

/**
 * Class for providing an XSLT transformation.
 * 
 * Furthermore, this class extracts error messages.
 * 
 * @author Markus Waltl <markus.waltl@itec.uni-klu.ac.at>
 */
public class XSLTTransformer {
	private final static String OPENTAG = "<svrl:failed-assert";
	private final static String CLOSETAG = "</svrl:failed-assert>";
	
    public static boolean transform(String xmlFileToCheck, String xsltFileToUse) throws Exception {
        if (xmlFileToCheck == null || xmlFileToCheck.toString().equals("") ||
        		xsltFileToUse == null || xsltFileToUse.equals("")) {
            System.err.println("Transformation cannot be done because either XML or XSLT is missing");
           	return false;
        }

        File xmlFile = new File(xmlFileToCheck);
        File xsltFile = new File(xsltFileToUse);

        Source xmlSource = new StreamSource(xmlFile);
        Source xsltSource = new StreamSource(xsltFile);

        TransformerFactory transFact =
                TransformerFactory.newInstance();
        Transformer trans = transFact.newTransformer(xsltSource);

        StreamResult result = new StreamResult(new StringWriter());
        trans.transform(xmlSource, result);

        String xmlString = result.getWriter().toString();
        
        if (Definitions.debug_)
        	System.out.println(xmlString);
        
        boolean hasNoErrors = true; 
        int startPos = xmlString.indexOf(OPENTAG);
        while (startPos > -1) {
        	hasNoErrors = false;
           	int endPos = xmlString.indexOf(CLOSETAG, startPos);
           	
           	System.out.println(xmlString.substring(startPos, endPos+CLOSETAG.length()));
           	
           	startPos = xmlString.indexOf(OPENTAG, endPos);        	
        }
                
        return hasNoErrors;
    }
}
