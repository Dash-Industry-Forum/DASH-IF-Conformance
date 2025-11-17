<x-layout>
  <x-outdated />
  <div class="container">
  <h4>About</h4>

  <p>
    DASH-IF Conformance Tool is used to validate DASH content according to DASH-related media specifications. It aims to give information about the validity of the content against one or more developed media standards. Consequently, it reports on (un)expected behavior that can be observed in provided media services that are aimed to be working in alignment to these standards.
  </p>

  <p>
The development of the tool started in 2012 funded by DASH-IF and has been continuously updated with newer versions of the already supported standards and/or new standards as required. Currently, the tool is aligned with a large set of specifications, namely MPEG-DASH,ISO BMFF, DASH-IF IOP, CMAF, DVB-DASH, HbbTV and CTA WAVE. It also integrates file format header level parsing of media codecs, including AVC, HEVC, AAC,HE-AAC, HE-AACv2, AC-3, AC-4, E-AC-3, WebVTT and TTML.
  </p>


  <p>
    For each corresponding specification, the scope of the validation covers:
  </p>


  <ul>
    <li>
      <em>Media PresentationDescription (MPD)</em> validation where the MPD is checked if it is a well-formed XML file, appropriate according to DASH schema and MPD-level signaling is done correctly
    </li>
    <li>
      <em>Segment validation</em> where the media content pointed to by the MPD is validated at container level,
    </li>
    <li>
      <em>Cross validation</em> of the MPD-level elements and attributes as well as of the mediacontent(s) signaled at the same hierarchy.
    </li>
  </ul>

  <p>
The DASH-IF Conformance Tool is an open source software available on <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance" target="_blank">Github</a>. A live demo of the tool is also provided <a href="https://conformance.dashif.org/" target="_blank">here</a>.
</p>
  </div>
</x-layout>
