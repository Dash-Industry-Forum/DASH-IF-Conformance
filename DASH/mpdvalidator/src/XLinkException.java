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
 * Class for defining an XLink exception
 *	  
 * @author Markus Waltl <markus.waltl@itec.uni-klu.ac.at>
 */
public class XLinkException extends Exception {
	private static final long serialVersionUID = 3613362922909616340L;

	public XLinkException(String message) {
		super(message);
	}
}
