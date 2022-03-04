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
 * The Initial Developer of the Original Code is Klagenfurt University, Austria
 * Portions created by the Initial Developer are Copyright (C) 2009
 * All Rights Reserved.
 *
 * The Initial Developer: Markus Waltl 
 * Contributor(s):
 *
 * ***** END LICENSE BLOCK ***** */
import javax.xml.bind.ValidationEvent;
import javax.xml.bind.ValidationEventHandler;
import javax.xml.bind.ValidationEventLocator;

/**
 * Class for handling validation events
 *	  
 * @author Markus Waltl <markus.waltl@itec.uni-klu.ac.at>
 */
public class EventHandler implements ValidationEventHandler {
	private static boolean errors = false;
	
	public boolean handleEvent(ValidationEvent ve) {
		// ignore warnings
		if (ve.getSeverity() != ValidationEvent.WARNING) {
			ValidationEventLocator vel = ve.getLocator();
			System.out.println("Line:Col[" + vel.getLineNumber() +
					":" + vel.getColumnNumber() +
					"]:" + ve.getMessage());
			errors = true;
		}
		return true;
	}
	
	public boolean hasErrors() {
		return errors;
	}
}
