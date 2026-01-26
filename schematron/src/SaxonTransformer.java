import java.io.File;
import java.io.StringWriter;

import javax.xml.transform.stream.StreamSource;

import net.sf.saxon.s9api.Processor;
import net.sf.saxon.s9api.SaxonApiException;
import net.sf.saxon.s9api.XsltExecutable;
import net.sf.saxon.s9api.XsltTransformer;

// Based on: http://fahdshariff.blogspot.com/2017/10/using-xslt-20-with-java-and-saxon.html

public class SaxonTransformer {

    private final static String OPENTAG = "<svrl:failed-assert";
    private final static String CLOSETAG = "</svrl:failed-assert>";

    private final Processor processor;
    private final XsltExecutable xsltExec;

    public SaxonTransformer(final String xslFileName) throws Exception {
        processor = new Processor(false);
        xsltExec = processor
            .newXsltCompiler()
            .compile(new StreamSource(new File(xslFileName)));
    }

    public boolean transform(final String xml) throws Exception {

        final XsltTransformer transformer = xsltExec.load();
        transformer.setSource(new StreamSource(new File(xml)));

        final StringWriter writer = new StringWriter();
        transformer.setDestination(processor.newSerializer(writer));
        transformer.transform();

        String xmlString = writer.toString();
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
