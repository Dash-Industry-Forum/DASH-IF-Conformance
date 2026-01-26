<x-layout>
  <div class="container">
  <h4 class="text-center">Frequently Asked Questions</h4>

  <h5>What is the 'DASH-IF Conformance Tool'?</h5>

  <p>The DASH-IF Conformance Tool is an open source application that checks whether a given DASH Manifest corresponds to the statements of a media specification.</p>

  <p>Also see the <a href="/about">About</a> page for more information</p>


  <h5>What is included in the 'Global Module'?</h5>
    <p>
      The global module contains checks that will always have to be performed, regardless of the selected specification. These checks include, but are not limited to:
    </p>

    <ul>
      <li>MPEG-DASH XSD validation</li>
      <li>XLink resolution</li>
      <li>Schematron Validation</li>
    </ul>

  <h5>How to use the application?</h4>

  <ol>
    <li>
      Provide a URL to an MPD.
      <ul>
        <li>The previously available 'upload' and 'text input' variants will be re-introduced in a future version.</li>
      </ul>
    </li>
    <li>
      Click 'Process'.
      <ul>
        <li>The application will now download the given MPD, as well as at most 3 segment URLs per representation.</li>
        <li>A progress bar is displayed while downloading the segments to indicate the progress</li>
      </ul>
    </li>
    <li>
      Select the specifications you want to validate.
      <ul>
        <li>Specifications can be enabled/disabled as desired, but depent checks will fail unless their corresponding specification is enabled as well.</li>
        <li>The 'Segments' section of the specifications list will become available after the segments have been downloaded.</li>
      </ul>
    </li>
  </ol>

  <h5>Are there any example MPDs that I can use for testing?</h5>

   <p>
     DASH-IF provides a set of test vectors that can be used as example MPDs. These are hosted as a separate project on <a href="https://testassets.dashif.org/" target="_blank">testasset.dashif.org</a>
   </p>

  <h5>I used to be able to do &lt;x&gt;, but this option is not available anymore</h5>

  <p>
    During the core rewrite of the application for the v3.x series, we have disabled some of the previously available features, in favor of a more robust base set.
    Many of these items are scheduled for re-implementation in the <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/issues" target="_blank">Github issues</a> section.
  </p>


  <h5>How can I contribute?</h5>
    <p>
        The project is developed as an open source project on <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance" target="_blank">Github</a>. It's been funded mainly by DASH-IF until 2022, and then by ATSC, CTA (WAVE), DVB, and HbbTV - with the help of many individual contributors. Contact us if you are interested in sponsoring the project.
    </p>

    <p>
      If there is a feature we missed, or you have more information on any of our open items, we welcome feedback in the <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/issues" target="_blank">issues</a>.
    </p>

    <p>
      If you want to contribute to the project directly, our development guidelines can be found on the <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Development-principles" target="_blank">Wiki</a>.
    </p>

  <div class="mb-5">&nbsp;</div>

</div>
</x-layout>
