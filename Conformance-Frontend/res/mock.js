let Mock = (function () {
  let testResults = [
    {
      parse_segments: true,
      source: "https://dash.akamaized.net/akamai/bbb_30fps/bbb_30fps.mpd",
      entries: {
        "MPEG-DASH Common": {
          verdict: "FAIL",
          MPD: {
            verdict: "FAIL",
            info: [
              "Schematron output: 0XLink resolving successful\n\n\nMPD validation successful - DASH is valid!\n\n\n<svrl:failed-assert test=\"if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentAlignment = 'true')) then false() else true()\"\n                       location=\"/*:MPD[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:Period[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:AdaptationSet[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]\">\n      <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentAlignment' as true</svrl:text>\n   </svrl:failed-assert>\n<svrl:failed-assert test=\"if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '1' or @subsegmentStartsWithSAP = '2')) then false() else true()\"\n                       location=\"/*:MPD[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:Period[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:AdaptationSet[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]\">\n      <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 1 or 2</svrl:text>\n   </svrl:failed-assert>\n<svrl:failed-assert test=\"if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentAlignment = 'true')) then false() else true()\"\n                       location=\"/*:MPD[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:Period[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:AdaptationSet[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][2]\">\n      <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentAlignment' as true</svrl:text>\n   </svrl:failed-assert>\n<svrl:failed-assert test=\"if((contains(@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012') or (not(@profiles) and contains(ancestor::dash:MPD/@profiles, 'urn:hbbtv:dash:profile:isoff-live:2012'))) and (@subsegmentStartsWithSAP = '1' or @subsegmentStartsWithSAP = '2')) then false() else true()\"\n                       location=\"/*:MPD[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:Period[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][1]/*:AdaptationSet[namespace-uri()='urn:mpeg:dash:schema:mpd:2011'][2]\">\n      <svrl:text>HbbTV-DVB DASH Validation Requirements check violated for HbbTV: Section 'MPD' - The MPD contains an attribute that is not part of the HbbTV profile', i.e., found 'subsegmentStartsWithSAP' as 1 or 2</svrl:text>\n   </svrl:failed-assert>\nSchematron validation not successful - DASH is not valid!\n\n\n",
            ],
            test: [
              {
                spec: "MPEG-DASH",
                section: "Commmon",
                test: "Schematron Validation",
                messages: [
                  "XLink resolving succesful",
                  "MPD validation succesful",
                  "Schematron validation failed",
                ],
                state: "FAIL",
              },
            ],
          },
        },
        Stats: {
          LastWritten: "2022-10-11 01:45:32",
        },
        verdict: "FAIL",
      },
      verdict: "FAIL",
      enabled_modules: [
        {
          name: "MPEG-DASH Common",
        },
        {
          name: "DASH-IF IOP Conformance",
        },
      ],
    },
  ];
  let instance = {
    testResults,
  };
  return instance;
})();
