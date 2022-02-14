# DASH-IF Conformance Software

This repository provides the source code for MPEG-DASH/DASH-IF Conformance Software/Validator. It has been extended according to further standards, such as CMAF, DVB-DASH, HbbTV, and CTA WAVE.

This repository contains the common directory (Utils) and submodules:
* [ISOSegmentValidator](https://github.com/Dash-Industry-Forum/ISOSegmentValidator)
* [Conformance-Frontend-HLS](https://github.com/Dash-Industry-Forum/Conformance-Frontend-HLS)

Each submodule is a repository on its own with its respective functionalities and all the submodules need the common directory.

### Installation

For the complete installation including dependencies etc, please refer to [Installation guide]( https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/wiki/Installation--guide).

To clone the IntegratedConformance with all the submodules, use the command,

`git clone --recurse-submodules https://github.com/Dash-Industry-Forum/DASH-IF-Conformance`

### Usage Guide

Information on how to use the conformance software, please refer to our [Usage Guide](https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/blob/master/Doc/Conformance%20Software%20Usage%20Guide.pdf) document.

### Detailed Information

For the framework of the conformance software and how the general conformance testing process works, please refer to the [DASH-IF-Conformance document](https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/blob/master/Doc/Conformance%20Software.pdf).

### Issue Reporting

If the issue is known to correspond to a specific submodule functionality, please open the issues in the respective submodule's Github issue page. Otherwise, the issues can be reported on this repository. Please beware that in the latter case, the issue can be moved to the corresponding submodule by the repository admin. Access to submodules' Github issue pages are provided below:

* [ISOSegmentValidator issues](https://github.com/Dash-Industry-Forum/ISOSegmentValidator/issues)
* [Conformance-Frontend-HLS issues](https://github.com/Dash-Industry-Forum/Conformance-Frontend-HLS/issues)
