# DASH-IF Conformance Software

This repository provides the source code for MPEG-DASH/DASH-IF Conformance Software/Validator. It has been extended
according to further standards, such as CMAF, DVB-DASH, HbbTV, and CTA WAVE.

## News

To stay up to date about latest changes, new release candidates and new releases please join our Google Group and our
Slack channel:

* Google group: https://groups.google.com/g/joint-conformance-software-project-jccp
* Slack invitation: https://join.slack.com/t/dashif/shared_invite/zt-egme869x-JH~UPUuLoKJB26fw7wj3Gg . Join the #jccp
  channel to stay up to date.

## Hosted versions

DASH-IF provides hosted versions of the Conformance Tools. Currently this is done as follows:

|URL| Version                             |Branch|
|---|-------------------------------------|---|
|https://conformance.dashif.org/| 1.0.0                               |https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/releases/tag/v1.0.0|
|https://beta.conformance.dashif.org/| latest released version, e.g. 2.0.0 |master|
|https://staging.conformance.dashif.org/| latest development version          |development|

As part of the JCCP project a major refactoring was done with version 2.0.0. To
offer users the possibility to fall back to version 1.0.0 of the tools three installations are maintained at this point (see table
above). At some point https://conformance.dashif.org/ will be replaced with the content that is currently available
at https://beta.conformance.dashif.org/.

## Installation

For the complete installation including dependencies etc, please refer
to [Installation guide]( https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Installation--guide).

To clone the IntegratedConformance, use the command,

`git clone https://github.com/Dash-Industry-Forum/DASH-IF-Conformance`

## Development

We very much appreciate all your code contributions to the project. To be compliant with our development guidelines
please checkout
the [development principles](https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Development-principles) and
also the [release procedure](https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Release-Procedure).

### API documentation

A hosted version of the Doxygen API documentation can be found at https://dashif.org/DASH-IF-Conformance/

## Usage Guide

Information on how to use the conformance software, please refer to
our [Usage Guide](https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Usage-guide) document.

## CLI

A preliminary CLI has been added to the project, which can be run from the `Utils` directory, see
the `Utils/Process_cli.php` script. At this point in time it requires the `Conformance-Frontend/temp/` directory to be
created manually, for storing temporary files.

## Detailed Information

For the framework of the conformance software and how the general conformance testing process works, please refer to
the [DASH-IF-Conformance document](https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/blob/master/Doc/Conformance%20Software.pdf)
.

## Issue Reporting

Issues can be reported on this repository. 
