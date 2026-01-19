<x-layout>
  <div class="container">
    <h4 class="text-center mb-4">About</h4>

    <p>
      The DASH-IF Conformance Tool can be used to validate DASH content according to DASH-related media specifications. It aims to give information about the validity of the content against one or more developed media standards. Consequently, it reports on (un)expected behavior that can be observed in provided media services that are aimed to be working in alignment to these standards.
    </p>

    <p>
  The development of the tool started in 2012, funded by DASH-IF and has since been updated with newer versions of the already supported standards and/or new standards as required. Currently, the tool is aligned with a large set of specifications, and also integrates file format header level parsing of several media codecs.
    </p>


    <p>
      Three categories of validation are covered, depending on the specification:
    </p>


    <ul>
      <li><em>MPD (Media Presentation Description) validation</em>: Validates well-formed XML files against the MPD specification, as well as checks various requirements and assumptions.
      </li>
      <li>
        <em>Segment validation</em>: Validates the corresponding media segments as refered to by the MPD.
      </li>
      <li>
        <em>Cross validation</em>: Validates the data present in the MPD against the data provided by analysing the Segments.
      </li>
    </ul>

    <p>
      The DASH-IF Conformance Tool is an open source software available on <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance" target="_blank">Github</a>. A live demo of the tool is also provided <a href="https://conformance.dashif.org/" target="_blank">here</a>.
    </p>

    <div class="mb-5">&nbsp;</div>
  </div>
</x-layout>
