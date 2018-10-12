<!DOCTYPE html>
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
              display: inline-block;
              padding: 10px 20px;
              font-size: 18px;
              cursor: pointer;
              text-align: center;
              text-decoration: none;
              outline: none;
              color: #fff;
              background-color: #4CAF50;
              border: none;
              border-radius: 15px;
              box-shadow: 0 9px #999;
            }

            .button:hover 
            {
                background-color: #3e8e41;
            }

            .button:active 
            {
              background-color: #3e8e41;
              box-shadow: 0 5px #666;
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
                left: 230px;
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
                height: 550px;
                width:550px;
                position: absolute;
                left: 35%;
                top:25%;
            }
            
            .title
            {                           
                font-family: Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif;
                font-size:24px;
                font-weight:bold;
                font-style:normal;
            }
            
            .center
            {
                position: relative;
                top:33%;
                left: 13%;
            }

        </style>
    </head>
    <body>   
        <div class="content">
            <div class="center">
            <label class="title">Choose a parameter to estimate:</label>
            <br><br>
            <label class="container">
                <input type="radio"   id="MinBufferTime"  value="MinBufferTime" name="radio" required>MinBufferTime
                <span class="checkmark"></span>
            </label>
            <input type="text" class="input" id="field1" name="field1">(sec)
            <br><br>

            <label class="container">Bandwidth
                <input type="radio" name="radio" checked="true" id ="Bandwidth" value ="Bandwidth"> 
                <span class="checkmark"></span>
            </label>
            <input class="input" type="text" id="field2" name="field2">(bit/s)
            <br><br>
            <button class="button" id="BTN" >Estimate</button>
            </div>
        </div>  
        
        <?php
            $locate = $_REQUEST['location'];
        ?>
        
        <script>
            $(document).ready(function () 
            {
                function checkradiobox()
                {
                    var radio = $("input[name='radio']:checked").val();
                    $('#field1, #field2').attr('disabled',true);
                    if(radio == "MinBufferTime")
                    {
                     $('#field1').attr('disabled',false);
                     $("#field1").focus(function() {$( this ).css( "display", "inline" );});
                    }
                    else if(radio == "Bandwidth")
                    {
                        $('#field2').attr('disabled',false);
                        $("#field2").focus(function() {$( this ).css( "display", "inline" );});
                    }
                }
                $("#MinBufferTime, #Bandwidth").change(function () {checkradiobox();});
                checkradiobox();
                
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
                
                var Sample_data = loadXMLDoc(location);
                    if (Sample_data !== null)
                    {
                        var BW, MBT;

                        BW = parseFloat(Sample_data.getElementsByTagName("MPDInfo").item(0).getAttribute("bandwidth"));
                        MBT = parseFloat(Sample_data.getElementsByTagName("MPDInfo").item(0).getAttribute("minBufferTime"));
                    }
                document.getElementById("field1").placeholder = MBT;
                document.getElementById("field2").placeholder = BW;
                 
                
                
                function ProcessSampleData(Sample_data, MBT, BW)
                {                                        
                   
                    
                    document.getElementById("field1").placeholder = MBT;
                    document.getElementById("field2").placeholder = BW;
                    if (Sample_data !== null)
                    {
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
                            }    
                        }
                        while (!done); 

                        if(currentBandwidth != bandwidth)
                            document.getElementById("field2").placeholder = Math.ceil(currentBandwidth);                               
                        else if(minBufferTime != MBT)
                            document.getElementById("field1").placeholder = minBufferTime.toFixed(4);
                        else
                            alert("No Buffer underrun conformance error detected!")
                    }                        
                }
                document.getElementById("BTN").onclick = function() {ProcessSampleData(Sample_data, MBT, BW)};
            }); 
            
        </script>
    </body>
</html>
