<?php
/* This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function CTAWAVE_Handle($request){
    $return_val = NULL;
    switch($request){
        case 'Tracks':
            CTAFlags();
            break;
        case 'AdaptationSet':
            $return_val = CTASelectionSet();
            $return_val = CTAPresentation();
            break;
        case 'Period' :
             $return_val= CTABaselineSpliceChecks();
        default:
            break;
    }
    
    return $return_val;
}