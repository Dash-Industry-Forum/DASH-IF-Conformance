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
        <title> HLS Conformance Test</title>
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
    <script type="text/javascript" src="tree/dhtmlxTree/codebase/dhtmlxcommon.js"></script>
    <script type="text/javascript"  src="tree/dhtmlxTree/codebase/dhtmlxtree.js"></script>
    <script type="text/javascript" src="tree/dhtmlxTree/codebase/ext/dhtmlxtree_json.js"></script>
    
<?php 
    if(isset($_REQUEST['mpdurl']))
    {
        $url = $_REQUEST['mpdurl'];     // To get url from POST request.
    }
    else
        $url = "";
?>

<script type="text/javascript">

    var url = "";
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
<div class="page-wrap">    
    <div class="container">
        <button type="submit" class="btn btn-info" id="settings_button" data-toggle="collapse" data-target="#demo" >
        <img src="img/settings.jpg" class="settings_img" id="settings_img"  width="45" height="45" />
        </button>  
        <div id="demo" class="collapse">    
            <legend>Enforce profile(s):</legend>
            <div data-role="controlgroup" id="cont">
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
    
    <div id="progressbar" style="width:100px;background:#FFFFF;"></div>

    <div id = "not">
        <br>    <br>
    </div>

    <div id="to" >
        <p align="center"></p>
        <p id="par" class="sansserif" style="visibility:hidden;">Loading....</p>
    </div>

    <table>
        <tr>
            <td valign="top">
                <div id="treeboxbox_tree" style="background-color:#0000;overflow:hidden;border :none; "></div>
                <a id="downloadpar" href="" download="" style="visibility:hidden;"></a>
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
var hinindex = 1;
var repid =[];	
var totarr = [];
var adaptid=[];
var file,fd,xhr;
var uploaded = false;
var SessionID = "id"+Math.floor(100000 + Math.random() * 900000);
var totarrstring='';
var xmlDoc_progress;
var progressSegmentsTimer;
var pollingTimer;
var cmaf = 0;
var ctawave=0;
var TotalAdaptRep_count = 0;
var crossValidation = 0;

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
                
                var progressText;
                if (lastRep == 1 && lastAdapt == 1 && progressPercent == 0 && dataDownloaded == 0 && dataProcessed == 0) //initial state
                    progressText = "Processing, please wait...";
                else
                    progressText = "Processing Track "+lastRep+" in Tag "+lastAdapt+", "+progressPercent+"% done ( "+dataDownloaded+" KB downloaded, "+dataProcessed+" MB processed )";
		
                document.getElementById("par").innerHTML=progressText;
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
 
    if (uploaded===true)
	url="upload";
    
    var stringurl = [];
	
    stringurl[0] = url;
    if($("#cmafprofile").is(':checked'))
        cmaf = 1;
    if($("#ctawaveprofile").is(':checked'))
        ctawave = 1;
    
    stringurl[1]=cmaf;
    stringurl[2]=ctawave;
    
    initVariables();
    setUpTreeView();
    setStatusTextlabel("Processing...");
    document.getElementById("btn8").disabled="true";
    document.getElementById("drop_div").disabled="true";
    dirid="id"+Math.floor((Math.random() * 10000000000) + 1);
    
    if(uploaded===true){ // In the case of file upload.
        fd.append("foldername", dirid);
        fd.append("urlcodehls", JSON.stringify(stringurl));
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
                 $.post("../Utils/Process.php",{urlcodehls:JSON.stringify(stringurl),sessionid:JSON.stringify(SessionID),foldername: dirid});
             }
             else{ //if(urlStatus === 404){
                window.alert("Error loading the M3U8, please check the URL.");
                clearInterval( pollingTimer);	
                finishTest(); 
             }
         });
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
        var allDownloadComplete=xmlDoc_progress.getElementsByTagName("allDownloadComplete");

    var totarrstring = allDownloadComplete[0].nodeValue;
    if (totarrstring == '') //Check for the error in segment download.   
        return;
    
    //Get Conformance results from progress.xml file.
    var ResultXML=xmlDoc_progress.getElementsByTagName("Results");
    if(ResultXML.length==0)
        return;
    
    var x=2;
    var y=1;

    var currentpath = window.location.pathname;
    currentpath = currentpath.substring(0, currentpath.lastIndexOf('/'));

    var childno=1;
    
    //Get the number of AdaptationSets, Representations and Periods.   
    var  Treexml=xmlDoc_progress.getElementsByTagName("Representation");
    if (Treexml.length==0){
        var complete=xmlDoc_progress.getElementsByTagName("completed");
        if(complete[0].textContent == "true"){
            clearInterval( pollingTimer);
            finishTest();
        }
        return;
    }else{
        var Periodxml=xmlDoc_progress.getElementsByTagName("Period"); 
        Adapt_count= Periodxml[0].childNodes.length;
        var AdaptRepPeriod_count=Adapt_count;
        TotalAdaptRep_count = AdaptRepPeriod_count;
        var Adaptxml=xmlDoc_progress.getElementsByTagName("Adaptation");
        for (var v=0; v<Adapt_count; v++){
            TotalAdaptRep_count = TotalAdaptRep_count + Adaptxml[v].getElementsByTagName("Representation").length;
            AdaptRepPeriod_count=AdaptRepPeriod_count+" "+Adaptxml[v].getElementsByTagName("Representation").length;
        }
    }
    
    tree.loadJSONObject({
        id: 0,
        item: [{
            id: 1,
            text: "HLS"
        }]
    });
    totarr=AdaptRepPeriod_count.split(" ");
    var AdaptXML=xmlDoc_progress.getElementsByTagName("Adaptation");
    lastloc = TotalAdaptRep_count + 2;
    for(var i=0;i<totarr[0];i++)
    {
        automate(y,x,"SwitchingSet "+(i+1));
        adaptid.push(x);
        //tree.setItemImage2( x,'adapt.jpg','adapt.jpg','adapt.jpg');
        
        for(var j=0;j<totarr[childno];j++)
        {
            automate(x,x+j+1,"Track "+(j+1));
            repid.push(x+j+1);
            
            var RepXML=AdaptXML[i].getElementsByTagName("Representation")[j].textContent;
            if(RepXML == "noerror")
                tree.setItemImage2( repid[counting],'right.jpg','right.jpg','right.jpg');
            else if(RepXML == "warning")
                tree.setItemImage2( repid[counting],'log.jpg','log.jpg','log.jpg');
            else
                tree.setItemImage2( repid[counting],'button_cancel.png','button_cancel.png','button_cancel.png');
            
            automate(repid[counting],lastloc,"log");
            tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
            kidsloc.push(lastloc);
            urlarray.push("temp/"+dirid+"/"+ "Adapt"+(i)+"rep"+(j) + "log.html");
            lastloc++;
            counting++;
            adjustFooter();
        }

        childno++;
        x=x+j;
        x++;
    }
    
    lastloc++;
    crossValidation = 1;
    
    clearInterval( pollingTimer);
    progressSegmentsTimer = setInterval(function(){progress()},400);
    document.getElementById('par').style.visibility='visible';
}

function progress()  //Progress of Segments' Conformance
{
    xmlDoc_progress=loadXMLDoc("temp/"+dirid+"/progress.xml");
    if(xmlDoc_progress == null)
        return;
    
    if(cmaf == 1 || ctawave == 1){
        //tree.setItemImage2( repid[counting],'progress3.gif','progress3.gif','progress3.gif');
        var ComparedRepresentations = xmlDoc_progress.getElementsByTagName("ComparedRepresentations");
        var SelectionSet=xmlDoc_progress.getElementsByTagName("SelectionSet");
        var CmafProfile=xmlDoc_progress.getElementsByTagName("CMAFProfile");
        var CTAWAVESelectionSet=xmlDoc_progress.getElementsByTagName("CTAWAVESelectionSet");
        var CTAWAVEProfile=xmlDoc_progress.getElementsByTagName("CTAWAVEPresentation");
        if (crossValidation == 1 && ((ComparedRepresentations.length !=0) || (SelectionSet.length !=0) || (CmafProfile.length !=0) ||
            (CTAWAVESelectionSet.length !=0) || (CTAWAVEProfile.length !=0))){
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
                        urlarray.push("temp/"+dirid+"/"+ "Adapt"+(i-1)+ "_compInfo.html");
                        lastloc++;
                    }
                }
            }
            //Additions for CMAF Selection Set and Presentation profile.
            if(SelectionSet.length!=0  && adaptationid>totarr[0]){
                if(SelectionSet[0].textContent=="noerror"){
                    automate(1,lastloc,"CMAF Selection Set");

                    tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                    lastloc++;
                }
                else{
                    automate(1,lastloc,"CMAF Selection Set");

                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "SelectionSet_infofile.html");
                    lastloc++;
                }
            }
            if(CmafProfile.length!=0  && adaptationid>totarr[0]){
                if(CmafProfile[0].textContent=="noerror"){
                        automate(1,lastloc,"CMAF Presentation Profile");

                        tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                        lastloc++;
                }
                else{
                    automate(1,lastloc,"CMAF Presentation Profile");

                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "Presentation_infofile.html");
                    lastloc++;
                }
            }
            if(CTAWAVESelectionSet.length!=0){
                if(CTAWAVESelectionSet[0].textContent=="noerror"){
                    automate(1,lastloc,"CTA WAVE Selection Set");
                    tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                    lastloc++;
                    
                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "SelectionSet_infofile_ctawave.html");
                    lastloc++;
                }
                else if(CTAWAVESelectionSet[0].textContent=="warning"){
                    automate(adaptid[i-1],lastloc,"CTA WAVE Selection Set");
                    tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "SelectionSet_infofile_ctawave.html");
                    lastloc++;
                }
                else{
                    automate(1,lastloc,"CTA WAVE Selection Set");
                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "SelectionSet_infofile_ctawave.html");
                    lastloc++;
                }
            }
            if(CTAWAVEProfile.length!=0){
                if(CTAWAVEProfile[0].textContent=="noerror"){
                    automate(1,lastloc,"CTA WAVE Presentation Profile");
                    tree.setItemImage2(lastloc,'right.jpg','right.jpg','right.jpg');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "Presentation_infofile_ctawave.html");
                    lastloc++;
                }
                else if(CTAWAVEProfile[0].textContent=="warning"){
                    automate(1,lastloc,"CTA WAVE Presentation Profile");
                    tree.setItemImage2(lastloc,'log.jpg','log.jpg','log.jpg');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "Presentation_infofile_ctawave.html");
                    lastloc++;
                }
                else{
                    automate(1,lastloc,"CTA WAVE Presentation Profile");
                    tree.setItemImage2(lastloc,'button_cancel.png','button_cancel.png','button_cancel.png');
                    lastloc++;

                    automate(lastloc-1,lastloc,"log");//adaptid[i-1]
                    tree.setItemImage2( lastloc,'csh_winstyle/iconText.gif','csh_winstyle/iconText.gif','csh_winstyle/iconText.gif');
                    kidsloc.push(lastloc);
                    urlarray.push("temp/"+dirid+"/"+ "Presentation_infofile_ctawave.html");
                    lastloc++;
                }
            }

            kidsloc.push(lastloc);
            var BrokenURL=xmlDoc_progress.getElementsByTagName("BrokenURL");
            if( BrokenURL != null && BrokenURL.length != 0){//if(locations[locations.length-1]!="noerror")
                if(BrokenURL[0].textContent == "error"){
                    urlarray.push("temp/" + dirid+"/missinglink.html");//urlarray.push(locations[locations.length-1]);

                    automate(1,lastloc,"Broken URL list");
                    tree.setItemImage2(lastloc,'404.jpg','404.jpg','404.jpg');
                    lastloc++; 
                }
            }

            adjustFooter();
            clearTimeout(progressTimer);
            setStatusTextlabel("Conformance test completed");
            finishTest();
        }
        else{
            progress();
        }
    }
    else{
        adjustFooter();
        clearTimeout(progressTimer);
        setStatusTextlabel("Conformance test completed");
        finishTest();
    }
}
/////////////////////////Automation starts///////////////////////////////////////////////////
var urlarray=[];
//var x=2;
//var y=1;
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

function tonrightclick(id)
{
    var urlto="";
    var position = kidsloc.indexOf(id);
    urlto=urlarray[position];
    //console.log(position);
    //console.log(urlto);
    
    if(urlto){
        var locarray = urlto.split("/");
        var htmlname = locarray[locarray.length-1];
        var textname = htmlname.split(".")[0] + ".txt";

        var textloc = window.location.href + "/../" + urlto.split(".")[0] + ".txt";
        downloadLog(textloc, textname);
    }
}
//var parsed;
//var uploaded = "false";

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
    hinindex = 1;
    //uploaded = false;
    dynamicsegtimeline = false;
    segmentListExist = false;
}

function setUpTreeView()
{
    if (typeof tree === "undefined") 
    {
//        console.log("tree:doesnt exist");				
    }
    else
    {
//        console.log("tree: exist");
        tree.deleteChildItems(0);
        tree.destructor(); 
    }

    tree = new dhtmlXTreeObject('treeboxbox_tree', '100%', '100%', 0);
    tree.setOnClickHandler(tonsingleclick);
    tree.setOnRightClickHandler(tonrightclick);
    tree.setSkin('dhx_skyblue');
    tree.setImagePath("img/");
    tree.enableDragAndDrop(true);
}

function setStatusTextlabel(textToSet)
{
    status = textToSet;
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
        <p><a target="_blank" href="https://github.com/DASHIndustryForum/Conformance-Software/issues"><b>Report issue</b></a></p>
    </center>
    <center> <p>
            <a target="_blank" href="https://github.com/DASHIndustryForum/Conformance-Software/"><b>GitHub</b></a></p>
    </center>
</footer>

</body>
</html>
