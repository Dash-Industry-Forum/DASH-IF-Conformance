<!DOCTYPE html>
<!-- This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 -->
 
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta charset="utf-8" />
        <meta name="description" content="DASH Conformance">
        <meta name="keywords" content="DASH,DASH Conformance,DASH Validator">
        <meta name="author" content="Nomor Research GmbH">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css" />
        <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
        <link rel="STYLESHEET" type="text/css" href="tree/dhtmlxTree/codebase/dhtmlxtree.css">
        <script type="text/javascript" src="tree/dhtmlxTree/codebase/dhtmlxcommon.js"></script>
        <script type="text/javascript"  src="tree/dhtmlxTree/codebase/dhtmlxtree.js"></script>
        <script type="text/javascript" src="tree/dhtmlxTree/codebase/ext/dhtmlxtree_json.js"></script>
	<style>
            html,body
            {
                background-color: #fff;
                background-size: 100% 1.2em;
                
            }
            
            /* The container */
            .container 
            {     
                position: relative;
                padding-left: 35px;
                margin-bottom: 12px;
                cursor: pointer;
                font-size: 20px;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                user-select: none;
                font-family: Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif; 
            }

            /* Hide the browser's default radio button */
            .container input 
            {
                position: absolute;
                opacity: 0;
                cursor: pointer;
            }

            /* Create a custom radio button */
            .checkmark 
            {
                position: absolute;
                top: 0;
                left: 0;
                height: 25px;
                width: 25px;
                background-color: #eee;
                border-radius: 50%;
            }

            /* On mouse-over, add a grey background color */
            .container:hover input ~ .checkmark 
            {
                background-color: #ccc;
            }

            /* When the radio button is checked, add a blue background */
            .container input:checked ~ .checkmark 
            {
                background-color: #2196F3;
            }

            /* Create the indicator (the dot/circle - hidden when not checked) */
            .checkmark:after 
            {
                content: "";
                position: absolute;
                display: none;
            }

            /* Show the indicator (dot/circle) when checked */
            .container input:checked ~ .checkmark:after 
            {
                display: block;
            }

            /* Style the indicator (dot/circle) */
            .container .checkmark:after 
            {
                    top: 9px;
                    left: 9px;
                    width: 8px;
                    height: 8px;
                    border-radius: 50%;
                    background: white;
            }

            /*for the button*/
            .button 
            {
        background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #007bff), color-stop(1, #007bff) );
        filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#bddbfa', endColorstr='#80b5ea');
        background-color:#bddbfa;
      
        border: 1px solid #007bff;
        color: #fff;
        font-size:1vw;
        text-align:center;
        height:30%;
        line-height:90%;
        position:absolute;
        margin-left:100%;
        margin-top: -0.7%;
      
        border-radius: 5px;
        width:7vw;
        cursor: pointer;

            }

            .button:hover 
            {
            background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #0069d9), color-stop(1, #0069d9) );
            background:-moz-linear-gradient( center top, #0062cc 5%, #0062cc 100% );
            filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#80b5ea', endColorstr='#bddbfa');
            background-color:#80b5ea;
            }

            .button:active 
            {
              transform: translateY(4px);
            }
            .buttonone 
            {
        background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #007bff), color-stop(1, #007bff) );
        filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#bddbfa', endColorstr='#80b5ea');
        background-color:#bddbfa;
        
        border: 1px solid #007bff;
        color: #fff;
        font-size:1vw;
        text-align:center;
        height:30%;
        line-height:90%;
        position:absolute;
        margin-left:0%;
        margin-top: -0.7%;
      
        border-radius: 5px;
       width: 7vw;
       cursor: pointer;

            }

            .buttonone:hover 
            {
            background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #0069d9), color-stop(1, #0069d9) );
            background:-moz-linear-gradient( center top, #0062cc 5%, #0062cc 100% );
            filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#80b5ea', endColorstr='#bddbfa');
            background-color:#80b5ea;
            }

            .buttonone:active 
            {
            transform: translateY(4px);
            }
            .buttontwo 
            {
        background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #007bff), color-stop(1, #007bff) );
        filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#bddbfa', endColorstr='#80b5ea');
        background-color:#bddbfa;
      
        border: 1px solid #007bff;
        color: #fff;
        height:30%;
        font-size:1vw;
        text-align:center;
        line-height:90%;
        position:absolute;
        margin-left:50%;
        margin-top: -0.7%;
        
        border-radius: 5px;
         width: 7vw; 
         cursor: pointer;

            }

            .buttontwo:hover 
            {
             background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #0069d9), color-stop(1, #0069d9) );
             background:-moz-linear-gradient( center top, #0062cc 5%, #0062cc 100% );
             filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#80b5ea', endColorstr='#bddbfa');
             background-color:#80b5ea;
            }

            .buttontwo:active 
            {
            transform: translateY(4px);
            }
            /*.button:disabled {
                    background-color: #3e7e41;
                    box-shadow: 0 5px #666;

                }*/

            /*for the forms*/
            .input
            {
                position:absolute;
                left: 100%;
                width: 6.8vw;
                font-size: 0.8vw; 

            }

            input[type=text]:enabled 
            { 
                border-radius: 8px;
            }

            input[type=text]:disabled 
            {
                border-radius: 8px;
            }

            .content
            {
                height: 50%;
                width: 50%;
                position: absolute;
                left:25%;
                top: 25%;
                buttom:25%
                
            }
            
            .title
            {      
                font-size: 1.5vw;
                font-family: Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif;
                font-weight:bold;
                font-style:normal;
            }
            
            .center
            {
                position: absolute;
                height: 50%;
                width: 30%;
                top:15%;
                buttom:35%; 
                left: 35%;
            }
            .writingone
            {
              font-size: 1.2vw;
              font-family: Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif;
              display: inline-block;
            }
             .writingtwo
            {
              font-size: 1.2vw; 
              font-family:Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif ;
              display: inline-block;

            }
             .writingthree
            {
              font-size: 0.8vw; 
              font-family:Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif; 
              display: inline-block;

            }
             .writingfour
            {
              font-size: 0.8vw; 
              font-family:Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif; 
              display: inline-block;

            }
        </style>
    </head>
    <body>   
        <div class="content">
            <div class="center">
            <label class="title">Choose a parameter to estimate:</label>
            <br><br>
            <label class="container">
                <input type="radio"   id="MinBufferTime"  value="MinBufferTime" name="radio" required> <div class="writingone">MinBufferTime</div>
                <span class="checkmark"></span>
            </label>
            <input type="text" class="input" id="field1" name="field1"> <div class="writingthree">(sec)</div>
            <br><br>

            <label class="container"> <div class="writingtwo">Bandwidth</div>
                <input type="radio" name="radio" checked="true" id ="Bandwidth" value ="Bandwidth"> 
                <span class="checkmark"></span> 
            </label>
            <input class="input" type="text" id="field2" name="field2"><div class="writingfour">(bit/s)</div> 
            <br><br>
            <button class="buttonone" id="BTNONE" onclick="btnoneFunction()">Manually edit values</button>
            <button class="buttontwo" id="BTNTWO" onclick="btntwoFunction()">Reset to MPD values</button>
            <button class="button" id="BTN" onclick="btnFunction()">Estimate</button>
            </div>
        </div>  
        
        <?php
            $locate = $_REQUEST['location'];
        ?>
        
        <script>
            var defBW, defMBT;
            var Sample_data;
            he = document.body.scrollHeight;
            wi = document.body.scrollWidth;
            $(document).ready(function () 
            {
                
                  function loadXMLDoc(dname)
                {
                    if (window.XMLHttpRequest)
                    {
                        xhttp=new XMLHttpRequest();
                    }
                    else
                    {
                        xhttp=new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    xhttp.open("GET",dname,false);
                    xhttp.send("");
                    return xhttp.responseXML;
                }
                
                var location = "<?php echo $locate ?>";
                Sample_data = loadXMLDoc(location);
                    if (Sample_data !== null)
                    {
                        defBW = parseFloat(Sample_data.getElementsByTagName("MPDInfo").item(0).getAttribute("bandwidth"));
                        defMBT = parseFloat(Sample_data.getElementsByTagName("MPDInfo").item(0).getAttribute("minBufferTime"));
                    }
               
                document.getElementById("field1").value = defMBT;
                document.getElementById("field2").value = defBW;
               
            }); 
         
                function btnFunction(){
                MBT = document.getElementById("field1").value; 
                BW = document.getElementById("field2").value;        
                ProcessSampleData(Sample_data, MBT, BW);
                
                if(checkerone == 0 || checkerone == " " || checkertwo == 0 || checkertwo == " " || isNaN(varone) || isNaN(vartwo) || varone != checkerone || vartwo != checkertwo ){
                placeholderactivator(false);
                cursoradjust("BTN", "auto");
                } else{
                placeholderactivator(true);
                cursoradjust("BTN", "none");
                turntogrey("BTN");              
                changebuttoncolor("BTNONE");
                changebuttoncolor("BTNTWO");
                }
                };
            
                placeholderactivator(true);
                function btnoneFunction(){
                placeholderactivator(false)
                $('#field1, #field2').focus(function() {$( this ).css( "display", "inline" );});
                placeholdercolor();
                turntogrey("BTNONE");
                cursoradjust("BTN", "auto");
                changebuttoncolor("BTN");
                changebuttoncolor("BTNTWO");
                };
                
                function btntwoFunction(){
                document.getElementById("field1").value = defMBT;
                document.getElementById("field2").value = defBW; 
                placeholderactivator(true);
                placeholdercolor();
                turntogrey("BTNTWO");
                changebuttoncolor("BTNONE");
                changebuttoncolor("BTN");
                cursoradjust("BTN", "auto");
               }
                
                function placeholderactivator(activate){
                $('#field1, #field2').attr('disabled',activate);
                }
                
                function changebuttoncolor(buttontype){
                
                document.getElementById(buttontype).style.background = "-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #007bff), color-stop(1, #007bff) )";
                document.getElementById(buttontype).style.filter="progid:DXImageTransform.Microsoft.gradient(startColorstr='#bddbfa', endColorstr='#80b5ea'";
                document.getElementById(buttontype).style.backgroundColor = "#bddbfa";
                document.getElementById(buttontype).style.border = "1px solid #007bff"; 
                document.getElementById(buttontype).style.color = "#fff";
              
                document.getElementById(buttontype).onmouseover=function(){
                document.getElementById(buttontype).style.background = "-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #0069d9), color-stop(1, #0069d9) )";
                document.getElementById(buttontype).style.background = "-moz-linear-gradient( center top, #0062cc 5%, #0062cc 100% )";
                document.getElementById(buttontype).style.filter = "progid:DXImageTransform.Microsoft.gradient(startColorstr='#80b5ea', endColorstr='#bddbfa')";
                document.getElementById(buttontype).style.backgroundColor = "#80b5ea";
                }
                
                document.getElementById(buttontype).onmouseout=function(){
                document.getElementById(buttontype).style.background = "-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #007bff), color-stop(1, #007bff) )";
                document.getElementById(buttontype).style.filter="progid:DXImageTransform.Microsoft.gradient(startColorstr='#bddbfa', endColorstr='#80b5ea'";
                document.getElementById(buttontype).style.backgroundColor = "#bddbfa";
                document.getElementById(buttontype).style.border = "1px solid #007bff"; 
                document.getElementById(buttontype).style.color = "#fff";
                }
                }
                
               function placeholdercolor(){
                document.getElementById("field1").style.backgroundColor= "#F8F8FF";
                document.getElementById("field2").style.backgroundColor= "#F8F8FF";
                }
                
               function turntogrey(buttontype){
               
                document.getElementById(buttontype).style.background= "#BEBEBE";
                document.getElementById(buttontype).style.border = "1px solid #BEBEBE"; 

                document.getElementById(buttontype).onmouseout=function(){
            
                document.getElementById(buttontype).style.background = "#BEBEBE";
                document.getElementById(buttontype).style.border = "1px solid #BEBEBE"; 

                }
                document.getElementById(buttontype).onmouseover=function(){

                document.getElementById(buttontype).style.background = "#BEBEBE"; 
                document.getElementById(buttontype).style.border = "1px solid #BEBEBE"; 

                }
                }
                
                function cursoradjust(buttontype, cursorstyle){
                    document.getElementById(buttontype).style.pointerEvents = cursorstyle;
                }
                
                
            var checkerone;
            var checkertwo;
            function ProcessSampleData(Sample_data, MBT, BW)
            {
                 checkerone = document.getElementById("field1").value;
                 checkertwo = document.getElementById("field2").value;
                 varone = parseFloat(checkerone);
                 vartwo = parseFloat(checkertwo);
                     
                
                if (Sample_data !== null)
                {
                    if(checkerone == 0 || checkerone == " " || checkertwo == 0 || checkertwo == " " || isNaN(varone) || isNaN(vartwo) || varone != checkerone || vartwo != checkertwo ) {
                alert("Please enter numbers other than zero for both boxes!");
            
            
                }  else{
                    var bandwidth, minBufferTime, timescale, initSize, announcedSAP, dataSizeToRemove, duration; // to be read from the xml file

                    bandwidth = BW;
                    minBufferTime = MBT;
                    initSize = parseFloat(Sample_data.getElementsByTagName("Representation").item(0).getAttribute("initSize"));
                    timescale = parseFloat(Sample_data.getElementsByTagName("Representation").item(0).getAttribute("timescale"));

                    var trackNonConforming = false;
                    var currentBandwidth = bandwidth;
                    var increment_factor = 2;
                    var decrement_factor = 0.75;
                    var upper_bound = 0;
                    var lower_bound = 0;
                    var done = false;
                    var mod_val = 0; 
                    var bufferFullness, lastOffset, timeNowInTicks, totalDataRemoved, totalBitsAdded; //get moofCount from counting the number of instances with annauncedSAP attribute
                    var radio = $("input[name='radio']:checked").val();

                    do
                    {  
                        bufferFullness = currentBandwidth * minBufferTime; //bits (not Bytes)
                        lastOffset = initSize;
                        timeNowInTicks = minBufferTime * timescale;
                        totalDataRemoved = 0;
                        totalBitsAdded = 0;
                        trackNonConforming = false;

                        for(var moof_index = 0; moof_index < Sample_data.getElementsByTagName("moof").length; moof_index ++)
                        {
                            var moof = Sample_data.getElementsByTagName("moof").item(moof_index);
                            announcedSAP = moof.getAttribute("a"); 
                            if (announcedSAP && bufferFullness > currentBandwidth * minBufferTime) //There is no buffer overflow for DASH buffer model, only case is on a SAP, as DASH spec. defines the requiremnt that the playback could be from any SAP and at the SAP, the buffer fullness is bandwidth*minBufferTime
                                {
                                    totalDataRemoved += ((bufferFullness - currentBandwidth * minBufferTime) / 8.0); //The clipped data, for debug information
                                    bufferFullness = currentBandwidth * minBufferTime;
                                }

                            for(var traf_index = 0; traf_index < moof.getElementsByTagName("traf").length; traf_index ++)
                            {    
                                var traf = moof.getElementsByTagName("traf").item(traf_index);
                                for(var trun_index = 0; trun_index < traf.getElementsByTagName("trun").length; trun_index ++)
                                {    
                                    var trun = traf.getElementsByTagName("trun").item(trun_index);
                                    for(var sample_index = 0; sample_index < trun.getElementsByTagName("s").length; sample_index ++)  
                                    {
                                        var sample = trun.getElementsByTagName("s").item(sample_index);
                                        dataSizeToRemove = parseFloat(sample.getAttribute("z"));
                                        duration = parseFloat(sample.getAttribute("d"));
                                        totalDataRemoved += dataSizeToRemove;
                                        if ((dataSizeToRemove * 8) > bufferFullness) //Bufferfullness is in bits
                                        {
                                            if (!trackNonConforming) 
                                            {
                                                //if (currentBandwidth == bandwidth)
                                                    //console.log("Buffer underrun conformance error: first (and only one reported here) for sample ", sample_index + 1," of run ", trun_index + 1, " of track fragment ", traf_index + 1, " of fragment ", moof_index + 1, ", bandwidth: ", currentBandwidth);
                                                trackNonConforming = true;
                                                break;
                                            }                     
                                        }

                                        bufferFullness -= (dataSizeToRemove * 8);
                                        bufferFullness += (currentBandwidth * ( duration / timescale));
                                        totalBitsAdded += (currentBandwidth * (duration / timescale));
                                        timeNowInTicks += duration;
                                    }
                                    if (trackNonConforming) break;
                                }
                                if (trackNonConforming) break;
                            }
                            if (trackNonConforming) break;
                        }

                        if(radio == "Bandwidth")
                        {    
                            if (trackNonConforming) // not passing with the current value
                            {
                                if(upper_bound == 0) // if it has yet to pass for the first time then increment
                                    currentBandwidth *= increment_factor;
                                else // it has already passed once and the upper bound was assigned so it goes here for first time when we find the lower bound
                                {    
                                    if(lower_bound == 0) // first time it doesn't pass after it has passed at least once so we can assign lower bound now
                                    {    
                                        lower_bound = currentBandwidth;
                                        mod_val = upper_bound - lower_bound;
                                    }
                                    mod_val /= 2; 
                                    currentBandwidth += mod_val; // to be eleborated more
                                }
                            }
                            else // passing with the current value
                            {
                                if((lower_bound != 0) && (mod_val <= 50))
                                    done = true;

                                if (currentBandwidth == bandwidth)
                                    done = true;
                                else
                                {
                                    if(upper_bound == 0) // assign only the first time the condition is fulfilled
                                        upper_bound = currentBandwidth;
                                    if(mod_val == 0)
                                        currentBandwidth *= decrement_factor;
                                    else if(done != true)
                                    {    
                                        mod_val /= 2; 
                                        currentBandwidth -= mod_val;
                                    }
                                }
                            }
                            document.getElementById("field2").style.backgroundColor= "#98FB98";
                        }
                        else if(radio == "MinBufferTime")
                        {
                            if (trackNonConforming) // not passing with the current value
                            {
                                if(upper_bound == 0) // if it has yet to pass for the first time then increment
                                    minBufferTime *= increment_factor;
                                else // it has already passed once and the upper bound was assigned so it goes here for first time when we find the lower bound
                                {    
                                    if(lower_bound == 0) // first time it doesn't pass after it has passed at least once so we can assign lower bound now
                                    {    
                                        lower_bound = minBufferTime;
                                        mod_val = upper_bound - lower_bound;
                                    }
                                    mod_val /= 2; 
                                    minBufferTime += mod_val; // to be eleborated more
                                }
                            }
                            else // passing with the current value
                            {
                                if((lower_bound != 0) && (mod_val <= 0.1))
                                    done = true;

                                if (minBufferTime == MBT)
                                    done = true;
                                else
                                {
                                    if(upper_bound == 0) // assign only the first time the condition is fulfilled
                                        upper_bound = minBufferTime;
                                    if(mod_val == 0)
                                        minBufferTime *= decrement_factor;
                                    else if(done != true)
                                    {    
                                        mod_val /= 2; 
                                        minBufferTime -= mod_val;
                                    }
                                }
                            }
                            document.getElementById("field1").style.backgroundColor= "#98FB98";
                        }    
                    }
                    while (!done); 

                    if(currentBandwidth != bandwidth)
                        document.getElementById("field2").value = Math.ceil(currentBandwidth);                               
                    else if(minBufferTime != MBT)
                        document.getElementById("field1").value = minBufferTime.toFixed(4);
                    else
                        alert("No Buffer underrun conformance error detected!")
                    
                }                        
            }
    }    
        </script>
    </body>
</html>


