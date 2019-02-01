<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<!--This program is free software: you can redistribute it and/or modify
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

<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <meta charset="utf-8" />
    <head>
        <title> DASH Conformance Test</title>
        <meta name="description" content="DASH Conformance">
        <meta name="keywords" content="DASH,DASH Conformance,DASH Validator">
        <meta name="author" content="Nomor Research GmbH">
        <link rel="icon" href="favicon.ico?v1" type="image/x-icon" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    </head>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
    <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/themes/smoothness/jquery-ui.css" />
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js"></script>
    <!--link rel="stylesheet" href="/resources/demos/style.css" /-->

    <link rel="STYLESHEET" type="text/css" href="tree/dhtmlxTree/codebase/dhtmlxtree.css">
    <script type="text/javascript" src="tree/dhtmlxTree/codebase/dhtmlx_deprecated.js"></script>
    <script type="text/javascript"  src="tree/dhtmlxTree/codebase/dhtmlxtree.js"></script>
    <script type="text/javascript" src="tree/dhtmlxTree/codebase/ext/dhtmlxtree_json.js"></script>
    
    <link href="gdpr/css/jquery-eu-cookie-law-popup.css" rel="stylesheet">
    <script src="gdpr/js/jquery-eu-cookie-law-popup.js"></script>
<?php 
    if(isset($_REQUEST['mpdurl']))
    {
        $url = $_REQUEST['mpdurl'];     // To get url from POST request.
    }
    else
        $url = "";
    
    if(isset($_REQUEST['cmaf']) && file_get_contents("../CMAF/cmaf_OnOff.txt")==1)
    {
        $cmaf = $_REQUEST['cmaf'];
    }
    else
        $cmaf = "";
    
    if(isset($_REQUEST['dvb']))
    {
        $dvb = $_REQUEST['dvb'];     // To get url from POST request.
    }
    else
        $dvb = "false";
    
    if(isset($_REQUEST['hbbtv']))
    {
        $hbbtv = $_REQUEST['hbbtv'];     // To get url from POST request.
    }
    else
        $hbbtv = "false";
?>

<script type="text/javascript">

    var url = "";
    var dvb=0,hbbtv=0;
    window.onload = function()
    {
        //To aid the localhost testing of the page, use CSP upgrade only if page is on https server.
        if(window.location.protocol== "https:")
            $('head').append('<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests" />');
        
        //Display the version number after refering to change log.     
        $.post(
                "../DASH/IOPVersion.php",
        ).done(function(response){
            document.getElementById("footerVersion").innerHTML=response;
        });

        url = "<?php echo $url; ?>";
        if(url !== "")
        {
            document.getElementById("urlinput").value=url;
            submit();
        }
    }

    function fixImage(id){
        switch(tree.getLevel(id)){
            case 1:
                tree.setItemImage2(id,'folderClosed.gif','folderOpen.gif','folderClosed.gif');
                break;
            case 2:
                tree.setItemImage2(id,'folderClosed.gif','folderOpen.gif','folderClosed.gif');
                break;
            case 3:
                tree.setItemImage2(id,'folderClosed.gif','folderOpen.gif','folderClosed.gif');
                break;
            default:
                tree.setItemImage2(id,'leaf.gif','folderClosed.gif','folderOpen.gif');
                break;
        }
    }

</script>
    
    
<style>

    * {
        margin: 0;
      }
      
    html, body {
        height: 100%;
        /*background-image: url("http://localhost/Conformance-Software/webfe/img/background_image/page_background4.jpg");
        background-size:     cover;                     
        background-repeat:   no-repeat;
        background-position: center center; */
     }
     
    .page-wrap {
        min-height: 100%;
        /* equal to footer height */
        margin-bottom: -90px; 
    }
    
    .page-wrap:after {
        content: "";
        display: block;
    }
    
    .site-footer, .page-wrap:after {
        height: 90px; 
    }
    
    .site-footer {
        background: #909090;
        margin-top: auto;
    }
    .mytext {
        width: 600px;
    }
    
    div.hidden{
        display: none;
    }
    
    div.normal{
        display: block;
    }
    
    #tot{
        text-align:center;
    }
    
    #groupA{
        text-align:center;
        margin-left:-5%;
        margin-top:1%;
    }
    
    .box{
        display:inline-block;
        text-align:center;
        width:600px;
        height:50px;
        border: 1px solid #fff;
        color: #fff;
        margin-top:0.2%;
    }
    
    #progressbar{
        text-align:center;
    }
    
    #to{
        text-align:center;
        border-width:medium;
    }
    
    #dash{
        text-align:center;
        margin-top: 15%;
        
    }
    
    p.sansserif {
        font-family: Arial, Helvetica, sans-serif;
    }
    
    #btn8 {
        background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #007bff), color-stop(1, #007bff) );
        filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#bddbfa', endColorstr='#80b5ea');
        background-color:#bddbfa;
        -webkit-border-top-left-radius:0px;
        -moz-border-radius-topleft:0px;
        border-top-left-radius:3px;
        -webkit-border-top-right-radius:0px;
        -moz-border-radius-topright:0px;
        border-top-right-radius:3px;
        -webkit-border-bottom-right-radius:0px;
        -moz-border-radius-bottomright:0px;
        border-bottom-right-radius:3px;
        -webkit-border-bottom-left-radius:0px;
        -moz-border-radius-bottomleft:0px;
        border-bottom-left-radius:3px;
        text-indent:-1px;
        border: 1px solid #007bff;
        display:inline-block;
        color: #fff;
        font-family: Avantgarde, TeX Gyre Adventor, URW Gothic L, sans-serif;
        font-size:24px;
        font-weight:normal;
        font-style:normal;
        height:50px;
        line-height:40px;
        width:100px;
        text-decoration:none;
        text-align:center;
        position:absolute;
        margin-left:0.5%;
        margin-top: -0.7%;
        -webkit-border-radius: 5px;
        -moz-border-radius: 5px;
        border-radius: 5px;
    }
    
    #btn8:hover:enabled {
        background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #0069d9), color-stop(1, #0069d9) );
        background:-moz-linear-gradient( center top, #0062cc 5%, #0062cc 100% );
        filter:progid:DXImageTransform.Microsoft.gradient(startColorstr='#80b5ea', endColorstr='#bddbfa');
        background-color:#80b5ea;
    }
    
    #btn8:active:enabled {
        transform: translateY(4px);
        /*position:relative;
        top:1px;*/
    }
    
    #btn8:disabled {
        background:-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #fff), color-stop(1, #007bff) );
        background:-moz-linear-gradient( center top, #007bff 5%, #007bff 100% );
        background-color:#007bff;
        color:#fff;
        text-shadow: 0px 1px 0px rgba(255, 255, 255, .5);
        -webkit-border-radius: 5px;
        -moz-border-radius: 5px;
        border-radius: 5px;
    }
    
    input{
        text-align:center;
    }
    
    #not{
        position:center;
    }
    
    #treeboxbox_tree{
        position:absolute;
        top:250px;
        left:40px;
    }
    
    .box__dragndrop{
        display: none;
    }
    
    .box.has-advanced-upload {
        background-color: #007bff;
        outline: 1px dashed #007bff;
        outline-offset: -3px;
        background-color: #007bff;
    }
    
    .box.has-advanced-upload .box__dragndrop {
        display: inline;
    }
    
    .box.is-dragover {
        background-color: #b4f7fa;
    }
    
    .box__file {
	width: 0.1px;
	height: 0.1px;
	opacity: 0;
	overflow: hidden;
	position: absolute;
	z-index: -1;
    }
    
    .box__file + label {
        position: relative;
        margin-top:2.5%;
        margin-left:3%;
        display: inline-block;
        cursor: pointer; /* "hand" cursor */
    }
    
    .box__button {
        position: absolute;
        margin-top:0.8%;
        margin-left: 8%;
        display: none;
    }
    
    .profiles{
        margin-top: -1%;
    }
    
    .topright {
        position: absolute;
        top: 0px;
        right: 0px;
    }
    
    #settings_button{
        position:absolute;
        top:10px;
        right:10px;
        border: 0;
    }
    
    #settings_img{
        position:absolute;
        top:0px;
        right:0px;
        background-size: 100%;
    }
    
    #demo{
        position:absolute;
        top:10px;
        right:50px;
        width:200px;
        height:80px;
    }
    
    #cont{
        position:absolute;
        top:40px;
        right:60px;
    }
    
    .chkbox {
        display: block;
        position: relative;
        padding-left: 35px;
        margin-bottom: 12px;
        cursor: pointer;
        font-size: 18px;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
    }

    /* Hide the browser's default checkbox */
    .chkbox input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    /* Create a custom checkbox */
    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 20px;
        width: 20px;
        background-color: #9bd3ec;
       
    }
    
    /* On mouse-over, add a grey background color */
    .chkbox:hover input ~ .checkmark {
        background-color: #7de5eb;
    }

    /* When the checkbox is checked, add a blue background */
    .chkbox input:checked ~ .checkmark {
        background-color: #2196F3;
    }

    /* Create the checkmark/indicator (hidden when not checked) */
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }

    /* Show the checkmark when checked */
    .chkbox input:checked ~ .checkmark:after {
        display: block;
    }
    
    /* Style the checkmark/indicator */
    .chkbox .checkmark:after {
        left: 9px;
        top: 5px;
        width: 5px;
        height: 10px;
        border: solid white;
        border-width: 0 3px 3px 0;
        -webkit-transform: rotate(45deg);
        -ms-transform: rotate(45deg);
        transform: rotate(45deg);
    }
    
    #conformance_only{
        position: absolute;
        left: 42%;
    }
    
</style>
<body>
<div class="eupopup"></div>
<div class="eupopup eupopup-fixedtop"></div>
<div class="eupopup eupopup-color-default"></div>

<div class="page-wrap">    
    <div class="container">
        <button type="submit" class="btn btn-info" id="settings_button" data-toggle="collapse" data-target="#demo" >
        <img src="img/settings.jpg" class="settings_img" id="settings_img"  width="45" height="45" />
        </button>  
        <div id="demo" class="collapse">    
            <legend>Enforce profile(s):</legend>
            <div data-role="controlgroup" id="cont">
                <label class="chkbox" for="dashifprofile">DASH-IF
                    <input type="checkbox" name="dashifprofile" id="dashifprofile" >
                    <span class="checkmark"></span>
                </label>
                <label class="chkbox" for="dvbprofile">DVB
                    <input type="checkbox" name="dvbprofile" id="dvbprofile" >
                    <span class="checkmark"></span>
                </label>
                <label class="chkbox" for="hbbtvprofile">HbbTV
                    <input type="checkbox" name="hbbtvprofile" id="hbbtvprofile" >
                    <span class="checkmark"></span>
                </label>
                <label class="chkbox" for="cmafprofile">CMAF
                    <input type="checkbox" name="cmafprofile" id="cmafprofile" >
                    <span class="checkmark"></span>
                </label>
                <label class="chkbox" for="ctawaveprofile">CTA WAVE
                    <input type="checkbox" name="ctawaveprofile" id="ctawaveprofile" >
                    <span class="checkmark"></span>
                </label>
            </div>  
        </div>  
    </div>

    <div id="dash">
        <br>
        <img id="img2" border="0" src="dash_img/dashlogo.jpeg" alt ="DASH Conformance" width="543" height="88" >
        <img id="img2" border="0" src="dash_img/Dash1.jpeg" width="191" height="61" >
        <br>    <br>
    </div>
    <p align="center" class="sansserif">Validation (Conformance check) of ISO/IEC 23009-1 MPEG-DASH MPD and Segments</p>
    <div id="groupA">
        <div>
            <input type="text" id='urlinput' name="urlinput" class="mytext" placeholder="Enter MPD URL" onkeyup="CheckKey(event)"/>

        </div>
        <div>
            <div class="box"  >
                <div class="box__input" id="drop_div">
                    <input class="box__file" type="file" name="file" id="file" />
                    <label for="file"><strong>Choose a file</strong><span class="box__dragndrop"> or drag it here</span>.</label>
                    <button class="box__button" type="submit">Upload</button>
                </div>
            </div>
            <button id="btn8" onclick="submit()"> GO </button>
        </div>
        
        <a id="dynamic" href="url" target="_blank" style="visibility:hidden;" >Dynamic timing validation</a>
    </div>
    
    <div id="conformance_only">
        <label class="chkbox">MPD conformance only
            <input type="checkbox" id="mpdvalidation"  value="0">
            <span class="checkmark"></span>
        </label>
    </div>        
    
    
    <div id="progressbar" style="width:100px;background:#FFFFF;"></div>

    <div id = "not">
        <br>    <br>
    </div>

    <div id="to" >
        <p align="center"></p>
        <p id="par" class="sansserif" style="visibility:hidden;">Loading....</p>
        <p id="profile" class="sansserif" style="visibility:hidden;">Profiles: </p>
        <a id="list" href="url" target="_blank" style="visibility:hidden;" >Feature list</a>
    </div>

    <table>
        <tr>
            <td valign="top">
                <div id="treeboxbox_tree" style="background-color:#0000;overflow:hidden;border :none; ">
                    <a id="downloadpar" href="" download="" style="visibility:hidden;"></a>
                </div>
            </td>

            <td rowspan="2" style="padding-left:25" valign="top">
            </td>
        </tr>
    </table>
</div>

<script type="text/javascript">
var progressXMLRequest;
var progressXML;
var progressTimer;
var current = 0;
var dirid="";
var kidsloc=[];
var lastloc = 0;
var mpdprocessed = false;
var counting =0;
var representationid =1;
var adaptationid = 1;
var periodid = 1;
var hinindex = 2;
var hinindex2 = 1;
var repid =[];	
var totarr = [];
var adaptid=[];
var perid=[];
var file,fd,xhr;
var uploaded = false;
var numPeriods = 0;
var dynamicsegtimeline = false;
var segmentListExist = false;
var SessionID = "id"+Math.floor(100000 + Math.random() * 900000);
var totarrstring=[];
var xmlDoc_progress;
var xmlDoc_mpdresult;
var progressSegmentsTimer;
var treeTimer;
var pollingTimer;
var mpdTimer;
var ChainedToUrl;
var cmaf = "<?php echo $cmaf; ?>";
var dvb = 0;
var hbbtv = 0;
var dashif=0;
var ctawave=0;
var downloadarray=[];

/////////////////////////////////////////////////////////////
//Check if 'drag and drop' feature is supported by the browser, if not, then traditional file upload can be used.
var isAdvancedUpload = function() {
    var div = document.createElement('div');
    return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
}();

var $form = $('.box');
var droppedFile = false;

if (isAdvancedUpload) {
    $form.addClass('has-advanced-upload');
    $form.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
    })
    .on('dragover dragenter', function() {
        $form.addClass('is-dragover');
    })
    .on('dragleave dragend drop', function() {
        $form.removeClass('is-dragover');
    })
    .on('drop', function(e) {
        droppedFile = e.originalEvent.dataTransfer.files;
        showFiles( droppedFile );
        $form.trigger('submit');
    });
}

$('.box__file').on('change', function(e) { // when drag & drop is NOT supported
    droppedFile=e.target.files;
    showFiles( droppedFile );
    $form.trigger('submit');
});

//document.querySelector('#btn8').addEventListener('change', function(e) {
$form.on('submit', function(e) {
 
    file=droppedFile[0];
    //file = this.files[0];
    fd = new FormData();
    fd.append("afile", file);
    fd.append("sessionid", JSON.stringify(SessionID));
    //xhr = new XMLHttpRequest();
    // xhr.open('POST', 'process.php', true);
    // xhr.onload = function() {
    uploaded=true;
    submit();

    //};
    //xhr.send(fd);
    //}, false);
});

var $input    = $form.find('input[type="file"]'),
    $label    = $form.find('label'),
    showFiles = function(files) {
        $label.text(files[0].name);
    };

///////////////////////////////////////////////////////////////

function button()
{
    current = current+1;
    $( "#progressbar" ).progressbar({
      value: current
    });
}

function CheckKey(e) //receives event object as parameter
{
   var code = e.keyCode ? e.keyCode : e.which;
   if((code === 13) && (document.getElementById("btn8").disabled == false))
   {
       submit();
   }
}

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

function  progressEventHandler(){
    if (progressXMLRequest.readyState == 4){    // continue if the process is completed
        if (progressXMLRequest.status == 200) {       // continue only if HTTP status is "OK" 
            try {
        
                response = progressXMLRequest.responseXML;          // retrieve the response

                // do something with the response
                progressXML = progressXMLRequest.responseXML.documentElement;

                var progressPercent = progressXML.getElementsByTagName("percent")[0].childNodes[0].nodeValue;
                var dataProcessed = progressXML.getElementsByTagName("dataProcessed")[0].childNodes[0].nodeValue;
                var dataDownloaded = progressXML.getElementsByTagName("dataDownloaded")[0].childNodes[0].nodeValue;

                dataProcessed = Math.floor( dataProcessed / (1024*1024) );
                dataDownloaded = Math.floor( dataDownloaded / (1024) );
                //Get currently running Adaptation and Representation numbers.
                var lastRep = progressXML.getElementsByTagName("CurrentRep")[0].childNodes[0].nodeValue;
                var lastAdapt =progressXML.getElementsByTagName("CurrentAdapt")[0].childNodes[0].nodeValue;
                var lastPeriod = progressXML.getElementsByTagName("CurrentPeriod")[0].childNodes[0].nodeValue;
                
                var progressText;
                if (lastRep == 1 && lastAdapt == 1 && progressPercent == 0 && dataDownloaded == 0 && dataProcessed == 0) //initial state
                    progressText = "Processing MPD, please wait...";
                else
                    progressText = "Processing Representation "+lastRep+" in Adaptationset "+lastAdapt+" in Period "+lastPeriod+", "+progressPercent+"% done ( "+dataDownloaded+" KB downloaded, "+dataProcessed+" MB processed )";

		if( numPeriods > 1 && !ctawave )
		{
                    progressText = progressText + "<br><font color='red'> MPD with multiple Periods (" + numPeriods + "). Only segments of the current period will be checked.</font>"
		}
		
                if( dynamicsegtimeline)
		{
                    progressText = progressText + "<br><font color='red'> Segment timeline for type dynamic is not supported, only MPD will be tested. </font>"
		}
                
                if(segmentListExist)
		{
                    progressText = progressText + "<br><font color='red'> SegmentList is not supported, only MPD will be tested. </font>"
		}
                
                document.getElementById("par").innerHTML=progressText;
                
                //update only once
                if (document.getElementById("profile").innerHTML === "Profiles: ")
                {
                    var profileList = progressXML.getElementsByTagName("Profile")[0].childNodes[0].nodeValue;
                    if(dashif && profileList.search("http://dashif.org/guidelines/dash264")===-1)
                        profileList+= ", http://dashif.org/guidelines/dash264";
                    document.getElementById("profile").innerHTML="Profiles: " + profileList;            
                    document.getElementById('profile').style.visibility='visible';
                }
            }
            catch(e)
            {
                ;//alert("Error processing: " + e.toString());          // display error message
            }
        }
        else
        {
            ;//alert("" + );        // display status message
        }
    }
}

function progressupdate()
{
    progressXMLRequest=createXMLHttpRequestObject();
    if (progressXMLRequest)     // continue only if xmlHttp isn't void
    {
        try          // try to connect to the server
        {
            var progressDocURL='temp/'+dirid+'/progress.xml';
            var now = new Date();
            progressXMLRequest.open("GET", progressDocURL += (progressDocURL.match(/\?/) == null ? "?" : "&") + now.getTime(), false);  
            //initiate server request, trying to bypass cache using tip from 
            //https://developer.mozilla.org/es/docs/XMLHttpRequest/Usar_XMLHttpRequest#Bypassing_the_cache,
            progressXMLRequest.onreadystatechange = progressEventHandler;
            progressXMLRequest.send(null);
        }
        catch (e)      // display an error in case of failure
        {
            ;//alert("Failed loading progress\n" + e.toString());
        }
    }
}

function submit()
{
    //document.getElementById("dash").style.marginTop="1%";
    mpdprocessed = false;
    url = document.getElementById("urlinput").value; 
    
    if(window.location.href.indexOf('https') !== -1){
        if(url && url.indexOf('https') === -1){
            setStatusTextlabel("HTTP content is detected. <span style=\"color:red\"><b>This secure (HTTPS) site cannot process the HTTP content.</b></span> If you wish to continue using this content, please use <a target=\"_blank\" href=\"http://54.72.87.160/conformance/current/Conformance-Frontend/Conformancetest.php\">HTTP-based interface</a> instead.");
            return false;
        }
    }
 
    if (uploaded===true)
	url="upload";
    
    var stringurl = [];
	
    stringurl[0] = url;

    if($("#mpdvalidation").is(':checked'))
        stringurl[1] = 1;
    else
   	stringurl[1] = 0 ;
    
    if($("#dvbprofile").is(':checked'))
        dvb = 1;
    if($("#hbbtvprofile").is(':checked'))
        hbbtv= 1;
    if($("#cmafprofile").is(':checked'))
        cmaf = 1;
    if($("#dashifprofile").is(':checked'))
        dashif = 1;
    if($("#ctawaveprofile").is(':checked'))
        ctawave = 1;
    
    stringurl[2]=cmaf;
    stringurl[3]=dvb;
    stringurl[4]=hbbtv;
    stringurl[5]=dashif;
    stringurl[6]=ctawave;

    initVariables();
    setUpTreeView();

    setStatusTextlabel("Processing...");
    document.getElementById("btn8").disabled="true";
    document.getElementById("drop_div").disabled="true";
    document.getElementById('list').style.visibility='hidden';
    //document.getElementById('img').style.visibility='visible';
    //document.getElementById('par').style.visibility='visible';
    //Generate a random folder name for results in "temp" folder
    dirid="id"+Math.floor((Math.random() * 10000000000) + 1);

    if(uploaded===true){ // In the case of file upload.
        fd.append("foldername", dirid);
        fd.append("urlcode", JSON.stringify(stringurl));
        $.ajax ({
            type: "POST",
            url: "../Utils/Process.php",
            data: fd,
            contentType: false,
            processData: false
        });}
    else{  // Pass to server only, no JS response model.
           UrlExists(stringurl[0], function(urlStatus){
                 //console.log(urlStatus);
                if(urlStatus === 200 && stringurl[0]!=""){
                    $.post("../Utils/Process.php",{urlcode:JSON.stringify(stringurl),sessionid:JSON.stringify(SessionID),foldername: dirid});
                }
                else{ //if(urlStatus === 404){
                   window.alert("Error loading the MPD, please check the URL.");
                   clearInterval( pollingTimer);	
                   finishTest(); 
                }
            });
        
        //$.post("process.php",{urlcode:JSON.stringify(stringurl),sessionid:JSON.stringify(SessionID),foldername: dirid});
    }
    
    //Start polling of progress.xml for the progress percentage results.
    progressTimer = setInterval(function(){progressupdate()},100);
    pollingTimer = setInterval(function(){pollingProgress()},800);//Start polling of progress.xml for the MPD conformance results.
};

function pollingProgress()
{
    xmlDoc_progress=loadXMLDoc("temp/"+dirid+"/progress.xml");

    if (xmlDoc_progress === null)
        return;
    else
        var MPDError=xmlDoc_progress.getElementsByTagName("MPDError");
    
    if(MPDError.length === 0)
        return;
    else    
        totarrstring=MPDError[0].childNodes[0].nodeValue;

    if (totarrstring == 1)//Check for the error in MPD loading.
    {
        window.alert("Error loading the MPD, please check the URL.");
        clearInterval( pollingTimer);	
        finishTest();            
        return false;
    }
    
    clearInterval(pollingTimer);
    tree.loadJSONObject({
        id: 0,
        item: [{
            id: 1,
            text: "Mpd"
        }]
    });
    mpdTimer = setInterval(function(){mpdProgress()},50);
}

var mpd_node_index = 0;
var mpdresult_x = 2;
var mpdresult_y = 1;
var branch_added = [0, 0, 0, 0];
var branchName = [
    "XLink resolving",
    "MPD validation",
    "Schematron validation",
    "HbbTv DVB validation"
];
var log_brancName = "mpd log";
var shouldFinishTest = false;

function mpdProgress(){
    xmlDoc_mpdresult = loadXMLDoc("temp/"+dirid+"/mpdresult.xml");
    
    if(xmlDoc_mpdresult === null)
        return;
    
    var mpd_node_index_until = xmlDoc_mpdresult.documentElement.childNodes.length;
    if(mpd_node_index === mpd_node_index_until){
        clearInterval(mpdTimer);

        if(!mpdprocessed){
            mpdprocessed = true;

            automate(mpdresult_y, mpdresult_x, log_brancName);
            tree.setItemImage2(mpdresult_x, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
            kidsloc.push(mpdresult_x);
            urlarray.push("temp/"+dirid+"/mpdreport.html");
            
            var i;
            for(i=0; i<3; i++){
                if(xmlDoc_mpdresult.documentElement.childNodes[i].childNodes[0].nodeValue === 'error'){
                    shouldFinishTest = true;
                    break;
                }
            }
            
            if(shouldFinishTest){
                finishTest();
                return false;
            }
            else{
                treeTimer = setInterval(function(){processmpdresults()},100);
                return;
            }
        }
    }
    
    var node = xmlDoc_mpdresult.documentElement.childNodes[mpd_node_index];
    if(!node){
        clearInterval(mpdTimer);	
        finishTest();            
        return false;
    }
    
    var node_result = node.childNodes[0].nodeValue;
    if(node_result === 'No Result'){
        addToTree(0);
        return;
    }
    else if(node_result === 'true'){
        addToTree(1);
        mpd_node_index++;
    }
    else if(node_result === 'warning'){
        addToTree(2);
        mpd_node_index++;
        log_branchName = "mpd warning log";
    }
    else if(node_result === 'error'){
        while(mpd_node_index !== mpd_node_index_until){
            addToTree(3);
            mpd_node_index++;
        }
        log_brancName = "mpd error log";
    }
}

function addToTree(button){
    if(branch_added[mpd_node_index] === 0){
        automate(mpdresult_y, mpdresult_x, branchName[mpd_node_index]);
        branch_added[mpd_node_index] = mpdresult_x;
        mpdresult_x++;
    }
    
    if(button === 0)
        tree.setItemImage2(branch_added[mpd_node_index], 'ajax-loader.gif', 'ajax-loader.gif', 'ajax-loader.gif');
    else if(button === 1)
        tree.setItemImage2(branch_added[mpd_node_index], 'right.jpg', 'right.jpg', 'right.jpg');
    else if(button === 2)
        tree.setItemImage2(branch_added[mpd_node_index], 'log.jpg', 'log.jpg', 'log.jpg');
    else if(button === 3)
        tree.setItemImage2(branch_added[mpd_node_index], 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
}

function processmpdresults()
{
    xmlDoc_progress=loadXMLDoc("temp/"+dirid+"/progress.xml");
    
    // Check if the MPD is dynamic.
    if(xmlDoc_progress.getElementsByTagName("dynamic").length !== 0){
        if (xmlDoc_progress.getElementsByTagName("dynamic")[0].innerHTML === "true"){
            if (xmlDoc_progress.getElementsByTagName("SegmentTimeline").length !== 0)
                dynamicsegtimeline = true;

            document.getElementById('dynamic').style.visibility='visible';
            document.getElementById("dynamic").href='http://vm1.dashif.org/DynamicServiceValidator/?mpdurl=' +url ;
        }
    }

    // Check if SegmentList exist
    if(xmlDoc_progress.getElementsByTagName("segmentList").length !== 0)
        segmentListExist = true;
    
    if (dynamicsegtimeline || segmentListExist){
        finishTest();
        return;
    }

    // Get the number of AdaptationSets, Representations and Periods.
    var currentpath = window.location.pathname;
    currentpath = currentpath.substring(0, currentpath.lastIndexOf('/'));
    document.getElementById("list").href=currentpath+'/temp/'+dirid+'/featuretable.html';
    document.getElementById('list').style.visibility='visible';

    var  Treexml = xmlDoc_progress.getElementsByTagName("Representation");
    if (Treexml.length==0){
        var complete=xmlDoc_progress.getElementsByTagName("completed");
        if(complete[0].textContent == "true")
            finishTest();     
        return;
    }else{
        var Periodxml = xmlDoc_progress.getElementsByTagName("Period"); 
        Period_count = Periodxml.length;
        var AdaptRepPeriod_count = Period_count;
        for(var p=0; p<Period_count; p++){
            Adapt_count= Periodxml[p].childNodes.length;
            AdaptRepPeriod_count += ' ' + Adapt_count;
            var Adaptxml = xmlDoc_progress.getElementsByTagName("Adaptation");
            for (var v=0; v<Adapt_count; v++){
                AdaptRepPeriod_count += " " +Adaptxml[v].getElementsByTagName("Representation").length;
            }
        }
    }
    
    totarr = AdaptRepPeriod_count.split(" ");
    var x = mpdresult_x+1;
    var y = 1;
    var childno = 1;
    var childno2 = 2;
    var id = x;
    repid = [];
    for(var i=0;i<totarr[0];i++){
        automate(y,x,"Period "+(i+1));
        perid.push(x);
        tree.setItemImage2(x,'adapt.jpg','adapt.jpg','adapt.jpg');
        
        id++;
        var adaptid_temp = [];
        for(var j=0; j<totarr[childno]; j++){
            automate(x,id,"Adaptationset "+(j+1));
            
            adaptid_temp.push(id);
            tree.setItemImage2(id,'adapt.jpg','adapt.jpg','adapt.jpg');
            
            var parentid = id;
            id++;
            for(var k=0; k<totarr[childno2]; k++){
                automate(parentid,id,"Representation "+(k+1));
                repid.push(adaptid_temp[adaptid_temp.length-1]+k+1);
                id++;
            }
            
            childno2++;
        }
        
        adaptid.push(adaptid_temp);
        childno += childno2 - 1;
        childno2 ++;
        x = id;
    }
    
    var period_count = xmlDoc_progress.getElementsByTagName('PeriodCount');
    if(period_count[0].childNodes.length != 0)
        numPeriods = period_count[0].childNodes[0].nodeValue;

    lastloc = x+1;
    clearInterval(treeTimer);
    progressSegmentsTimer = setInterval(function(){progress()},400);
    document.getElementById('par').style.visibility='visible';
    document.getElementById('list').style.visibility='visible';
}

function progress()
{
    if(periodid > totarr[0]){
        clearTimeout(progressSegmentsTimer);
        setStatusTextlabel("Conformance test completed");
        finishTest();
    }
    
    xmlDoc_progress=loadXMLDoc("temp/"+dirid+"/progress.xml");
    
    if(xmlDoc_progress == null)
        return;
    
    tree.setItemImage2(repid[counting],'ajax-loader.gif','ajax-loader.gif','ajax-loader.gif');
    
    var CrossRepValidation=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CrossRepresentation");
    var ComparedRepresentations = xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("ComparedRepresentations");
    var HbbTVDVBComparedRepresentations = xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("HbbTVDVBComparedRepresentations");
    var SelectionSet=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("SelectionSet");
    var CmafProfile=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CMAFProfile");
    var CTAWAVESelectionSet=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CTAWAVESelectionSet");
    var CTAWAVEProfile=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CTAWAVEPresentation");
    
    if(representationid >totarr[hinindex]){
        if (cmaf && ComparedRepresentations.length !=0){
            for(var i =1; i<=ComparedRepresentations.length;i++){

                if(ComparedRepresentations[i-1].textContent=="noerror"){
                    tree.setItemImage2(adaptid[periodid-1][i-1],'right.jpg','right.jpg','right.jpg');
                    automate(adaptid[periodid-1][i-1],lastloc,"CMAF Compared representations validation success");

                    tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                    lastloc++;
                }
                else{
                    tree.setItemImage2(adaptid[periodid-1][i-1],'button_cancel.png','button_cancel.png','button_cancel.png');
                    automate(adaptid[periodid-1][i-1],lastloc,"CMAF Compared representations validation error");

                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2(lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_compInfo.html");
                    lastloc++;
                }
            }
        }
        
        adjustFooter();
        representationid = 1;
        hinindex++;
        adaptationid++;
    }
    else if(adaptationid>totarr[hinindex2]){
        if(CrossRepValidation.length!=0){
            for(var i =1; i<=CrossRepValidation.length;i++)
            {
                if(CrossRepValidation[i-1].textContent=="noerror"){
                    tree.setItemImage2(adaptid[periodid-1][i-1],'right.jpg','right.jpg','right.jpg');
                    automate(adaptid[periodid-1][i-1],lastloc,"Cross-representation validation success");

                    tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                    lastloc++;
                }
                else{
                    tree.setItemImage2(adaptid[periodid-1][i-1],'button_cancel.png','button_cancel.png','button_cancel.png');
                    automate(adaptid[periodid-1][i-1],lastloc,"Cross-representation validation error");

                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(adaptid[periodid-1][i-1],lastloc,"log");
                    tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_CrossInfofile.html");
                    lastloc++;
                }
            }
        }
        
        if((dvb == 1 || hbbtv == 1) && HbbTVDVBComparedRepresentations.length!=0){
            for(var i =1; i<=HbbTVDVBComparedRepresentations.length;i++){
                if(HbbTVDVBComparedRepresentations[i-1].textContent=="noerror"){
                    automate(adaptid[periodid-1][i-1],lastloc,"DVB-HbbTV Compared representations validation success");
                    tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                    lastloc++;

                    automate(adaptid[periodid-1][i-1],lastloc,"log");
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_hbbtv_dvb_compInfo.html");
                    lastloc++;
                }
                else if(HbbTVDVBComparedRepresentations[i-1].textContent=="warning"){
                    automate(adaptid[periodid-1][i-1],lastloc,"DVB-HbbTV Compared representations validation warning");
                    tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                    lastloc++;

                    automate(adaptid[periodid-1][i-1],lastloc,"log");
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_hbbtv_dvb_compInfo.html");
                    lastloc++;
                }
                else{
                    automate(adaptid[periodid-1][i-1],lastloc,"DVB-HbbTV Compared representations validation error");
                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(adaptid[periodid-1][i-1],lastloc,"log");
                    tree.setItemImage2(lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_hbbtv_dvb_compInfo.html");
                    lastloc++;
                }
            }
        }
        
        if(cmaf){
            //Additions for CMAF Selection Set and Presentation Profile.
            if(SelectionSet.length!=0){
                if(SelectionSet[0].textContent=="noerror"){
                        automate(perid[i-1],lastloc,"CMAF Selection Set");

                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile.html");
                        lastloc++;
                }
                else{
                        automate(perid[i-1],lastloc,"CMAF Selection Set");

                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile.html");
                        lastloc++;
                    }
            }
            if(CmafProfile.length!=0){
                if(CmafProfile[0].textContent=="noerror"){
                        automate(perid[i-1],lastloc,"CMAF Presentation Profile");

                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile.html");
                        lastloc++;
                }
                else{
                        automate(perid[i-1],lastloc,"CMAF Presentation Profile");

                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile.html");
                        lastloc++;
                }
            }
        }
        
        if(ctawave == 1){
            //Additions for CTA WAVE Selection Set and Presentation Profile.
            if(CTAWAVESelectionSet.length!=0)
            {
                if(CTAWAVESelectionSet[0].textContent=="noerror"){
                        automate(perid[periodid-1],lastloc,"CTA WAVE Selection Set");
                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile_ctawave.html");
                        lastloc++;
                }
                else if(CTAWAVESelectionSet[0].textContent=="warning"){
                        automate(perid[periodid-1],lastloc,"CTA WAVE Selection Set");
                        tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile_ctawave.html");
                        lastloc++;
                }
                else{
                        automate(perid[periodid-1],lastloc,"CTA WAVE Selection Set");
                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile_ctawave.html");
                        lastloc++;
                }
            }
            if(CTAWAVEProfile.length!=0)
            {
                if(CTAWAVEProfile[0].textContent=="noerror"){
                        automate(perid[periodid-1],lastloc,"CTA WAVE Presentation Profile");
                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile_ctawave.html");
                        lastloc++;
                }
                else if(CTAWAVEProfile[0].textContent=="warning"){
                        automate(perid[periodid-1],lastloc,"CTA WAVE Presentation Profile");
                        tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile_ctawave.html");
                        lastloc++;
                }
                else{
                        automate(perid[periodid-1],lastloc,"CTA WAVE Presentation Profile");
                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile_ctawave.html");
                        lastloc++;
                }
            }
        }
        
        adjustFooter();
        adaptationid = 1;
        hinindex2 += hinindex - 1;
        hinindex = hinindex2 + 1;
        tree.setItemImage2(perid[periodid-1],'right.jpg','right.jpg','right.jpg');
        periodid++;
    }
    else{
        var AdaptXML=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("Adaptation"); 
        if(AdaptXML[adaptationid-1]== null)
            return;
        else if(AdaptXML[adaptationid-1].getElementsByTagName("Representation")[representationid-1] == null)
            return;
        else{   
            var RepXML=AdaptXML[adaptationid-1].getElementsByTagName("Representation")[representationid-1].textContent;
            if(RepXML == "")
                return;
            representationid++;
        }

        if(RepXML == "noerror")
            tree.setItemImage2( repid[counting],'right.jpg','right.jpg','right.jpg');
        else if(RepXML == "warning")
            tree.setItemImage2( repid[counting],'log.jpg','log.jpg','log.jpg');
        else
            tree.setItemImage2( repid[counting],'button_cancel.png','button_cancel.png','button_cancel.png');
        
        automate(repid[counting],lastloc,"log");
        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
        kidsloc.push(lastloc);
        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(adaptationid-1)+"rep"+(representationid-2) + "log.html");
        lastloc++;

        var location = "temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(adaptationid-1)+"rep"+(representationid-2) + "sample_data.xml";
        automate(repid[counting],lastloc,"Estimate bitrate");
        tree.setItemImage2( lastloc,'csh_winstyle/calculator.gif','csh_winstyle/calculator.gif','csh_winstyle/calculator.gif');
        kidsloc.push(lastloc);
        urlarray.push("Estimate.php?location=" + location );
        lastloc++;

        counting++;
        
        adjustFooter();
        progress();
    }
}

function progress1()  //Progress of Segments' Conformance
{
    xmlDoc_progress=loadXMLDoc("temp/"+dirid+"/progress.xml");
    
    if(periodid > totarr[0]){
        clearTimeout(progressSegmentsTimer);
        setStatusTextlabel("Conformance test completed");
        finishTest();
    }
    if(representationid >totarr[hinindex]){
        representationid = 1;
        hinindex++;
        adaptationid++;
    }
    if(adaptationid>totarr[hinindex2]){
        adaptationid = 1;
        hinindex2 += hinindex - 1;
        hinindex = hinindex2 + 1;
    }
    
    tree.setItemImage2( repid[counting],'progress3.gif','progress3.gif','progress3.gif');
    
    if(xmlDoc_progress == null)
        return;
    
    var CrossRepValidation=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CrossRepresentation");
    var ComparedRepresentations = xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("ComparedRepresentations");
    var HbbTVDVBComparedRepresentations = xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("HbbTVDVBComparedRepresentations");
    var SelectionSet=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("SelectionSet");
    var CmafProfile=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CMAFProfile");
    var CTAWAVESelectionSet=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CTAWAVESelectionSet");
    var CTAWAVEProfile=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("CTAWAVEPresentation");
    if ((CrossRepValidation.length!=0 && adaptationid>totarr[hinindex2]) || (ComparedRepresentations.length !=0 && representationid>totarr[hinindex]) || 
            (SelectionSet.length !=0 && adaptationid>totarr[hinindex2]) || (CmafProfile.length !=0 && adaptationid>totarr[hinindex2]) || 
            (HbbTVDVBComparedRepresentations.length !=0 && adaptationid>totarr[hinindex2]) ||
            (CTAWAVESelectionSet.length!=0 && adaptationid>totarr[hinindex2]) || (CTAWAVEProfile.length!=0 && adaptationid>totarr[hinindex2])){
        
        if(CrossRepValidation.length!=0 && adaptationid>totarr[hinindex2]){
            for(var i =1; i<=CrossRepValidation.length;i++)
            {
                if(CrossRepValidation[i-1].textContent=="noerror"){
                    tree.setItemImage2(adaptid[i-1],'right.jpg','right.jpg','right.jpg');
                    automate(adaptid[i-1],lastloc,"Cross-representation validation success");

                    tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                    lastloc++;
                }
                else{
                    tree.setItemImage2(adaptid[i-1],'button_cancel.png','button_cancel.png','button_cancel.png');
                    automate(adaptid[i-1],lastloc,"Cross-representation validation error");

                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(adaptid[i-1],lastloc,"log");
                    tree.setItemImage2( lastloc,'log.jpg','log.jpg','log.jpg');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_CrossInfofile.html");
                    lastloc++;
                }
            }
        }
        
        if( dvb == 1 || hbbtv == 1){
            if(HbbTVDVBComparedRepresentations.length!=0 && adaptationid>totarr[hinindex2]){
                for(var i =1; i<=HbbTVDVBComparedRepresentations.length;i++)
                {
                    if(HbbTVDVBComparedRepresentations[i-1].textContent=="noerror"){
                        automate(adaptid[i-1],lastloc,"DVB-HbbTV Compared representations validation success");
                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(adaptid[i-1],lastloc,"log");
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_hbbtv_dvb_compInfo.html");
                        lastloc++;
                    }
                    else if(HbbTVDVBComparedRepresentations[i-1].textContent=="warning"){
                        automate(adaptid[i-1],lastloc,"DVB-HbbTV Compared representations validation warning");
                        tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                        lastloc++;
                        
                        automate(adaptid[i-1],lastloc,"log");
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_hbbtv_dvb_compInfo.html");
                        lastloc++;
                    }
                    else{
                        automate(adaptid[i-1],lastloc,"DVB-HbbTV Compared representations validation error");
                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                        
                        automate(adaptid[i-1],lastloc,"log");
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_hbbtv_dvb_compInfo.html");
                        lastloc++;
                    }
                }
            }
        }
        
        if(cmaf == 1)
        {
            if(ComparedRepresentations.length !=0){
                for(var i =1; i<=ComparedRepresentations.length;i++){
                
                    if(ComparedRepresentations[i-1].textContent=="noerror"){
                        tree.setItemImage2(adaptid[i-1],'right.jpg','right.jpg','right.jpg');
                        automate(adaptid[i-1],lastloc,"CMAF Compared representations validation success");

                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                    }
                    else{
                        tree.setItemImage2(adaptid[i-1],'button_cancel.png','button_cancel.png','button_cancel.png');
                        automate(adaptid[i-1],lastloc,"CMAF Compared representations validation error");

                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(i-1)+ "_compInfo.html");
                        lastloc++;
                    }
                }
            }
            //Additions for CMAF Selection Set and Presentation profile.
            if(SelectionSet.length!=0  && adaptationid>totarr[hinindex2])
            {
                if(SelectionSet[0].textContent=="noerror"){
                        automate(perid[i-1],lastloc,"CMAF Selection Set");

                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile.html");
                        lastloc++;
                }
                else{
                        automate(perid[i-1],lastloc,"CMAF Selection Set");

                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile.html");
                        lastloc++;
                    }
            }
            if(CmafProfile.length!=0  && adaptationid>totarr[hinindex2])
            {
                if(CmafProfile[0].textContent=="noerror"){
                        automate(perid[i-1],lastloc,"CMAF Presentation Profile");

                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile.html");
                        lastloc++;
                }
                else{
                        automate(perid[i-1],lastloc,"CMAF Presentation Profile");

                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile.html");
                        lastloc++;
                }
            }
        }
        
        if(ctawave == 1)
        {
            //Additions for CTA WAVE Selection Set and Presentation profile.
            if(CTAWAVESelectionSet.length!=0  && adaptationid>totarr[hinindex2])
            {
                if(CTAWAVESelectionSet[0].textContent=="noerror"){
                        automate(perid[i-1],lastloc,"CTA WAVE Selection Set");
                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile_ctawave.html");
                        lastloc++;
                }
                else if(CTAWAVESelectionSet[0].textContent=="warning"){
                        automate(perid[i-1],lastloc,"CTA WAVE Selection Set");
                        tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile_ctawave.html");
                        lastloc++;
                }
                else{
                        automate(perid[i-1],lastloc,"CTA WAVE Selection Set");
                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"SelectionSet_infofile_ctawave.html");
                        lastloc++;
                }
            }
            if(CTAWAVEProfile.length!=0  && adaptationid>totarr[hinindex2])
            {
                if(CTAWAVEProfile[0].textContent=="noerror"){
                        automate(perid[i-1],lastloc,"CTA WAVE Presentation Profile");
                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile_ctawave.html");
                        lastloc++;
                }
                else if(CTAWAVEProfile[0].textContent=="warning"){
                        automate(perid[i-1],lastloc,"CTA WAVE Presentation Profile");
                        tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                        lastloc++;
                        
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile_ctawave.html");
                        lastloc++;
                }
                else{
                        automate(perid[i-1],lastloc,"CTA WAVE Presentation Profile");
                        tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                        lastloc++;
                    
                        automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                        kidsloc.push(lastloc);
                        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Presentation_infofile_ctawave.html");
                        lastloc++;
                }
            }
        }
        
        kidsloc.push(lastloc);
        var BrokenURL=xmlDoc_progress.getElementsByTagName("BrokenURL");
        if( BrokenURL != null && BrokenURL[0].textContent == "error")
        {
            urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"missinglink.html");

            automate(perid[i-1],lastloc,"Broken URL list");
            tree.setItemImage2(lastloc,'404.jpg','404.jpg','404.jpg');
            lastloc++; 
        }

        adjustFooter();
        periodid++;
        progress();
    }
    else
    {
        var AdaptXML=xmlDoc_progress.getElementsByTagName("Period")[periodid-1].getElementsByTagName("Adaptation"); 
        if(AdaptXML[adaptationid-1]== null)
            return;
        else if(AdaptXML[adaptationid-1].getElementsByTagName("Representation")[representationid-1] == null)
            return;
        else{   
            var RepXML=AdaptXML[adaptationid-1].getElementsByTagName("Representation")[representationid-1].textContent;
            if(RepXML == "")
                return;
            representationid++;
        }

        if(RepXML == "noerror")
            tree.setItemImage2( repid[counting],'right.jpg','right.jpg','right.jpg');
        else if(RepXML == "warning")
            tree.setItemImage2( repid[counting],'log.jpg','log.jpg','log.jpg');
        else
            tree.setItemImage2( repid[counting],'button_cancel.png','button_cancel.png','button_cancel.png');
        
        automate(repid[counting],lastloc,"log");
        tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
        kidsloc.push(lastloc);
        urlarray.push("temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(adaptationid-1)+"rep"+(representationid-2) + "log.html");
        lastloc++;

        var location = "temp/"+dirid+"/"+"Period"+(periodid-1)+"/"+"Adapt"+(adaptationid-1)+"rep"+(representationid-2) + "sample_data.xml";
        automate(repid[counting],lastloc,"Estimate bitrate");
        tree.setItemImage2( lastloc,'csh_winstyle/calculator.gif','csh_winstyle/calculator.gif','csh_winstyle/calculator.gif');
        kidsloc.push(lastloc);
        urlarray.push("Estimate.php?location=" + location );
        lastloc++;

        counting++;

        adjustFooter();
        progress();
    }
}
/////////////////////////Automation starts///////////////////////////////////////////////////
var urlarray=[];
function automate(y,x,stri)
{
    tree.insertNewChild(y,x,stri,0,0,0,0,'SELECT');
    fixImage(x.valueOf());
    x++;
    y++;
}
function brother(y,x)
{
    tree.insertNewNext(y,x,"New Node"+x,0,0,0,0,'SELECT');
    fixImage(x.valueOf());
    x++;
    y++;
}

function tonsingleclick(id)
{
    var urlto="";
    var position = kidsloc.indexOf(id);
    urlto=urlarray[position];
    
    if(urlto)
        window.open(urlto, "_blank");
}

 document.addEventListener("click",function()//delete the download button when anywhere in the dom file is left clicked.
    {
        if(buttoncontroller)
            downloadButtonHandle.remove();
        buttoncontroller=false;//because button is removed, change buttoncontroller to be false so that intvariable becomes false...
            //and function automaticalyy enters into if statement above and a button is created with next right click.
    }
        );
        
    document.addEventListener("contextmenu",function()//delete the download button when anywhere in the dom file is right clicked.
    {
        if(buttoncontroller)
            downloadButtonHandle.remove();
        buttoncontroller=false;//because button is removed, change buttoncontroller to be false so that intvariable becomes false...
            //and function automaticalyy enters into if statement above and a button is created with next right click.
    }
        );

function adjuststylein(id)//This function is created to adjust the style of the mouse cursor when it goes onto a Dtmlxtree element.
{
    var urlto="";
    var position = kidsloc.indexOf(id);
  
        if(position !== -1){//when id is not in the kidsloc, it returns -1. Therefore this if statement is created.
        urlto=urlarray[position];// url corresponding to this id, in other words the url of the webpage opened when this element is clicked. 
        if(urlto){//if url exists, change the cursor to pointer on this tree element.
        tree.style_pointer = "pointer";//This makes the cursor pointer when the pointer is exactly on this tree element(it works for texts) 
        document.getElementById("treeboxbox_tree").style.cursor = "pointer";//This makes the cursor pointer when the pointer is exactly on this tree element(it works for the icons)
        }
    }
        else{ //This makes the tree pointer when the cursor is exactly on this tree element
        tree.style_pointer = "default";//If no url exists corresponding to tree element make the cursor default
        document.getElementById("treeboxbox_tree").style.cursor = "default";//If no url exists corresponding to tree element make the cursor default
       }
}


function adjuststyleout(id)//This function is created to adjust the style of the mouse cursor when it leaves a tree element.
{
    var urlto="";
    var position = kidsloc.indexOf(id);
    if(position !== -1){
        urlto=urlarray[position];
        if(urlto){//If it leaves a tree element that has a corresponding url make the cursor style default.
        tree.style_pointer = "default";
        document.getElementById("treeboxbox_tree").style.cursor= "default";
        }
    }
}

var downloadButtonHandle=false;
var buttoncontroller=false;

function tonrightclick(id)
{
    var intvariable=buttoncontroller;
    $(document).ready(function()//cretaed to remove the custom right click popup menu from this page
{ 
    $(document).bind("contextmenu",function(e){
        return false;
    }); 
});
    aPos=event.clientX;//position of the x coordinate of the right click point in terms of pixels.
    bPos=event.clientY;//position of the y coordinate of the right click point in terms of pixels.
    
    var urlto="";
    var position = kidsloc.indexOf(id);
    urlto=urlarray[position];
    
    if(urlto){//if this tree element has a corresponding url
        var locarray = urlto.split("/");
        var htmlname = locarray[locarray.length-1];
        var textname = htmlname.split(".")[0] + ".txt";
        var textloc = window.location.href + "/../" + urlto.split(".")[0] + ".txt";
        var arrayurl= textloc.split(".");
    if(intvariable==false && arrayurl[3]!=="/Estimate"){//if intvariable is false execute 
        downloadButtonHandle = document.createElement("BUTTON");//create a dynamic button
        var t = document.createTextNode("click to download");//put this text in to the button
        downloadButtonHandle.appendChild(t);
        document.body.appendChild(downloadButtonHandle);//put button in the body of the document
        var str1=aPos+20 + "px";//x coordinate of the button is adjusted to be 20 pixel right of the click position
        var str2=bPos + "px";//y coordinate of the button is adjusted to be the same with the click position
        downloadButtonHandle.style.position = 'absolute';
        downloadButtonHandle.style.left = str1;//x coordinate assigned
        downloadButtonHandle.style.top =  str2;//y coordinate assigned
        downloadButtonHandle.style.background= "white";
       
        downloadButtonHandle.onmouseover = function(){
        downloadButtonHandle.style.background = "Gainsboro ";
        }
        downloadButtonHandle.onmouseout = function(){
        downloadButtonHandle.style.background = "white";
        }
    
        /*downloadButtonHandle : hover{ = "#F0F8FF";}*/
        downloadButtonHandle.onclick=function(){//when button is clicked, this function executes
        downloadLog(textloc,textname);
        downloadButtonHandle.remove();//after the file is downloaded, remove the button.
        }
    }
    else if(intvariable==false && arrayurl[3]==="/Estimate"){
        downloadButtonHandle.remove();
        buttoncontroller=false;
       }
    
        
        else{//if intvariable is correct it means there is already a button in the page so remove it.
        downloadButtonHandle.remove();
        }
        if(intvariable==false&& arrayurl[3]!=="/Estimate"){//int variable is created because both in the if statement and between the curly braces of if statement having buttoncontroller cretae some problems during new assignments. 
            buttoncontroller=true;//if intvariable is false, a button is created after the execution of rightclick. Therefore change the global variable buttoncontroller to be true so that intvariable becomes true...
            //and function automaticalyy enters into else statement above and button is removed with next right click.
            
        }       
        else{//if intvariable is correct, a button is removed after the execution of rightclick. Therefore change the global variable buttoncontroller to be false so that intvariable becomes false...
            //and function automaticalyy enters into if statement above and a button is created with next right click.
           buttoncontroller=false;
        }
              }
         else{//if any tree element, other than the ones which have corresponding ids, are right clicked remove the button 
        downloadButtonHandle.remove();
        buttoncontroller=false;//because button is removed, change buttoncontroller to be false so that intvariable becomes false...
            //and function automaticalyy enters into if statement above and a button is created with next right click.
    }
              
    }     

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

function finishTest()
{
    document.getElementById("btn8").disabled=false;
    document.getElementById("drop_div").disabled=false;

    clearInterval( progressTimer);
    clearInterval( progressSegmentsTimer);
    clearInterval( mpdTimer);
    clearInterval( treeTimer);
    
    //Open a new window for checking Conformance of Chained-to MPD (if present).
    xmlDoc_progress=loadXMLDoc("temp/"+dirid+"/progress.xml");
    if (xmlDoc_progress !== null){
        var MPDChainingUrl=xmlDoc_progress.getElementsByTagName("MPDChainingURL");

        if(MPDChainingUrl.length !== 0){   
            ChainedToUrl=MPDChainingUrl[0].childNodes[0].nodeValue;
            window.open("conformancetest.php?mpdurl="+ChainedToUrl);
        }
    }
    setStatusTextlabel("Conformance test completed");
}

function initVariables()
{
    urlarray.length = 0;
    kidsloc.length = 0;
    current = 0;
    dirid="";
    lastloc = 0;
    counting =0;
    representationid =1;
    adaptationid = 1;
    periodid = 1;
    hinindex = 2;
    hinindex2 = 1;
    numPeriods = 0;
    //uploaded = false;
    dynamicsegtimeline = false;
    segmentListExist = false;
    
    mpd_node_index = 0;
    mpdresult_x = 2;
    mpdresult_y = 1;
    branch_added = [0, 0, 0, 0];
    shouldFinishTest = false;
}

function setUpTreeView()
{
    if (typeof tree === "undefined"){		
    }
    else{
        tree.deleteChildItems(0);
        tree.destructor(); 
    }

    tree = new dhtmlXTreeObject('treeboxbox_tree', '100%', '100%', 0);
    tree.setOnClickHandler(tonsingleclick);
    tree.setOnRightClickHandler(tonrightclick);
    tree.setSkin('dhx_skyblue');
    tree.setImagePath("img/");
    tree.enableDragAndDrop(true);
    tree.attachEvent("onMouseIn", function(id){adjuststylein(id);});//Dhtmlx onMouseIn function is customized. 
    tree.attachEvent("onMouseout", function(id){adjuststyleout(id);});//Dhtmlx onMouseout function is customized.

}

function setStatusTextlabel(textToSet)
{
    status = textToSet;
    if( numPeriods > 1 && !ctawave)
        status = status + "<br><font color='red'> MPD with multiple Periods (" + numPeriods + "). Only segments of the current period were checked.</font>";
    if( dynamicsegtimeline)
        status = status + "<br><font color='red'> Segment timeline for type dynamic is not supported, only MPD will be tested. </font>";
    if(segmentListExist)
        status = status + "<br><font color='red'> SegmentList is not supported, only MPD will be tested. </font>";
    if(ChainedToUrl)
        status = status + "<br><font color='red'> Chained-to MPD conformance is opened in new window. </font>";

    document.getElementById("par").innerHTML=status;
    document.getElementById('par').style.visibility='visible';
}

function UrlExists(url, cb){
    jQuery.ajax({
        url:      url,
        dataType: 'text',
        type:     'GET',
        complete:  function(xhr){
            if(typeof cb === 'function')
               cb.apply(this, [xhr.status]);
        }
    });
}

function adjustFooter(){
    var docHeight = Math.max( document.body.scrollHeight, document.documentElement.scrollHeight,
                              document.body.offsetHeight, document.documentElement.offsetHeight,
                              document.body.clientHeight, document.documentElement.clientHeight
                    );
    var footerHeight = $('.site-footer').height();
    var footerTop = $('.site-footer').position().top + footerHeight;
    
    if (footerTop < docHeight) {
     $('.site-footer').css('margin-top', 1.5*footerHeight + (docHeight - footerTop) + 'px');
    }
}

function downloadLog(url, name){
    var element = document.getElementById("downloadpar");
    element.href = url;
    element.download = name;
    
    document.querySelector('#downloadpar').click();
}
</script>

<script>
    (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

    ga('create', 'UA-48482208-1', 'dashif.org');
    ga('send', 'pageview');
</script>

<footer class="site-footer">
    <center> <p id="footerVersion"></p>
        <p><a target="_blank" href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/issues"><b>Report issue</b></a></p>
    </center>
    <center> <p>
            <a target="_blank" href="https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/"><b>GitHub</b></a></p>
    </center>
</footer>

</body>
</html>
