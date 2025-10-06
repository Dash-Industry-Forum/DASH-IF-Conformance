<x-layout>
      <div class="alert alert-warning">
        <h4>Outdated</h4>
        <p>
           This content has not yet been updated to reflect the current version
        </p>
      </div>
  <div class="container">
  <h3>FAQ</h3>

  <h4>What is Conformance Software Tool?</h4>

  <p>The Conformance Software Tool is an open source software tool that checks whether the following elements conform to DASH-related media specifications</p>

  <ul>
    <li>the provided MPEG-DASH MPD manifest</li>
    <li>optionally, the media content pointed to within the MPD</li>
  </ul>

  <h4>What does the tool check?</h4>
    <p>
    By default, conformance software tool validates the provided MPD against:
    </p>

    <ul>
<li> ISO/IEC 23009-1 DASH MPD and Segment Formats as well as
<li> The profiles provided in the @profiles attribute in the given MPD that the tool supports
</ul>

<p>only at the MPD level which includes</p>

<ul>
<li> Xlink Resolving
<li> DASH Schema Validation
<li> MPD-Level Checks
</ul>

<p>
Optionally, you can include additional tests as below:
</p>

<ul>
<li>Segment Validation
<li> DASH-IF
<li>DVB
<li>HbbTV
<li>CMAF
<li>CTA WAVE
</ul>

<h4>How can I use the tool for testing?</h4>

<p>
Testing can be done in maximum 3 easy steps:
</p>

<ul>
  <li>Provide MPD in the input field
  <ul>
   <li>URL
   <li>Upload / Drag&Drop file
  </ul>
  </li>
  <li>Select additional tests (optional)
  <li>Click RUN button
  </ul>

  <p>
For a visual usage guideline, you can check the tour guide on the navigation menu.
</p>


<h4>What does the "Include additional tests" mean?</h4>

<p>

This part provides additional tests that can be included by the user if desired. The options are:
</p>

<ul>
<li><em>Segment Validation</em>: By default, only MPD validation (Xlink resolving, DASH schema validation, MPD-level checks) is performed against the existing profiles in @profiles attribute and the enforced profiles, if any. By checking this option, the media in each Representation pointed to by the MPD will also be validated according to provided profiles and ISO/IEC 14496-12 for base media file format.
</li>
<li>
<em>DASH-IF</em>: Enables validation checks against https://dashif.org/guidelines/dash264 profile according to DASH-IF IOP guidelines v4.3.
</li>
<li>
<em>LL DASH-IF</em>: Enables validation checks against http://www.dashif.org/guidelines/low-latency-live-v5 profile according to Low Latency DASH-IF IOP guidelines v4.3 CR.
</li>
<li>
<em>DVB</em>: Enables validation checks against urn:dvb:dash:profile:dvb-dash:2014 profile according to ETSI TS 103 285 v1.1.1.
</li>
<li
<em>HbbTV</em>: Enables validation checks against urn:hbbtv:dash:profile:isoff-live:2012 profile according to HbbTV 1.5 and ETSI TS 102 796 v1.4.1.
</li>
<li>
<em>CMAF</em>: Enables validation checks against CMAF specification for segmented media according to ISO/IEC 23000-19.
</li>
<li>
<em>CTA WAVE</em>: Enables validation checks against WAVE content specification according to CTA-5001.
</li>
</ul>


<p><b>NOTE</b>: Please also check the answer under the question, <b>What does the tool check?</b> for understanding the default operation of the tool.</p>

<h4>5. Are there any example MPDs that I can use for testing?</h4>

<p>DASH-IF provides example MPDs also called as test vectors covering various test cases and features. They are hosted and accessible at <a href="https://testassets.dashif.org/" target="_blank">testasset.dashif.org</a></p>

<h4>How can I contribute?</h4>

<p>The source code is hosted at <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance" target="_blank">https://github.com/Dash-Industry-Forum/DASH-IF-Conformance</a>. To contribute to the software:</p>

<ul>
<li> Fork the repository(-ies) that will be changed in the scope of the contribution
<li> Make changes to files locally
<li> Test the changes against various MPDs
<li> Commit and push the changes to respective fork(s)
<li> Create pull request
<li> Wait for reviewer feedback
</ul>

<p><b>NOTE</b>: Depending on the feedback on the pull request is reviewed by the maintainers of the software, the above steps might need to be repeated by the contributers.</p>
</div>
</x-layout>
