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
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.OutputKeys;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerException;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathExpressionException;
import javax.xml.xpath.XPathFactory;

import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.NodeList;
import org.w3c.dom.Node;
import org.xml.sax.SAXException;

import java.io.BufferedWriter;
import java.io.FileWriter;
import java.io.IOException;
import java.io.StringWriter;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.Vector;

import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.io.BufferedInputStream;
import java.io.FileOutputStream;
import java.io.SequenceInputStream;
import java.util.Arrays;
import java.util.Collections;
import java.util.List;
/**
 * Class for providing an XLink resolver.
 * 
 * The XLink resolver handles all cases defined in the standard
 * (e.g., only HTTP, no circular referencing)
 * 
 * @author Markus Waltl <markus.waltl@itec.uni-klu.ac.at>
 */
public class XLinkResolver {
	private static Vector<Document> documentList_ = new Vector<Document>();
	
	
	public void resolveXLinks(String fileToResolve) throws SAXException, IOException, ParserConfigurationException, XPathExpressionException, XLinkException, TransformerException {
		Document localDoc = parseXML(fileToResolve);
		documentList_.add(localDoc); // add start document to the list
		Document newDocument = handleNodeList(localDoc);
			
		// output temporary XML file
		writeXMLFile(newDocument);
	}
	
	private static Document handleNodeList(Document doc) throws XLinkException, XPathExpressionException, SAXException, IOException, ParserConfigurationException {
		NodeList nList = extractXLinkElements(doc);
		if (nList != null) {
			for (int i = 0; i < nList.getLength(); i++) {
				Node nNode = nList.item(i);
				
				if (Definitions.debug_)
					printNode(nNode);
				String link = extractXLinkHref(nNode);
				String resolveToZeroPeriod = new String("urn:mpeg:dash:resolve-to-zero:2013");
				if (link != null && !link.equals(resolveToZeroPeriod)) {
                    Document remoteDoc = parseXML(link);
                    // Earlier the calling xlink node can be directly replaced with the remoteDoc's root element.
                    // But this is no longer possible, because we add a manual root element.
					// Instead we need to extract the child nodes.
                    // As a first step, the first child node should replace the calling parent xlink node.
                    // As the next few steps, the nodes after the first child node should be added after the parent
                    // xlink node. Along with this, we need to check the conformance of each of the child nodes.

					// Extract the nodes from remote element.
					Element remoteRootElementWithManualRoot = remoteDoc.getDocumentElement();
					NodeList nList1 = remoteRootElementWithManualRoot.getChildNodes();

					//	Certain parameters declared and initialized.
                    boolean firstNode = true;
                    Node referenceNode = nList1.item(0);
                    Node replacedNode = nList1.item(0);
                    Node refParent = nNode.getParentNode();

					// Iterate over all the nodes.
					for (int k = 0; k < nList1.getLength(); k++){
						Node nodeCurr = nList1.item(k);
						// Whitespaces are detected as node sometimes. This "if" condition will take of that.
						if ( nodeCurr.getNodeType() == Node.ELEMENT_NODE ) {
							// Create new document element with the current node.
                            DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
                            factory.setNamespaceAware(true);
                            DocumentBuilder builder = factory.newDocumentBuilder();
                            Document newRemoteDoc = builder.newDocument();
                            Node tmpNode = newRemoteDoc.importNode(nodeCurr, true);
                            newRemoteDoc.appendChild(tmpNode);

							Element remoteRootElement = newRemoteDoc.getDocumentElement();

							if (remoteRootElement.getAttribute("xlink:actuate").equals("onLoad")) {
								throw new XLinkException("URI references to remote element entities that contain another" +
                                        " @xlink:href attribute with xlink:actuate set to onLoad are treated as invalid circular references.");
							}

                            if (!nNode.getNodeName().equals(remoteRootElement.getNodeName())) {
                                throw new XLinkException("Referenced Document must contain same element type as referencing element!\n\n"
                                        + "Referencing element: " + nNode.getNodeName() + "\nReferenced element: " + newRemoteDoc.getDocumentElement().getNodeName());
                            }

                            // check if we already used this document (direct or indirect circular reference)
                            for (int j = 0; j < documentList_.size(); j++) {
                                if (documentList_.get(j).isEqualNode(newRemoteDoc))
                                    throw new XLinkException("Circular referencing detected!");
                            }
                            documentList_.add(newRemoteDoc); // add the parsed document to the list for detecting circular referencing
                            //printElement(remoteDoc.getDocumentElement());
                            newRemoteDoc = handleNodeList(newRemoteDoc);
                            // replace XLink included document
							remoteRootElement = newRemoteDoc.getDocumentElement(); // The remote remoteRootElement has to be update to ensure that it takes the current values.
                            Node parent = nNode.getParentNode();
                            Node importedNode = doc.importNode(remoteRootElement, true);
                            // re-add and reset original attributes
                            NamedNodeMap nodeAttributes = nNode.getAttributes();
                            if (nodeAttributes != null) {
                                for (int j = 0; j < nodeAttributes.getLength(); j++) {
                                    Node attribute = nodeAttributes.item(j);
                                    String name = attribute.getNodeName();
                                    String value = attribute.getNodeValue();
                                    String namespace = attribute.getNamespaceURI();
                                    // do not re-add the XLink attributes and empty attributes
                                    // also reset to original values of attributes in referenced document
                                    // having different values than in original document
                                    if (!Definitions.XLINK_NAMESPACE.equals(namespace) && !value.equals(""))
                                        ((Element)importedNode).setAttribute(name, value);
                                }
                            }
                            // replace node
                            if (firstNode == true) { // Replace if it is the first node.
                                parent.replaceChild(importedNode, nNode);
                                firstNode = false;
                            }
                            else { // The following line adds the newly created node after the reference node.
                                referenceNode.getParentNode().insertBefore(importedNode, referenceNode.getNextSibling());
                            }
							referenceNode = importedNode; // Reinitialize the reference node for every iteration.
						}
					}
				}
			}
		}
		return doc;
	}

	// needed to write or can be used directly?
	private static void writeXMLFile(Document doc) throws TransformerException, IOException {
		// set up a transformer
		TransformerFactory transfac = TransformerFactory.newInstance();
		Transformer trans = transfac.newTransformer();
		trans.setOutputProperty(OutputKeys.INDENT, "yes");
		trans.setOutputProperty(OutputKeys.OMIT_XML_DECLARATION, "no");

		// create string from xml tree
		StringWriter sw = new StringWriter();
		StreamResult result = new StreamResult(sw);
		DOMSource source = new DOMSource(doc);
		trans.transform(source, result);
		String xmlString = sw.toString();

		// print xml
		if (Definitions.debug_)
			System.out.println(xmlString);

		// Write newly generated XML to temp folder
		FileWriter fstream = new FileWriter(Definitions.tmpOutputFile_);
		BufferedWriter out = new BufferedWriter(fstream);
		out.write(xmlString);
		out.close();
	}
	
	private static Document parseXML(String xmlURI) throws SAXException, IOException, ParserConfigurationException {
		DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
		dbFactory.setNamespaceAware(true); // resolve namespaces
		
		DocumentBuilder dBuilder = dbFactory.newDocumentBuilder();
		Document doc;
		try {
			URL myURL = new URL(xmlURI);
			// Earlier problem was that when a multi period remote content is fed for parsing, it does not work because
			// it is not a valid xml file. The reason is that there can be only one root element. We fix this by
            // manually adding a root element around the multi period content. Note that if it is a normal .xml file,
            // then it is being parsed as it was before (the if condition takes care of this).
            String type = (xmlURI.lastIndexOf('?') == -1) ? xmlURI.substring(xmlURI.lastIndexOf('.') + 1) : xmlURI.substring(xmlURI.lastIndexOf('.') + 1, xmlURI.lastIndexOf('?'));
            if(!type.equals("mpd")) {
				// Add manual <root> element around the xml file.
                List<InputStream> streams = Arrays.asList(
                        new ByteArrayInputStream("<root>".getBytes()),
                        new BufferedInputStream(myURL.openStream()),
                        new ByteArrayInputStream("</root>".getBytes())
                );
                ByteArrayOutputStream out = new ByteArrayOutputStream();
                InputStream in = new SequenceInputStream(Collections.enumeration(streams));
                doc = dBuilder.parse(in);
            }
            else {
                doc = dBuilder.parse(myURL.openStream()); // The usual parse for a normal .mpd file.
            }
        }
		catch ( MalformedURLException e ) {
			doc = dBuilder.parse(xmlURI);
		}
		doc.getDocumentElement().normalize();
		return doc;
	}
	
	private static NodeList extractXLinkElements(Document doc) throws XPathExpressionException {
		XPath xpath = XPathFactory.newInstance().newXPath();
		return (NodeList)xpath.evaluate("//*[@*[namespace-uri()='" + Definitions.XLINK_NAMESPACE + "']]", doc, XPathConstants.NODESET);
	}
	
	/**
	 * Extracts the XLink href attribute and checks the protocol
	 * @param node Node to extract the link from
	 * @return String with the link
	 * @throws XLinkException thrown if an unsupported protocol is used
	 */
	private static String extractXLinkHref(Node node) throws XLinkException {
		String href = null;
		NamedNodeMap nnm = node.getAttributes();
		boolean found = false;
		for (int att = 0; att < nnm.getLength() && !found; att++) {
			Node n = nnm.item(att);
			// check for href attribute and if the correct namespace is used
			if (Definitions.XLINK_NAMESPACE.equals(n.getNamespaceURI()) && Definitions.HREF.equals(n.getLocalName())) {
				href = n.getNodeValue();
				found = true;
			}			
		}
		
		// we only allow http links
		if (href != null && (!href.startsWith(Definitions.PROTOCOL) && !href.startsWith(Definitions.SECURE_PROTOCOL) && !href.startsWith(Definitions.RESOLVE_TO_ZERO)))
			throw new XLinkException("Only HTTP links or urn:mpeg:dash:resolve-to-zero:2013 are allowed!");

		return href;
	}
	
	private static void printNode(Node node) {
		System.out.println(node.getNodeName());
		NamedNodeMap nnm = node.getAttributes();
		for (int att = 0; att < nnm.getLength(); att++) {
			Node n = nnm.item(att);
			System.out.println(n.getNamespaceURI() + " -- " + n.getLocalName() + " -- " + n.getNodeValue());
		}
		System.out.println("");
	}
}