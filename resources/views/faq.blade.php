<x-layout>
  <div class="container">
  <h4 class="text-center">Frequently Asked Questions</h4>

  <h5>What is the 'DASH-IF Conformance Tool'?</h5>

  <p>The DASH-IF Conformance Tool is an open source applications that checks whether a given DASH Manifest corresponds to the statements of a media specification.</p>

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
        <li>The application will now download the given MPD, as well as at most 3 segment urls per representation.</li>
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

  <h5>How can I contribute?</h5>
    <p>The project is developed as an open source project on <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance" target="_blank">Github</a>. Development guidelines can be found on the <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Development-principles" target="_blank">Wiki</a>.

  <div class="mb-5">&nbsp;</div>

</div>
</x-layout>
