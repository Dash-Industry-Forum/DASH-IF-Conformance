/*******************************************************************************************************************************
Script with main functions for loading, parsing MPD, dispatching segment checks and verifying

Copyright (c) 2013, Nomor Research
All rights reserved.
********************************************************************************************************************************/

//Main MPD structure with all relevant information
var MPD = {xmlHttpMPD: createXMLHttpRequestObject(), xmlData: null, FT: null, Periods: new Array(), MUP: null, segmentsDispatch: null, mpdDispatch: null, totalSegmentCount: 0, updatedSegments: 0, mpdEvents: new Array(), mpdEventCount: 5, RTTs: new Array(), numRTTs: 10, 
clockSkew: new Array(), numClockSkew: 10, numSuccessfulChecksSAS: 0, numSuccessfulChecksSAE: 0, UTCTiming:new Array()};

//Past or already available segments, cost a significant processing overhead at the startup, this needs to be sorted out. Right now, we keep it to a minimum
var maxPastSegmentsPerIteration = 2;

var veryLargeDuration = 86400000; //Lets stick to 10000 days for now, Infinity not working as expected;

//For MPDs with no MUP
var maxMUP = 3600;
var UTCElementArray=[];
var serverTimeOffsetStatus=false;
var serverTimeOffset=0;

var RequestCounter = 0;
function createXMLHttpRequestObject(){ 
  var xmlHttp; // xmlHttp will store the reference to the XMLHttpRequest object
  try{         // try to instantiate the native XMLHttpRequest object
    xmlHttp = new XMLHttpRequest(); // create an XMLHttpRequest object
  }
  catch(e) {
    try     // assume IE6 or older
    {
      xmlHttp = new ActiveXObject("Microsoft.XMLHttp");
    }
    catch(e) { }
  }
  if (!xmlHttp)       // return the created object or display an error message
    alert("Error creating the XMLHttpRequest object.");
  else 
    return xmlHttp;
}

/*******************************************************************************************************************************
Entry and re-entry (for MPD updates) point
********************************************************************************************************************************/

function process()
{ 
  if (MPD.xmlHttpMPD)     // continue only if xmlHttp isn't void
  {
    try          // try to connect to the server
    {	
	  var mpd_url=document.getElementById("mpdbox").value;

      var now = new Date();
      if(document.getElementById("cscorrect").checked)
      {
        now = new Date(now - getCSOffset());
      }
	  MPD.xmlHttpMPD.open("GET", mpd_url += (mpd_url.match(/\?/) == null ? "?" : "&") + now.getTime(), false);  // initiate server request, trying to bypass cache using tip
	   //MPD.xmlHttpMPD.open("GET", mpd_url, false);                                                                                                         // from 
	                                                                                                            // https://developer.mozilla.org/es/docs/XMLHttpRequest/Usar_XMLHttpRequest#Bypassing_the_cache,
	                                                                                                            // same technique used for segment request
      MPD.xmlHttpMPD.onreadystatechange = mpdReceptionEventHandler;
      MPD.xmlHttpMPD.send(null);
    }
    catch (e)      // display an error in case of failure
    {
      alert("Can't connect to server when request the MPD:\n" + e.toString());
    }
  }
}

/*******************************************************************************************************************************
Generate and print the important information about the last mpdEventCount times
********************************************************************************************************************************/

function mpdStatusUpdate(mpd)
{

    if(mpd.mpdEvents.length >= mpd.mpdEventCount)
        mpd.mpdEvents.shift();

    var publishTime = getPT(mpd.xmlData);
    var publishTimeString = "Publish time not available";

    if(publishTime)
        publishTimeString = publishTime.toUTCString();
        
    mpd.mpdEvents.push("Fetch: " + mpd.FT.toUTCString() + ", Publish: " + publishTimeString + ", " + mpd.updatedSegments + " new segments.");
    
    var mpdOP =document.getElementById('MPDOutput');
    mpdOP.innerHTML="";

    for(var index = mpd.mpdEvents.length - 1; index >= 0 ; index --)
        mpdOP.innerHTML+=(mpd.mpdEvents[index] + "<br/>");						 
    
}

/*******************************************************************************************************************************
Main matching function: as a head request is fulfilled, find out which segment request got replied. Compare and calculate times,
print the outcome. Also calculate RTT and clock skew
********************************************************************************************************************************/

function segmentEventHandler() {
	
    var statusReported = false;
    var totalSASRequestsDispatched = 0;
    var totalSASRequestsProcessed = 0;
    var totalSAERequestsDispatched = 0;
    var totalSAERequestsProcessed = 0;
    var intrinsicTimeNow = new Date();  //Without any clock-skew corrections
    var timeNow = intrinsicTimeNow;
    var dispatchTime;
    var intrinsicDispatchTime;  //Actual machine time when the request was sent out
    var responseTime;
    var printString = "";
    
    if(this.getAllResponseHeaders().indexOf("Date") != -1)
        responseTime = new Date(this.getResponseHeader("Date"));
    else
        responseTime = null;
    
    if(document.getElementById("cscorrect").checked)
    {
         timeNow = new Date(timeNow - getCSOffset());
    }
    
    var RTT;

    var segment = MPD.Periods[this.ref.period].AdaptationSets[this.ref.as].Representations[this.ref.rep].Segments[this.ref.seg];
    var requestType = this.ref.type;
    
    if(requestType == "SAS")
    {
        printString += 'Segment Start check: <a href=" ' + segment.url + '">' + segment.url + '</a>, SAST: ' + segment.SAS.time.toUTCString() + "";
        if(segment.SAS.deltaTime < 0)
            printString += ", Prior available Segment";

        intrinsicDispatchTime = segment.SAS.intrinsicDispatchTime;
        dispatchTime = new Date(intrinsicDispatchTime.getTime() - segment.SAS.dispatchTimeOffset);
            
        MPD.Periods[this.ref.period].AdaptationSets[this.ref.as].Representations[this.ref.rep].processedSASRequests ++;
    }
    else
    {
        printString += 'Segment End check: <a href=" ' + segment.url + '">' + segment.url + '</a>, SAET: ' + segment.SAE.time.toUTCString() + "";

        if(0)//responseTime)
            printString += ", RT: " + responseTime.toUTCString() + ", Diff: " + 
            (segment.SAE.time - responseTime)/1000 + ", checked after: " + (segment.SAS.time - responseTime)/1000;

        intrinsicDispatchTime = segment.SAE.intrinsicDispatchTime;
        dispatchTime = new Date(intrinsicDispatchTime.getTime() - segment.SAE.dispatchTimeOffset);
        
        MPD.Periods[this.ref.period].AdaptationSets[this.ref.as].Representations[this.ref.rep].processedSAERequests ++;
    }
    
    RTT = intrinsicTimeNow - intrinsicDispatchTime;
    if(MPD.RTTs.length >= MPD.numRTTs)
        MPD.RTTs.shift();
    
    //if(segment.SAS.deltaTime > 0)
    MPD.RTTs.push(RTT);
    
    statusReported = true;


    if (this.status === 200)
    {
        printString += ", " + '<span style="color:blue">'+"Status: "+this.statusText+'</span>'+"<br/>";	
        if(requestType == "SAS")
            MPD.numSuccessfulChecksSAS++;
        if(requestType == "SAE")
            MPD.numSuccessfulChecksSAE++;
	if(RequestCounter <= 10 && document.getElementById('access').innerText != "Fail" ) 
	{
	  document.getElementById('access').innerHTML = '<span style= "font-size:40px; color:blue">'+"Processing"+'</span>';
	}
	if ( RequestCounter > 10 && document.getElementById('access').innerText != "Fail")
	{
	  document.getElementById('access').innerHTML = '<span style= "font-size:40px; color:green">'+"Pass"+'</span>';
	}
    }
    else
    {
        printString += ", " + '<span style="color:red">'+"Status: "+this.statusText+'</span>';

	document.getElementById('access').innerHTML = '<span style= "font-size:40px; color:red">'+"Fail"+'</span>';
        if(responseTime && (requestType == "SAE") && responseTime > segment.SAE.time)
        {
            printString += '<span style="color:red">'+", <b>Clock skew: response time: " + responseTime.toUTCString() + " msec. </b> </span>";
        }
            
        if(responseTime && (requestType == "SAS") && responseTime < segment.SAS.time)
            printString += '<span style="color:red">'+", <b> Clock skew: response time: " + responseTime.toUTCString() + " msec. </b> </span>";

        printString += "<br/>";//responseTime.toUTCString() + "<br/>";

    }

    //if(!(this.status === 200))
        printOutput(printString);
        RequestCounter++;


    for(var periodIndex = 0; periodIndex < MPD.Periods.length ; periodIndex++)
    {
        var Period = MPD.Periods[periodIndex];
        
        for(var asIndex = 0; asIndex < Period.AdaptationSets.length ; asIndex++)
        {
            var AdaptationSet = Period.AdaptationSets[asIndex];
            
            for(var repIndex = 0; repIndex < AdaptationSet.Representations.length ; repIndex++)
            {
                var Representation = AdaptationSet.Representations[repIndex];

                totalSASRequestsDispatched += Representation.dispatchedSASRequests;
                totalSASRequestsProcessed += Representation.processedSASRequests;
                totalSAERequestsDispatched += Representation.dispatchedSAERequests;
                totalSAERequestsProcessed += Representation.processedSAERequests;
            }
        }
    }

    var progress = document.getElementById('Progress');
    progress.innerHTML =totalSASRequestsProcessed + "/" + totalSASRequestsDispatched + " Segment Availabilty Start checks processed, " + MPD.numSuccessfulChecksSAS + " successful.<br/>";
    progress.innerHTML+=totalSAERequestsProcessed + "/" + totalSAERequestsDispatched + " Segment Availabilty End checks processed, " + MPD.numSuccessfulChecksSAE + " successful.<br/>";

    progress.innerHTML+="<br/>RTT (mSec): ";
    
    if(MPD.RTTs.length > 0)
        progress.innerHTML += " Mean: " + (MPD.RTTs.average().toFixed(2)) + ", Max: " + (MPD.RTTs.max().toFixed(2));
    else
        progress.innerHTML += "Calculating...";
        
    if(responseTime)
    {
        var clockSkew = ((intrinsicTimeNow.getTime() + intrinsicDispatchTime.getTime())/2) - responseTime.getTime();
        
        if(MPD.clockSkew.length >= MPD.numClockSkew)
            MPD.clockSkew.shift();
        
        MPD.clockSkew.push(clockSkew);

        progress.innerHTML += "<br/> Mean Clock skew (msec): " + getCSOffset().toFixed(2);
    }
    else
        progress.innerHTML += "<br/> Response time not reported, cannot calculate clock skew.";

    if(!statusReported)
        printOutput('<span style="color:red">'+"Segment report match could not be made!! <br/>");
};

/*******************************************************************************************************************************
Clock skew offset with a deadzone of 0.5 seconds, as response precision is 1 sec
********************************************************************************************************************************/

function getCSOffset()
{
    var csOffset = 0;
    
    if(MPD.clockSkew.length > 0)
    {
        csOffset = MPD.clockSkew.average();
        
        if((csOffset > 500) || (csOffset < -500))
        {
            if(csOffset > 500)csOffset-=500;
            if(csOffset < -500)csOffset+=500;            
        }
        else
            csOffset = 0;
    }
    
    return csOffset;
}

/*******************************************************************************************************************************
An MPD got loaded, process the MPD and initiate URL dispatching
********************************************************************************************************************************/

function  mpdReceptionEventHandler(){
        
  if (MPD.xmlHttpMPD.readyState == 4){    // continue if the process is completed
    if (MPD.xmlHttpMPD.status == 200) {       // continue only if HTTP status is "OK"   
      try {

        var now = new Date();
        
        if(document.getElementById("cscorrect").checked)
        {
            now = new Date(now - getCSOffset());
        }
        MPD.FT = now;
        
        response = MPD.xmlHttpMPD.responseXML;          // retrieve the response
                
        // do something with the response
        MPD.xmlData = MPD.xmlHttpMPD.responseXML.documentElement;	
         
        //Removing this if, to ensure MPD is updated after MUP and processed.
        //if(getMUP(MPD.xmlData) < maxMUP)
        //{
            MPD.mpdDispatch = setTimeout(process,getMUP(MPD.xmlData)*1000);
        //}
        if (MPD.xmlHttpMPD.responseText.search("xlink")!==-1)
	{
	    MPD.xmlData = xlink(MPD.xmlData);
	}
		processMPD(MPD.xmlData);

        mpdStatusUpdate(MPD);

        if(MPD.segmentsDispatch != null)
            clearTimeout(MPD.segmentsDispatch);
        
        MPD.segmentsDispatch = null;
        
        dispatchChecks();

        		
      }
      catch(e)
      {
        alert("Error MPD processing: " + e.toString());          // display error message
      }
    } 
    else
    {
      alert("There was a problem retrieving the data (MPD):\n" + MPD.xmlHttpMPD.status);        // display status message
    }
  }
}
/*******************************************************************************************************************************
make a modified MPD if there was a xlink 
********************************************************************************************************************************/

function xlink(MPDxmlData)
{
  	
	
	    var numPeriods = MPDxmlData.getElementsByTagName("Period").length;
	    for(i=0; i<numPeriods; i++){
	      try{
		while (MPDxmlData.getElementsByTagName("Period")[i].getAttribute('xlink:href')){
		  var xlinkrequest = new XMLHttpRequest();
		  xlinkrequest.open("GET", MPDxmlData.getElementsByTagName("Period")[i].getAttribute('xlink:href'), false);
		  xlinkrequest.send(null);
		  var parser = new DOMParser();
		  var xmlHttpPeriod = parser.parseFromString(xlinkrequest.responseText, "text/xml");
		  MPDxmlData.getElementsByTagName("Period")[i].parentNode.replaceChild(xmlHttpPeriod.documentElement, MPDxmlData.getElementsByTagName("Period")[i]);  
		  }
		}
		  catch(e)
		  {
		    var x = MPDxmlData.getElementsByTagName("Period")[i];
		    x.parentNode.removeChild(x);
		    i--;
		    numPeriods--;
		    alert("xlink Period not found(404)");
		    continue;
		  }
		var adaptationSets = MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet");
		var numadaptations = adaptationSets.length;
		for( j=0; j< numadaptations ; j++){
		  try{
		    while (MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j].getAttribute('xlink:href')){
			var xlinkrequest = new XMLHttpRequest();
			xlinkrequest.open("GET", MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j].getAttribute('xlink:href'), false);
			xlinkrequest.send(null);
			var parser = new DOMParser();
			var xmlHttpAdaptation = parser.parseFromString(xlinkrequest.responseText, "text/xml");
			MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j].parentNode.replaceChild(xmlHttpAdaptation.documentElement, MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j]);
		    }
		  }
		  catch(e)
		  {
		      var x = MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j];
		      x.parentNode.removeChild(x);
		      j--;
		      numadaptations--;
		      alert("xlink AdaptationSet not found(404)");
		      continue;
		  }
		    var representationSets = MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j].getElementsByTagName("Representation");
		    var numrepresentations = representationSets.length;
			for (k=0; k< numrepresentations ; k++){
			  try{
			    while(MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j].getElementsByTagName("Representation")[k].getAttribute('xllink:href')){
				var xlinkrequest = new XMLHttpRequest();
				xlinkrequest.open("GET", MPDxmlData.getElementsByTagName("Period")[i].getAttribute('xlink:href'), false);
				xlinkrequest.send(null);
				var parser = new DOMParser();
				var xmlHttpRepresentation = parser.parseFromString(xlinkrequest.responseText, "text/xml");
				MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName("AdaptationSet")[j].getElementsByTagName("Representation")[k].parentNode.replaceChild(xmlHttpRepresentation.documentElement, MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName(AdaptationSet)[j].getElementsByTagName("Representation")[k]); 
			     }
			  }
			  catch(e)
			  {
			     var x = MPDxmlData.getElementsByTagName("Period")[i].getElementsByTagName(AdaptationSet)[j].getElementsByTagName("Representation")[k];
			     x.parentNode.removeChild(x);
			     k--;
			     numrepresentations--;
			     alert("xlink Representation not found(404)");
			     continue;
			  }
			}
		 }
	    }
	    return MPDxmlData;
}  
/*******************************************************************************************************************************
Dispatch URL request of a single time (either SAS or SAE)
********************************************************************************************************************************/

function dispatchRequest(segment,type,xmlHttp)
{
    xmlHttp.onload = segmentEventHandler;

    var intrinsicTimeNow = new Date();
    var urlTime = intrinsicTimeNow;
    
    if(document.getElementById("cscorrect").checked)
        urlTime = new Date(urlTime - getCSOffset());
    
    xmlHttp.open("HEAD",segment.url += (segment.url.match(/\?/) == null ? "?" : "&") + urlTime.getTime(),true);   //Head, not get, we just need to check if segment is available, not get it

    var timeToSend;

    if(type == "SAS")
    {
        //printOutput("SAS dispatch time: " + intrinsicTimeNow.getTime() + ", SAS: " + segment.SAS.time.getTime() + "<br/>");
        segment.SAS.intrinsicDispatchTime = intrinsicTimeNow;
        timeToSend = intrinsicTimeNow - segment.SAS.dispatchTimeOffset;
    }
    else
    {
        segment.SAE.intrinsicDispatchTime = intrinsicTimeNow;
        timeToSend = intrinsicTimeNow - segment.SAE.dispatchTimeOffset;
    }
    
    xmlHttp.setRequestHeader("Date",timeToSend);
        
    xmlHttp.send(null);//if the request method is post, it will not be null
}

/*******************************************************************************************************************************
Dispatch time checks for all of the current MPD
********************************************************************************************************************************/

function dispatchChecks()
{
    var pastSegmentsDispatched = 0;

    for(var periodIndex = 0; periodIndex < MPD.Periods.length ; periodIndex++)
    {
        var Period = MPD.Periods[periodIndex];
        
        for(var asIndex = 0; asIndex < Period.AdaptationSets.length ; asIndex++)
        {
            var AdaptationSet = Period.AdaptationSets[asIndex];
            
            for(var repIndex = 0; repIndex < AdaptationSet.Representations.length ; repIndex++)
            {
                var Representation = AdaptationSet.Representations[repIndex];
                var SSN = Representation.SSN;
                var GSN = Representation.GSN;
                
                var now = new Date();
                
                //alert("To dispatch: " + (GSN - (SSN + Math.max(0,pastSegments-maxPastSegments))));
                
                for(var i = Representation.firstAvailableSsegment; i <= GSN ; i ++)
                {
                  var saeCheckOffset;
                  var csOffset = 0;

                  if(document.getElementById("rtt").disabled)
                    saeCheckOffset = 0;
                  else
                    saeCheckOffset = document.getElementById("rtt").value;

                  now = new Date();
                  
                  if(document.getElementById("cscorrect").checked)
                  {
                    csOffset = getCSOffset();
                    now = new Date(now - csOffset);
                  }
                  
                   try
            	   	{	
                        var currentSegment = Representation.Segments[i];
                        currentSegment.SAS.deltaTime = Math.ceil(currentSegment.SAS.time.getTime()- (now.getTime()+serverTimeOffset));
                        currentSegment.SAE.deltaTime = Math.ceil(currentSegment.SAE.time.getTime() - saeCheckOffset - (now.getTime()+serverTimeOffset));
                        //printOutput("Index: " + i + ", SAS delta: " + currentSegment.SAS.deltaTime + ", SAE delta: " + currentSegment.SAE.deltaTime + "<br/>");

                        if(currentSegment.SAE.deltaTime >= 0)   //Still some time to expiry of segment
                        {
                            if(!currentSegment.SAS.requestDispatched)
                            {
                                if(currentSegment.SAS.deltaTime >= 0)  //Still some time to availabilty of segment
                                {
                                    if(currentSegment.SAS.deltaTime < 2000)
                                    {

                                        currentSegment.SAS.xmlHttp = createXMLHttpRequestObject();
                                        currentSegment.SAS.xmlHttp.ref = {period: periodIndex, as: asIndex, rep: repIndex, seg: i, type: "SAS"};
                                        currentSegment.SAS.timeOutRet=setTimeout(dispatchRequest, currentSegment.SAS.deltaTime,currentSegment,"SAS",currentSegment.SAS.xmlHttp);
                                        currentSegment.SAS.dispatchTimeOffset = csOffset;
                                        currentSegment.SAS.requestDispatched = true;
                                        Representation.dispatchedSASRequests ++;
                                    }
                                }						   						   
                                else if(pastSegmentsDispatched < maxPastSegmentsPerIteration)
                                {	
                                    currentSegment.SAS.xmlHttp = createXMLHttpRequestObject();
                                    currentSegment.SAS.xmlHttp.ref = {period: periodIndex, as: asIndex, rep: repIndex, seg: i, type: "SAS"};
                                    dispatchRequest(currentSegment,"SAS",currentSegment.SAS.xmlHttp);						 
                                    currentSegment.SAS.dispatchTimeOffset = csOffset;
                                    currentSegment.SAS.requestDispatched = true;
                                    Representation.dispatchedSASRequests ++;
                                    pastSegmentsDispatched ++;
                                }
                            }

                            if(currentSegment.SAE.deltaTime < 2000 && !currentSegment.SAE.requestDispatched)
                            {
                                currentSegment.SAE.xmlHttp = createXMLHttpRequestObject();
                                currentSegment.SAE.xmlHttp.ref = {period: periodIndex, as: asIndex, rep: repIndex, seg: i, type: "SAE"};
                                currentSegment.SAE.timeOutRet=setTimeout(dispatchRequest, currentSegment.SAE.deltaTime,currentSegment,"SAE",currentSegment.SAE.xmlHttp);
                                currentSegment.SAE.dispatchTimeOffset = csOffset;
                                currentSegment.SAE.requestDispatched = true;
                                Representation.dispatchedSAERequests ++;
                            }

                        }
            		}
                   catch(e)
                    {
                         printOutput("Exception while dispatching for segment: "+ 1+", Error: "+e.toString()+"<br/>");						 
                    }		              
                }

                //printOutput("Dispatched " + Representation.dispatchedSASRequests + " SAS reqs, " + Representation.dispatchedSAERequests + "  SAE reqs for AS " + asIndex + " Rep "+ repIndex + "<br/>");						 

            }
        }
    }

    MPD.segmentsDispatch = setTimeout(dispatchChecks,1000);
}

/*******************************************************************************************************************************
Merge the high level information with the low level information.
This is done for preventing the loss of possible extra information provided in higher layers.
Lower level information takes precedence for the information provided for same functionalities.
This holds for SegmentTemplate and SegmentTimeline existing on different levels in MPD.
********************************************************************************************************************************/

function hierarchyLevelInfoGathering(higherLevel, lowerLevel)
{
    if(higherLevel.SegmentTemplate != null){
        var highSegmentTemplate = higherLevel.SegmentTemplate;
        var highAttributes = highSegmentTemplate.attributes;
        var highAttributesLen = highAttributes.length;
        var i, j;
        
        if(lowerLevel.SegmentTemplate != null){
            var lowSegmentTemplate = lowerLevel.SegmentTemplate;
            var lowAttribute;
            
            for(i=0; i<highAttributesLen; i++){
                lowAttribute = lowSegmentTemplate.getAttribute(highAttributes[i].nodeName);
                if(!lowAttribute){
                    var newAttribute = document.createAttribute(highAttributes[i].nodeName);
                    newAttribute.value = highAttributes[i].nodeValue;
                    lowerLevel.SegmentTemplate.attributes.setNamedItem(newAttribute);
                }
            }
            
            if(higherLevel.SegmentTemplate.getElementsByTagName("SegmentTimeline").length != 0){
                var highSegmentTimeline = higherLevel.SegmentTemplate.getElementsByTagName("SegmentTimeline")[0].cloneNode(true);
                
                if(lowerLevel.SegmentTemplate.getElementsByTagName("SegmentTimeline").length == 0){
                    lowerLevel.SegmentTemplate.appendChild(highSegmentTimeline);
                }
            }
        }
        else{
            lowerLevel.SegmentTemplate = highSegmentTemplate;
        }
    }
}

/*******************************************************************************************************************************
Generate URLs and availability times for segment timeline based template.
********************************************************************************************************************************/

function processSegmentTimeline(Representation, Period)
{
    var segmentTimeline = Representation.SegmentTemplate.getElementsByTagName("SegmentTimeline")[0];
    var media=Representation.SegmentTemplate.getAttribute("media");
    var S = segmentTimeline.getElementsByTagName("S");
    var o = 0;
    var timescale;
    var mpd = MPD;
    var newSegmentCount = 0;
    
    var k = 0;
    var MST = Array();
    var MD = Array();

    if(Representation.SegmentBase)
    {
        o = Representation.SegmentBase.getAttribute("presentationTimeOffset");
        timescale = Representation.SegmentBase.getAttribute("timescale");
    }

    if(!timescale)
    {
        timescale = Representation.SegmentTemplate.getAttribute("timescale");
        if(!timescale)
            timescale = 1;  //gitay de khotee uthe he aan khalotee
    }

    var r0 = parseFloat(S[0].getAttribute("r"));

    if(!r0 || r0 >= 0)
    {
         for(var s = 0; s < S.length ; s++)
         {
            if(S[s].getAttribute("t"))
                MST[k] = parseFloat(S[s].getAttribute("t")) - o;
            else
            {
                if(s > 0)
                    MST[k] = MST[k-1] + parseFloat(S[s].getAttribute("d"));
                else
                    MST[k] = 0;
            }

            MD[k] = parseFloat(S[s].getAttribute("d"));
            k++;

            var r = parseFloat(S[s].getAttribute("r"));

            if(!r)
                r = 0;

            for(var j = 0; j < r ; j++)
            {
                MST[k] = MST[k-1] + parseFloat(S[s].getAttribute("d"));
                MD[k] = parseFloat(S[s].getAttribute("d"));
                k++;
            }
         }

        var minNewSeg = veryLargeDuration;
        var maxNewSeg = 0;
        var startIndex = Representation.GSN + 1;
        Representation.SSN = startIndex;
        var segNum = startIndex;
        var LSN = 0;  

        for(var stlIndex = 0 ; stlIndex < MST.length ; stlIndex ++)
        {
            var urlObj = getBaseURL(mpd.xmlData)+((media.replace("$RepresentationID$",Representation.xmlData.getAttribute("id"))).replace("$Time$",MST[stlIndex])).replace("$Bandwidth$",Representation.xmlData.getAttribute("bandwidth"));
            var sasObj = {time: new Date((getAST(mpd.xmlData).getTime()+Period.PeriodStart*1000+(MST[stlIndex]/timescale)*1000)), deltaTime: 0, timeOutRet : 0, xmlHttp: null, requestDispatched: false, intrinsicDispatchTime: null, dispatchTimeOffset: null};
            var saeObj = {time: new Date((getAST(mpd.xmlData).getTime()+Period.PeriodStart*1000+(MST[stlIndex]/timescale)*1000)+getTSBD(mpd.xmlData)*1000), deltaTime: 0, timeOutRet : 0, xmlHttp: null, requestDispatched: false, intrinsicDispatchTime: null, dispatchTimeOffset: null};

            if(sasObj.time.getTime() < mpd.FT.getTime())
                LSN = segNum;

            if(segNum == 1 || sasObj.time > Representation.Segments[segNum - 1].SAS.time) //First segment to be populated in list
            {
                Representation.Segments[segNum] = {SAS: sasObj, SAE: saeObj, segNum: segNum, url: urlObj};
                newSegmentCount++;
                if(segNum > maxNewSeg)
                    maxNewSeg = segNum;
                if(segNum < minNewSeg)
                    minNewSeg = segNum;
                
                segNum++;
            }
        }
     
        Representation.GSN = segNum - 1;

        //Try to find firstAvailableSsegment by parsing all SAEs and comparing with TSBD from FT
        Representation.firstAvailableSsegment = Representation.SSN;
        
        for(var segNum = Representation.Segments.length - 1 ; Representation.Segments[segNum] != null ; segNum--)
        {
            if(Representation.Segments[segNum].SAE.time < (mpd.FT.getTime() - getTSBD(mpd.xmlData)*1000))
                break;
            
            Representation.firstAvailableSsegment = segNum;
        }
        

        //printOutput(Representation.firstAvailableSsegment + " - " + Representation.GSN + "; diff: " + (Representation.GSN - Representation.firstAvailableSsegment)+ ", new: " + minNewSeg + " - " + maxNewSeg + "; diff: " + (maxNewSeg-minNewSeg) 
        //+ ", AST: " + getAST(mpd.xmlData) + "<br/>");
        
    }
    else
    {
        alert("r = -1 not supported yet, exiting!");
        exit();
    }
     
    MPD.updatedSegments += newSegmentCount;

}

/*******************************************************************************************************************************
Generate URLs and availability times for number based template.
********************************************************************************************************************************/

function processSegmentTemplate(Representation, Period)
{        
    var init=Representation.SegmentTemplate.getAttribute("initialization");
    var media=Representation.SegmentTemplate.getAttribute("media");

    if(media.indexOf("$Number") == -1)
    {
        alert("SegmentTemplate with no $Number$/$Number%[width]d$ not supported, exiting!");
        exit();
    }
    
    var mpd = MPD;

    //if timescale is not defined, then its default value is 1
    if(Representation.SegmentTemplate.getAttribute("timescale")){
      Representation.duration=parseFloat(Representation.SegmentTemplate.getAttribute("duration"))/parseFloat(Representation.SegmentTemplate.getAttribute("timescale"));
    }else{
      Representation.duration=parseFloat(Representation.SegmentTemplate.getAttribute("duration"));
    }
    
    var d = Representation.duration;

    var SSN = Representation.SegmentTemplate.getAttribute("startNumber");

    if(SSN != null)
        Representation.SSN = parseInt(SSN);
    else
        Representation.SSN = 1;

    // start to check from the start number, but we need to to calculate the GSN 
    var LSN            = Math.floor((mpd.FT.getTime()     +serverTimeOffset                      - ( getAST(mpd.xmlData).getTime() + Period.PeriodStart*1000 ) - d*1000 )/ (d*1000) ) +  Representation.SSN;  
    Representation.GSN = Math.ceil(( mpd.FT.getTime()+serverTimeOffset + getMUP(mpd.xmlData)*1000 - ( getAST(mpd.xmlData).getTime() + Period.PeriodStart*1000 ) - d*1000 )/ (d*1000) ) +  Representation.SSN;
    Representation.firstAvailableSsegment = Math.floor(Math.max(LSN - Math.ceil(getTSBD(mpd.xmlData)+d)/d - 100, Representation.SSN));

    var newSegmentCount = 0;

    var num=Representation.firstAvailableSsegment;//Representation.SSN;
    var minNewSeg = veryLargeDuration;
    var maxNewSeg = 0;
    
    do{
        var urlObj = getBaseURL(mpd.xmlData)+((media.replace("$RepresentationID$",Representation.xmlData.getAttribute("id")))).replace("$Bandwidth$",Representation.xmlData.getAttribute("bandwidth"));
        //Replace Number%[width]d format
        var str_$Number$ = urlObj.match(/\$(.*)\$/).pop();
        var width_regex= /\d+/g;
        var width = str_$Number$.match(width_regex);
        if(width!=null){
            var num_to_replace=new Intl.NumberFormat('en-IN', { minimumIntegerDigits: width ,useGrouping:false}).format(num);
            urlObj=urlObj.replace("$"+str_$Number$+"$",num_to_replace);
        }
        else
            urlObj=urlObj.replace("$Number$",num);
        //
        var sasObj = {time: new Date((getAST(mpd.xmlData).getTime()+Period.PeriodStart*1000+(num-Representation.SSN)*d*1000)), deltaTime: 0, timeOutRet : 0, xmlHttp: null, requestDispatched: false, intrinsicDispatchTime: null, dispatchTimeOffset: null};
        var saeObj = {time: new Date((getAST(mpd.xmlData).getTime()+Period.PeriodStart*1000+(num-Representation.SSN)*d*1000)+getTSBD(mpd.xmlData)*1000), deltaTime: 0, timeOutRet : 0, xmlHttp: null, requestDispatched: false, intrinsicDispatchTime: null, dispatchTimeOffset: null};

        if(Representation.Segments[num] == null)
        {
            Representation.Segments[num] = {SAS: sasObj, SAE: saeObj, segNum: num, url: urlObj};
            newSegmentCount++;
            if(num > maxNewSeg)
                maxNewSeg = num;
            if(num < minNewSeg)
                minNewSeg = num;
            
        }

        num++;

    }while(num <= Representation.GSN);
    
    //printOutput(Representation.firstAvailableSsegment + " - " + Representation.GSN + "; diff: " + (Representation.GSN - Representation.firstAvailableSsegment)+ ", new: " + minNewSeg + " - " + maxNewSeg + "; diff: " + (maxNewSeg-minNewSeg) + "<br/>");

    MPD.updatedSegments += newSegmentCount;

}

/*******************************************************************************************************************************
Process all data pertaining a representation, mainly SAEs and SASs of all the segments for different addressing methods
********************************************************************************************************************************/

function processRepresentation(Representation, AdaptationSet, Period)
{
    var id = Representation.xmlData.getAttribute('id');

    if(Representation.id != "" && Representation.id != id)
    {
        throw("A different Representation with id " + id + " found, previous Representation id was " + Representation.id + ", not handled, returning!");
    }
    
    Representation.id = id;
    
    var SegmentTemplate = getChildByTagName(Representation,"SegmentTemplate");
    
    if( SegmentTemplate != null )
        Representation.SegmentTemplate = SegmentTemplate;
    hierarchyLevelInfoGathering(AdaptationSet, Representation);
    
    var SegmentBase = getChildByTagName(Representation,"SegmentBase");
    
    if( SegmentBase != null )
        Representation.SegmentBase = SegmentBase;

    if(!Representation.SegmentTemplate)
    {
        alert("SegmentTemplate not found, no other addressing mode supported, exiting!");
        exit();
    }

    if(Representation.SegmentTemplate.getElementsByTagName("SegmentTimeline").length == 0)
        processSegmentTemplate(Representation, Period);
    else
        processSegmentTimeline(Representation, Period);
}

function processAdaptationSet(AdaptationSet,Period)
{
    var numRepresentations = AdaptationSet.xmlData.getElementsByTagName("Representation").length;

    var SegmentTemplate = getChildByTagName(AdaptationSet,"SegmentTemplate");
    
    if( SegmentTemplate != null )
        AdaptationSet.SegmentTemplate = SegmentTemplate;
    hierarchyLevelInfoGathering(Period, AdaptationSet);
    
    var SegmentBase = getChildByTagName(AdaptationSet,"SegmentBase");
    
    if( SegmentBase != null )
        AdaptationSet.SegmentBase = SegmentBase;
    
    
    for(var repIndex = 0; repIndex < numRepresentations ; repIndex++)
    {
        //printOutput("Processing rep: " + (repIndex+1));
        if(AdaptationSet.Representations[repIndex] == null)
            AdaptationSet.Representations[repIndex] = {xmlData: null, id: "", SegmentTemplate: null, SegmentBase: null, Segments: new Array(), duration: 0, SSN: 0, GSN: 0, 
                                                    firstAvailableSsegment: 0, dispatchedSASRequests: 0, dispatchedSAERequests: 0, processedSASRequests: 0, processedSAERequests: 0};
        
        //Initializations
        AdaptationSet.Representations[repIndex].xmlData = AdaptationSet.xmlData.getElementsByTagName("Representation")[repIndex];
        AdaptationSet.Representations[repIndex].SegmentTemplate = AdaptationSet.SegmentTemplate;
        AdaptationSet.Representations[repIndex].SegmentBase = AdaptationSet.SegmentBase;

        processRepresentation(AdaptationSet.Representations[repIndex],AdaptationSet,Period);
    }
}


function processPeriod(Period)
{
    Period.SegmentTemplate = getChildByTagName(Period,"SegmentTemplate");
    Period.SegmentBase = getChildByTagName(Period,"SegmentBase");
    
    var id = Period.xmlData.getAttribute('id');

    //Removing this block as we are processing current period and it should be continued if current period is updated.
    /*if(Period.id != "" && Period.id != id)
    {
        throw("A different period with id " + id + " found, previous period id was " + Period.id + ", not handled, returning!");
    }*/
    
    Period.id = id;
    
    var numAdaptationSets = Period.xmlData.getElementsByTagName("AdaptationSet").length;

    if(Period.xmlData.getAttribute("start")){
       Period.PeriodStart = getTiming(Period.xmlData.getAttribute("start"));
    }	

    for(var asIndex = 0; asIndex < numAdaptationSets ; asIndex++)
    {
        //printOutput("Processing AS: " + (asIndex+1));
        if(Period.AdaptationSets[asIndex] == null)
            Period.AdaptationSets[asIndex] = {xmlData: null, SegmentTemplate: null, SegmentBase: null, Representations: new Array()}; 

        //Initializations
        Period.AdaptationSets[asIndex].xmlData = Period.xmlData.getElementsByTagName("AdaptationSet")[asIndex];
        Period.AdaptationSets[asIndex].SegmentTemplate = Period.SegmentTemplate;
        Period.AdaptationSets[asIndex].SegmentBase = Period.SegmentBase;

        processAdaptationSet(Period.AdaptationSets[asIndex],Period);
    }
}

function getNTPServerTimeOffset(URLList)
{
    var xhttpNTP = createXMLHttpRequestObject();
    var clientTimestamp = Date.parse(new Date().toUTCString()); 
    xhttpNTP.onreadystatechange = function() {
    if (this.readyState == 4 ){
        if(this.status == 200) {
      
            //console.log(this.responseText*1000);
            var serverTimestamp=this.responseText*1000;
            var nowTimeStamp  = Date.parse(new Date().toUTCString());
            if(serverTimeOffsetStatus==false){
                serverTimeOffset = (serverTimestamp - ((nowTimeStamp-clientTimestamp)/2)-clientTimestamp);
                serverTimeOffsetStatus=true;
            }
            //console.log(serverTimeOffset);
        }
        else{
            URLList.shift(); // remove already used URL from the list and try next URL.
            if(URLList.length>0)
                getNTPServerTimeOffset(URLList);
        }
    }
  };
  xhttpNTP.open("GET", "resources/script/GetServerTime.php?host="+URLList[0], true);
  xhttpNTP.send();

}

function getServerTimeOffset(URLList, method,schema)
{   
        var clientTimestamp = Date.parse(new Date().toUTCString()); 
        var xmlhttp=createXMLHttpRequestObject();
      
        xmlhttp.onreadystatechange = function(){
            if (xmlhttp.readyState === 4) {
                if (xmlhttp.status === 200) {
                    if(method== "HEAD"){
                        var serverDateStr = xmlhttp.getResponseHeader('Date');
                        var serverTimestamp = Date.parse(new Date(Date.parse(serverDateStr)).toUTCString());
                    }
                    else{
                        var xmlDocServer=xmlhttp.responseXML.documentElement;
                        var message=xmlDocServer.getElementsByTagName("message");
                        var serverDateStr=message[0].getAttribute("generated.on");
                        if(schema=="urn:mpeg:dash:utc:http-ntp:2014")
                            var serverTimestamp=serverDateStr*1000;
                        else
                            var serverTimestamp = Date.parse(new Date(Date.parse(serverDateStr)).toUTCString());
    
                    }                                                
                    //console.log("serverTimestamp "+serverTimestamp);
                    var nowTimeStamp  = Date.parse(new Date().toUTCString());

                    // Here http request and response times are assumed equal.
                    if(serverTimeOffsetStatus==false){
                        serverTimeOffset = (serverTimestamp - ((nowTimeStamp-clientTimestamp)/2)-clientTimestamp);
                        serverTimeOffsetStatus=true;
                        //console.log("serverTimeOffset "+serverTimeOffset);
                    }
                    
                } else {
                    //console.error(xmlhttp.statusText);
                    URLList.shift(); // remove already used URL from the list and try next URL.
                    if(URLList.length>0)
                        getServerTimeOffset(URLList, method);
                     
                }
            }
        };
        if(method=="HEAD")
            xmlhttp.open("HEAD", URLList[0],true);
        else
            xmlhttp.open("GET", URLList[0],true);
        xmlhttp.send(null);
}

function processUTCTiming(MPDxmlData)
{
    var UTCTiming=MPDxmlData.getElementsByTagName("UTCTiming");
    // If number of UTCTiming elements is 0, then MPD.FT is not altered.
    // Otherwise, UTCTiming sync is done here.
    for(var i=0; i<UTCTiming.length; i++)
    {
        if(MPD.UTCTiming[i]==null)
            MPD.UTCTiming[i]={scheme:"", value:""};
        
        MPD.UTCTiming[i].scheme=UTCTiming[i].getAttribute('schemeIdUri');
        MPD.UTCTiming[i].value=UTCTiming[i].getAttribute('value');
    }
    UTCElementArray=MPD.UTCTiming;
    if(UTCElementArray.length>0)
        UTCSchemesEvaluate(UTCElementArray[0]);

}

function UTCSchemesEvaluate(UTCElement){
    var URLList=UTCElement.value.split(" ");// Most of the UTC schemes give white space separated list.
    switch(UTCElement.scheme)
    {
        case "urn:mpeg:dash:utc:ntp:2014" :
            getNTPServerTimeOffset(URLList);
            break;
        case "urn:mpeg:dash:utc:sntp:2014" :
            getNTPServerTimeOffset(URLList);
            break;
        case "urn:mpeg:dash:utc:http-head:2014" : 
            getServerTimeOffset(URLList,"HEAD",UTCElement.scheme);        
            break;
        case "urn:mpeg:dash:utc:http-xsdate:2014" :
            getServerTimeOffset(URLList,"GET",UTCElement.scheme);
            break;
        case "urn:mpeg:dash:utc:http-iso:2014" : 
            getServerTimeOffset(URLList,"GET",UTCElement.scheme);
            break;
        case "urn:mpeg:dash:utc:http-ntp:2014" : 
            getServerTimeOffset(URLList,"GET",UTCElement.scheme);
            break;
        case "urn:mpeg:dash:utc:direct:2014" :
            serverTimeOffset=Date.parse(new Date(URLList[0]))-Date.parse(MPD.FT);
            //console.log("serverTimeoffset"+serverTimeOffset);
            serverTimeOffsetStatus=true;
            break;
    }
    if(serverTimeOffsetStatus==false)
    {
        UTCElementArray.shift(); // When first UTCTiming element did not respond, try the clock in the next element.
        if(UTCElementArray.length>0)
            setTimeout(function(){UTCSchemesEvaluate(UTCElementArray[0]);},200);
    }
                
}

function processMPD(MPDxmlData)
{
    processUTCTiming(MPDxmlData);
    
    var numPeriods = MPDxmlData.getElementsByTagName("Period").length;
    var currentPeriod = 0;
    
    if(numPeriods > 1){
        currentPeriod = determineCurrentPeriod(MPD, periodInformation(MPD));
        //alert("Found " + numPeriods + "periods, current implementation will only handle Period " + currentPeriod + "!");
    }
    
    MPD.updatedSegments = 0;

    MPD.MUP = getMUP(MPD.xmlData);
    
    

    for(var periodIndex = 0; periodIndex < 1/*numPeriods*/; periodIndex++) //Currently only first period
    {
        if(MPD.Periods[periodIndex] == null)
            MPD.Periods[periodIndex] = {xmlData: null, PeriodStart: 0, id: "", SegmentTemplate: null, SegmentBase: null, AdaptationSets: new Array()};

        //Initializations
        MPD.Periods[periodIndex].xmlData = MPDxmlData.getElementsByTagName("Period")[currentPeriod];
        processPeriod(MPD.Periods[periodIndex]);
    }

}

function getChildByTagName(parent,tagName)
{
    var matches = parent.xmlData.getElementsByTagName(tagName);

    for(var index = 0 ; index < matches.length ; index++)
    {
        if(matches[index].parentNode == parent.xmlData)
        {
            //alert("Found match for: " + tagName + ", parent: " + parent.xmlData.tagName);
            return matches[index];
        }
    }

    return null;
}

/******************************Determine Current Period******************************************/
function determineCurrentPeriod(MPD, periodsInfo)
{
    var ast = new Date(MPD.xmlData.getAttribute("availabilityStartTime")).getTime();
    var numPeriods = MPD.xmlData.getElementsByTagName("Period").length;
    var starts = periodsInfo[0];
    var durations = periodsInfo[1];
    var timeNow = new Date();
    var i;
    
    for(i=0; i<numPeriods; i++){
        if((timeNow.getTime() > ast + starts[i]*1000) && (timeNow.getTime() < ast + starts[i]*1000 + durations[i]*1000))
            return i;
    }
    
    throw("According to timing, no period is available, exiting!");
}

function periodInformation(MPD)
{
    var periods = MPD.xmlData.getElementsByTagName("Period");
    var numPeriods = periods.length;
    
    var returnInfo = new Array();
    var starts = new Array();
    var durations = new Array();
    var period;
    var start;
    var duration;
    var i;
    
    for(i=0; i<numPeriods; i++){
        period = periods[i];
        
        // determine period start
        if(period.getAttribute("start")){
            start = getTiming(period.getAttribute("start"));
        }
        else{
            if(i > 0){
                if(periods[i-1].getAttribute("duration"))
                    start = starts[i-1] + getTiming(periods[i-1].getAttribute("duration"));
            }
            else{
                if(MPD.xmlData.getAttribute("type") == "static"){
                    start = 0;
                }
            }
        }
        starts[i] = start;
        
        // determine period duration
        if(i != numPeriods-1){
            if(periods[i+1].getAttribute("start")){
                duration = getTiming(periods[i+1].getAttribute("start")) - start;
            }
            else if(period.getAttribute("duration")){
                duration = getTiming(period.getAttribute("duration"));
            }
        }
        else{
            var endTime;
            if(MPD.xmlData.getAttribute("mediaPresentationDuration"))
                endTime = getTiming(MPD.xmlData.getAttribute("mediaPresentationDuration"));
            else if(period.getAttribute("duration"))
                endTime = getTiming(period.getAttribute("duration"));
            else if(MPD.xmlData.getAttribute("type") == "dynamic")
                endTime = Number.POSITIVE_INFINITY;
            else
                throw("Must have @mediaPresentationDuratio on MPD or an explicit @duration on the last period, exiting!");
            
            duration = endTime - start;
        }
        durations[i] = duration;
    }
    
    returnInfo[0] = starts;
    returnInfo[1] = durations;
    
    return returnInfo;
}

/******************************Time Parsing******************************************/
function getTiming(timeval)
{
    var movingIndex = timeval.indexOf("P")+1;
    var Y = 0;
    var M = 0;
    var W = 0;
    var D = 0;
    var H = 0;
    var Min = 0;
    var S = 0;
    
    var year = timeval.indexOf("Y", movingIndex);
    if(year != -1){
        Y = timeval.substring(movingIndex, year);
        movingIndex = year+1;
    }
    
    var month = timeval.indexOf("M", movingIndex);
    if(month != -1 && month < timeval.indexOf("T", movingIndex)){
        M = timeval.substring(movingIndex, month);
        movingIndex = month+1;
    }
    
    var week = timeval.indexOf("W", movingIndex);
    if(week != -1){
        W = timeval.substring(movingIndex, week);
        movingIndex = week+1;
    }
    
    var day = timeval.indexOf("D", movingIndex);
    if(day != -1){
        D = timeval.substring(movingIndex, day);
        movingIndex = day+1;
    }
    
    movingIndex = timeval.indexOf("T", movingIndex)+1;
    
    var hour = timeval.indexOf("H", movingIndex);
    if(hour != -1){
        H = timeval.substring(movingIndex, hour);
        movingIndex = hour+1;
    }
    
    var minute = timeval.indexOf("M", movingIndex);
    if(minute != -1){
        Min = timeval.substring(movingIndex, minute);
        movingIndex = minute+1;
    }
    
    var second = timeval.indexOf("S", movingIndex);
    if(second != -1){
        S = timeval.substring(movingIndex, second);
        movingIndex = second+1;
    }
    
    return (Y*365*24*60*60 + M*30*24*60*60 + W*7*24*60*60 + D*24*60*60 + H*60*60 + Min*60 + S*1);
}

/******************************get MediaPresentationDuration******************************************/
function getMediaPresentationDuration(mpd){
//case 1:mediaPresentationDuration="PT9M57S"
var r1=/[-+]?[0-9]*\.?[0-9]+/g;
//Case 2:sometimes they are in the form of "PT7200S"
var MediaPresentationDuration=0;
//s.match(r):object, Number(s.match(r)):number  
if(mpd.getAttribute("mediaPresentationDuration")){
   var num_string=mpd.getAttribute("mediaPresentationDuration").match(r1);
   var num_len=(mpd.getAttribute("mediaPresentationDuration").match(r1)).length;   
   for(i=num_len-1;i>=0;i--){
	MediaPresentationDuration=MediaPresentationDuration+parseFloat(num_string[i])*Math.pow(60,num_len-i-1);
   }
}else{
   MediaPresentationDuration=100;
} 
return MediaPresentationDuration;
}

/******************find the URL(the top level baseURL)********************************/
function getBaseURL(mpd){
  var BaseURL1;
  if(mpd.getElementsByTagName("BaseURL")[0]){
    URLs=mpd.getElementsByTagName("BaseURL");
	BaseURL1=URLs.item(0).firstChild.data;
  }else{	
    var mpdurl=document.getElementById("mpdbox").value;
    var n=mpdurl.lastIndexOf("/"); 
    BaseURL1= mpdurl.substring(0,n+1);  
  }
//alert("BaseURL:"+BaseURL1);
return BaseURL1; 
}

/************************* Period@start ****************************************/
// normally the number of period is 1, but it can be multiple
function getPS(mpd){
var len=mpd.getElementsByTagName("Period").length;
var ps_string= new Array(len);
var ps= new Array(len);
for(var i=0; i<len;i ++){
    if(mpd.getElementsByTagName("Period")[i].getAttribute("start")){
       ps_string[i]=mpd.getElementsByTagName("Period")[i].getAttribute("start");  // ps[0]=PT0S, string, if not defined, then it is value if null
	 } else{
	   ps_string[i]="PT0S";
	 }
	var ps=parseFloat(ps_string);
    alert("ps[i]:"+ps[i]);
	alert("period:"+mpd.getElementsByTagName("Period")[i]);
}
return ps;
}

/******************get the attributes of MPD(root) element********************************/
// get fetch time(FT), which is normally earlier than MPD@availabilityStartTime
//MPD@availabilityStartTime, it is just a value
function getAST(mpd){
var ast_string=mpd.getAttribute("availabilityStartTime"); //string

//if(ast_string.charAt(ast_string.length - 1) != "Z")
  //  ast_string = ast_string + "Z";

var ast=new Date(ast_string);		
//ast = new Date(ast.getTime() - ast.getTimezoneOffset()*60000);  //Convert to UTC
return ast;
}
//MPD@timeShiftBufferDepth
function getTSBD(mpd){

var r1=/[-+]?[0-9]*\.?[0-9]+/g;
//Case 2:sometimes they are in the form of "PT7200S"
var tsbd=0;
//s.match(r):object, Number(s.match(r)):number  
if(mpd.getAttribute("timeShiftBufferDepth")){
//   var tsbd_string=mpd.getAttribute("timeShiftBufferDepth").match(r1);
//   var tsbd_len=tsbd_string.length;   
//   for(i=tsbd_len-1;i>=0;i--){
//	tsbd=tsbd+parseFloat(tsbd_string[i])*Math.pow(60,tsbd_len-i-1);
//   }
    tsbd = getTiming(mpd.getAttribute("timeShiftBufferDepth"));
}else{
   tsbd=veryLargeDuration;
}
//alert("tsbd:"+tsbd);
return tsbd;
}

// MPD@suggestedPresentationDelay, it is just a value
function getSPD(mpd){
//var r1=/[-+]?[0-9]*\.?[0-9]+/g;
//Case 2:sometimes they are in the form of "PT7200S"
var spd=0;
//s.match(r):object, Number(s.match(r)):number  
if(mpd.getAttribute("suggestedPresentationDelay")){
//   var spd_string=mpd.getAttribute("suggestedPresentationDelay").match(r1);
//   var spd_len=spd_string.length;   
//   for(i=spd_len-1;i>=0;i--){
//	spd=spd+parseFloat(spd_string[i])*Math.pow(60,spd_len-i-1);
//   }
    spd = getTiming(mpd.getAttribute("suggestedPresentationDelay"));
}else{
   spd=5;
}
return spd;
}

 //MPD@minimumUpdatePeriod, it is just a value
function getMUP(mpd){
//var r1=/[-+]?[0-9]*\.?[0-9]+/g;
//Case 2:sometimes they are in the form of "PT7200S"
var mup=0;
//s.match(r):object, Number(s.match(r)):number  
if(mpd.getAttribute("minimumUpdatePeriod")){
//   var mup_string=mpd.getAttribute("minimumUpdatePeriod").match(r1);
//   var mup_len=mup_string.length;   
//   for(i=mup_len-1;i>=0;i--){
//	mup=mup+parseFloat(mup_string[i])*Math.pow(60,mup_len-i-1);
//   }
    mup = getTiming(mpd.getAttribute("minimumUpdatePeriod"));
    mup= Math.min(mup, 24*60*60);
}else{
   mup=maxMUP;
}
return mup;
}
// MPD@minBufferTime, it is just a value
function getMBT(mpd){
//var r1=/[-+]?[0-9]*\.?[0-9]+/g;
//Case 2:sometimes they are in the form of "PT7200S"
var mbt=0;
//s.match(r):object, Number(s.match(r)):number  
if(mpd.getAttribute("minBufferTime")){
//   var mbt_string=mpd.getAttribute("minBufferTime").match(r1);
//   var mbt_len=mbt_string.length;   
//   for(i=mbt_len-1;i>=0;i--){
//	mbt=mbt+parseFloat(mbt_string[i])*Math.pow(60,mbt_len-i-1);
//   }
    mbt = getTiming(mpd.getAttribute("minBufferTime"));
}else{
   mbt=10;
}
return mbt;
}

// MPD@publishTime
function getPT(mpd){
var pt;
if(mpd.getAttribute("publishTime")){
   var pt_string=mpd.getAttribute("publishTime");
   
//if(pt_string.charAt(pt_string.length - 1) != "Z")
  //  pt_string = pt_string + "Z";

   var pt=new Date(pt_string);	

}else{
   pt=null;
}
return pt;
}

function printOutput(string)
{
    theD =document.getElementById('SegmentOutput');
    theD.innerHTML=string + theD.innerHTML;						 
}

