# Integrated Conformance Software

The existing conformance software [Conformance-Software](https://github.com/Dash-Industry-Forum/Conformance-Software) has been extended according to new standards, such as CMAF, DVB-DASH, and HbbTV over time. However, these extensions have been spread over different parts of the entire software; and this makes testing, managing and further extending processes quite difficult. Therefore, a code refactoring which would clearly separate the different modules, functionalities and extensions from each other has been initiated.

This repository contains common modules (Utils, webfe) and submodules (DASH, CMAF, HbbTV, ISOSegmentValidator). Each submodule is a repository on its own and all the submodules need the common modules.

### Installation
To clone the IntegratedConformance with all the submodules, use the command, 

git clone --recurse-submodules https://github.com/Dash-Industry-Forum/IntegratedConformance

For the complete installation including dependencies etc, please refer [Installation guide]( HbbTV_DVB/Documentation/Installation_Guide.pdf).
