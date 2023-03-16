# FAQ

##### 1. What is Conformance Software Tool?

Conformance Software Tool is an open source software tool that checks if:

* the provided MPEG-DASH MPD manifest and
* optionally, the media content pointed to within the MPD

conform to DASH-related media specifications.

##### 2. What does the tool check?

By default, conformance software tool validates the provided MPD against:

* ISO/IEC 23009-1 DASH MPD and Segment Formats as well as
* The profiles provided in the @profiles attribute in the given MPD that the tool supports

only at the MPD level which includes

* Xlink Resolving
* DASH Schema Validation
* MPD-Level Checks

Optionally, you can include additional tests as below:

* Segment Validation
* DASH-IF
* DVB
* HbbTV
* CMAF
* CTA WAVE

##### 3. How can I use the tool for testing?

Testing can be done in maximum 3 easy steps:

* Provide MPD in the input field
    * URL or
    * Upload / Drag&Drop file
* Select additional tests (optional)
* Click RUN button

For a visual usage guideline, you can check the tour guide on the navigation menu.

##### 4. What does the "Include additional tests" mean?

This part provides additional tests that can be included by the user if desired. The options are:

* _Segment Validation_: By default, only MPD validation (Xlink resolving, DASH schema validation, MPD-level checks) is performed against the existing profiles in @profiles attribute and the enforced profiles, if any. By checking this option, the media in each Representation pointed to by the MPD will also be validated according to provided profiles and ISO/IEC 14496-12 for base media file format.
* _DASH-IF_: Enables validation checks against https://dashif.org/guidelines/dash264 profile according to DASH-IF IOP guidelines v4.3.
* _LL DASH-IF_: Enables validation checks against http://www.dashif.org/guidelines/low-latency-live-v5 profile according to Low Latency DASH-IF IOP guidelines v4.3 CR.
* _DVB_: Enables validation checks against urn:dvb:dash:profile:dvb-dash:2014 profile according to ETSI TS 103 285 v1.1.1.
* _HbbTV_: Enables validation checks against urn:hbbtv:dash:profile:isoff-live:2012 profile according to HbbTV 1.5 and ETSI TS 102 796 v1.4.1.
* _CMAF_: Enables validation checks against CMAF specification for segmented media according to ISO/IEC 23000-19.
* _CTA WAVE_: Enables validation checks against WAVE content specification according to CTA-5001.

__NOTE__: Please also check the answer under the question, __What does the tool check?__ for understanding the default operation of the tool.

##### 5. Are there any example MPDs that I can use for testing?

DASH-IF provides example MPDs also called as test vectors covering various test cases and features. They are hosted and accessible at https://testassets.dashif.org/.

##### 6. How can I contribute?

The source code is hosted at <a href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance" target="_blank">https://github.com/Dash-Industry-Forum/DASH-IF-Conformance</a>. To contribute to the software:

* Fork the repository(-ies) that will be changed in the scope of the contribution
* Make changes to files locally
* Test the changes against various MPDs
* Commit and push the changes to respective fork(s)
* Create pull request
* Wait for reviewer feedback

__NOTE__: Depending on the feedback on the pull request is reviewed by the maintainers of the software, the above steps might need to be repeated by the contributers.