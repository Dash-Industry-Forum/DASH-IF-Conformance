# Integrated Conformance Software

The existing conformance software [Conformance-Software](https://github.com/Dash-Industry-Forum/Conformance-Software) has been extended according to new standards, such as CMAF, DVB-DASH, and HbbTV over time. However, these extensions have been spread over different parts of the entire software; and this makes testing, managing and further extending processes quite difficult. Therefore, a code refactoring which would clearly separate the different modules, functionalities and extensions from each other has been initiated.

This repository contains common modules (Utils) and submodules (DASH, CMAF, HbbTV, ISOSegmentValidator, Frontend). Each submodule is a repository on its own and all the submodules need the common modules.

### Installation

For the complete installation including dependencies etc, please refer [Installation guide]( https://github.com/Dash-Industry-Forum/Conformance-Software/blob/master/Documentation/HbbTV_DVB/Installation_Guide.pdf).

To clone the IntegratedConformance with all the submodules, use the command, 

git clone --recurse-submodules https://github.com/Dash-Industry-Forum/IntegratedConformance


