<x-layout>
  <x-outdated />
      <div class="container">

      <h3>Terms and Privacy Policy</h3>

<h4>1. Data Processing</h4>

<p>
The DASH-IF Conformance Tool processes media files and manifests uploaded by users to validate conformance with industry standards. Files are temporarily stored on our servers for the duration of the validation process.
</p>


<h4>2. Storage of Preferences</h4>

<p>
This tool stores your consent preference in your browser's local storage. No personal information is collected or stored on our servers beyond the files you upload for validation.
</p>

<h4>3. Data Retention</h4>


<p>Uploaded files are automatically deleted after processing, typically within 24 hours. We maintain anonymized logs for statistical purposes and to improve our service.</p>


<h4>4. User Rights</h4>

<p>Under GDPR, you have the right to:</p>

<ul>

<li>Access any personal data we hold about you
<li>Request deletion of your data
<li>Object to processing of your data
<li>Withdraw consent at any time
</ul>
# About

DASH-IF Conformance Tool is used to validate DASH content according to DASH-related media specifications. It aims to give information about the validity of the content against one or more developed media standards. Consequently, it reports on (un)expected behavior that can be observed in provided media services that are aimed to be working in alignment to these standards.

The development of the tool started in 2012 funded by DASH-IF and has been continuously updated with newer versions of the already supported standards and/or new standards as required. Currently, the tool is aligned with a large set of specifications, namely MPEG-DASH,ISO BMFF, DASH-IF IOP, CMAF, DVB-DASH, HbbTV and CTA WAVE. It also integrates file format header level parsing of media codecs, including AVC, HEVC, AAC,HE-AAC, HE-AACv2, AC-3, AC-4, E-AC-3, WebVTT and TTML.

For each corresponding specification, the scope of the validation covers:

* _Media PresentationDescription (MPD)_ validation where the MPD is checked if it is a well-formed XML file, appropriate according to DASH schema and MPD-level signaling is done correctly,
* _Segment validation_ where the media content pointed to by the MPD is validated at container level,
* _Cross validation_ of the MPD-level elements and attributes as well as of the mediacontent(s) signaled at the same hierarchy.

The DASH-IF Conformance Tool is an open source software available on <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance" target="_blank">Github</a>. A live demo of the tool is also provided <a href="https://conformance.dashif.org/" target="_blank">here</a>.[phencys@phe[phencys@phencys-xps static (laravel-beta)]$
<h4>5. Self-Hosted Option</h4>

<p>
If you prefer not to upload your content to our servers, a self-hosted version of this tool is available. You can install and run the validation tool locally on your own infrastructure.</p>

<ul>
<li>Download and installation instructions are available at: [https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Installation--guide](https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Installation--guide)
<li>The self-hosted version provides the same validation capabilities without sending data to external servers
<li>This option is recommended for sensitive or confidential content
</ul>

<h4>6. Contact</h4>

<p>For any questions regarding this privacy policy, please contact: <a href="mailto:contact@dashif.org">
contact@dashif.org</a></p>

<h4>7. Changes to This Policy</h4>

<p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new privacy policy on this page.</p>

<p><b>Note</b>: Last updated: April 2025</p>
</div>
</x-layout>
