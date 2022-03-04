(function () {
    //initializing jQuery
    var jq = $.noConflict();
    
    //attaching stepper controller
    angular.module('ConformanceSoftware').controller('stepperController', function ($document, $window, $location, $interval, $scope, $q, $timeout, $mdDialog, $cookies, $mdSidenav, ngIntroService) {
        var vm = this;
        //Local Variables
        //stepper var
        vm.selected_file_method = "MPD_URL";
        vm.selectedStep = 0;
        vm.stepProgress = 1;
        vm.maxStep = 2;
        vm.stepData = [
            {
                step: 1,
                completed: false,
                optional: false,
                data: {}
            },
            {
                step: 2,
                completed: false,
                optional: false,
                data: {}
            }
        ];
        
        //getting cookies
        vm.stepData[0].data.file = "";
        vm.stepData[0].data.cmaf = $cookies.get('cmaf')=== "true"? true : false;
        vm.stepData[0].data.dvb2019 =  $cookies.get('dvb2019')=== "true"? true : false;
        vm.stepData[0].data.dvb2018 =  $cookies.get('dvb2018')=== "true"? true : false;
        vm.stepData[0].data.hbbTv = $cookies.get('hbbtv')=== "true"? true : false;
        vm.stepData[0].data.dashIf = $cookies.get('dashIf')=== "true"? true : false;
        vm.stepData[0].data.dashIfll = $cookies.get('dashIfll')=== "true"? true : false;
        vm.stepData[0].data.ctawave = $cookies.get('ctawave')=== "true"? true : false;
        vm.stepData[0].data.segmentvalidation =  $cookies.get('segmentvalidation')=== "true"? true : false;
        vm.isResultDrop = true;
        vm.isContentDrop = false;
        vm.isSummaryDrop = false;

        //conformance variables
        vm.mpdprocessed = false;
        vm.fd = new FormData();
        vm.SessionID = "";
        vm.dynamicsegtimeline = false;
        vm.segmentListExist = false;
        vm.ChainedToUrl;
        vm.progressXMLRequest;
        vm.progressXML;
        vm.progressTimer;
        vm.xmlDoc_progress;
        vm.treeTimer;
        vm.mpdresult_x = 2;
        vm.mpdresult_y = 1;
        vm.kidsloc = [];
        vm.log_branchName = "mpd log";
        vm.shouldFinishTest = false;
        vm.totarr = [];
        vm.totarrstring = [];
        vm.repid = [];
        vm.perid = [];
        vm.adaptid = [];
        vm.counter = [];
        vm.periodid = 1;
        vm.holder = 1;
        vm.lastloc = 0;
        vm.hinindex = 2;
        vm.hinindex2 = 1;
        vm.numPeriods = 0;
        vm.representationid = 1;
        vm.adaptationid = 1;
        vm.adaptholder = [];
        vm.entered_cross = false;
        vm.entered_hbb = false;
        vm.entered_cmaf = false;
        vm.entered_dashifll = false;
        vm.progressSegmentsTimer;
        vm.branchName = [
            "XLink resolving",
            "MPD validation",
            "Schematron validation",
            "DASH-IF validation",
            "LL DASH-IF validation",
            "HbbTV DVB validation"
        ];
        vm.downloadButtonHandle = false;
        vm.buttoncontroller = false;
        vm.xmlDoc_mpdresult;
        vm.dirid = "";
        vm.counting = 0;
        vm.pollingTimer;
        vm.mpdTimer;
        vm.profileTimer;
        vm.urlarray = [];
        vm.now = new Date();
        vm.exp = new Date(vm.now.getFullYear()+1, vm.now.getMonth(), vm.now.getDate());
        vm.repCounter = -1;
        vm.prevRepNum = 0;
        vm.segmentPercentage = 0;
        vm.mpdPercentage = 0;
        vm.flag = 0;
        vm.a="";
        vm.b="";
        vm.Adaptelem;
        vm.flag1 = 0;
        
        //getting query strings
        vm.stepData[0].data.url = $location.search().mpdurl;
        vm.stepData[0].data.segmentvalidation = $location.search().segval ? true : false;
        vm.stepData[0].data.dashIf = $location.search().dashif ? true : false;
        vm.stepData[0].data.dashIfll = $location.search().dashifll ? true : false;
        vm.stepData[0].data.dvb2018 = $location.search().dvb2018 ? true : false;
        vm.stepData[0].data.dvb2019 = $location.search().dvb2019 ? true : false;
        vm.stepData[0].data.dvb = vm.stepData[0].data.dvb2018 || vm.stepData[0].data.dvb2019;
        vm.stepData[0].data.hbbTv = $location.search().hbbtv ? true : false;
        vm.stepData[0].data.cmaf = $location.search().cmaf ? true : false;
        vm.stepData[0].data.ctawave = $location.search().ctawave ? true : false;
        vm.stepData[0].data.schema = $location.search().schema;
        vm.stepData[0].data.autorun = $location.search().autorun ? true : false;
        
        //intro part
        if( localStorage.getItem('Tour') === 'Completed'){
        }
        else
        {
            //$scope.CallMe();
        }
        
        $scope.IntroOptions = {
            steps:[
                {
                    element: '#step1-1',
                    intro: "This is DASH-IF CONFORMANCE TOOL<br>The tool checks if<ul><li>the provided MPEG-DASH MPD manifest</li><li>media content in the MPD</li></ul><br>conform to DASH-related media specifications.",
                    position:'bottom'
                },
                {
                    element: '#step1-2',
                    intro: "You can start conformance testing in 3 easy steps:<ol><li>Provide MPD</li><li>Include additional tests</li><li>Click 'RUN'</li></ol>",
                    position: 'top'
                },
                {
                    element: '#step1-3',
                    intro: "For conformance testing you should provide an MPD file in one of 3 ways:<ul><li>MPD URL</li><li>Drag & drop</li><li>Upload</li></ul>",
                    position: 'bottom'
                },
                {
                    element: '#step2-1',
                    intro: 'By default, conformance software tool validates the provided MPD against<ul><li>ISO/IEC 23009-1 DASH MPD and Segment Formats as well as</li><li>The profiles provided in the @profiles attribute in the given MPD that the tool supports</li></ul>only at MPD level that includes<ul><li>Xlink Resolving</li><li>DASH Schema Validation</li><li>MPD-Level Checks</li></ul>',
                    position: 'bottom'

                },
                {
                    element: '#step2-2',
                    intro: "Optionally, you can include additional tests as below:<ul><li><strong>Segment Validation</strong></li><li><strong>DASH-IF</strong></li><li><strong>DVB</strong></li><li><strong>HbbTV</strong></li><li><strong>CMAF</strong></li><li><strong>CTA WAVE</strong></li></ul>For further details on each test you can check the <img src='dash_img/help.svg'/> icon",
                    position:'right'
                },
                {
                    element: '#run',
                    intro: 'To start conformance testing, simply click "RUN"',
                    position:'left'
                }
            ],
            showStepNumbers: false,
            showBullets: true,
            exitOnOverlayClick: false,
            exitOnEsc: false,
            nextLabel: '<span style="color: rgb(2,119,189)">Next</span>',
            prevLabel: '<span style="color: rgb(2,119,189)">Previous</span>',
            skipLabel: '<span style="color: rgb(2,119,189)">Exit</span>',
            doneLabel: '<span style="color: rgb(2,119,189)"><strong>Finish</strong></span>',
            hidePrev: true,
            hideNext: true,
            disableInteraction: true,
            scrollToElement: true
        };

        $scope.BeforeChangeEvent = function(targetElement, scope){
            console.log("Change Event before called");
            console.log(targetElement); //The target element
            console.log(this); //The IntroJS object
            
            if(targetElement.id == "step1-1"){
                //
            }
        };

        $scope.AfterChangeEvent = function(){
        };

        $scope.CompletedEvent = function(){
            localStorage.setItem('Tour', 'Completed');
        };

        $scope.ExitEvent= function(){
             localStorage.setItem('Tour', 'Completed');
        };

        $scope.temp=-100;
        $scope.isSidenavOpen="false";
        $scope.$watch('isSidenavOpen',function(isSidenavOpen){
            if(isSidenavOpen){
                if($scope.temp==100){ 
                    $document[0].getElementsByTagName("BODY")[0].style.overflow="hidden";
                }
            }
            else{
                if($scope.temp==-100){
                    $document[0].getElementsByTagName("BODY")[0].style.overflow="visible";
                } 
            }
        });
        $scope.openSideNavPanel = function() {
            $scope.temp=100;
            $mdSidenav('left').open();
        };
        $scope.closeSideNavPanel = function() {
            $mdSidenav('left').close();
            $scope.temp=-100;
        };
        $scope.GoToStart = function() {
            if($window.location.href.indexOf('/start') === -1){
                $window.open('#/start/', "_self");
            }
        };

        //stepper functionality
        //step enable check
        vm.enableNextStep = function nextStep() {
            //do not exceed into max step
            if (vm.selectedStep >= vm.maxStep) {
                return;
            }
            //do not increment vm.stepProgress when submitting from previously completed step
            if (vm.selectedStep == vm.stepProgress - 1) {
                vm.stepProgress = vm.stepProgress + 1;
            }
            vm.selectedStep = vm.selectedStep + 1;
        };
        
        //takes to prev step
        vm.moveToPreviousStep = function moveToPreviousStep() {
            if (vm.selectedStep > 0) {
                vm.selectedStep = vm.selectedStep - 1;
            }
        };
        
        //submits current step
        vm.submitCurrentStep = function submitCurrentStep(stepData, isSkip) {
            var deferred = $q.defer();
            //            vm.showBusyText = true;
            //console.log('On before submit');
            if (!stepData.completed && !isSkip) {
                //simulate $http
                $timeout(function () {
                    //                    vm.showBusyText = false;
                    //console.log('On submit success');
                    deferred.resolve({
                        status: 200,
                        statusText: 'success',
                        data: {}
                    });
                    //move to next step when success
                    stepData.completed = true;
                    vm.enableNextStep();
                }, 1000);
            } else {
                //                vm.showBusyText = false;
                vm.enableNextStep();
            }
        };
        
        //delete uploaded file
        vm.deleteFile = function deleteFile() {
            $document[0].getElementById('fileTag').style.display = 'none';
            $document[0].getElementById('triggerUpload').disabled = false;
            $document[0].getElementById("triggerUpload").style.color = '#007bff';
            $document[0].getElementsByClassName("urlContainer")[0].style.display = 'block';
            $document[0].getElementById('upload').value = '';
            $document[0].getElementById('label-text').innerHTML = 'Enter URL or drag FILE here';
            vm.stepData[0].data.file = '';
            vm.selected_file_method = 'MPD_URL';
            if(jq(window).width()>= 600){
                //                document.getElementById("uploadContainer").style.marginLeft = '0';
            }
        }
        
        //Changes on Stop and Go back button 
        vm.buttonChange=function buttonChange(){
            if(vm.stepData[1].completed==false){
                vm.finishTest();
            }
            else{
                vm.stepData[1].completed= false;
                vm.moveToPreviousStep();
                $document[0].getElementById('customButton').innerHTML = 'STOP';
                $document[0].getElementById('profile').style.display = 'none';
                $document[0].getElementById('profileHead').style.display = 'none';
                $document[0].getElementById("profile").innerHTML = "";
                $document[0].getElementById('dynamicMain').style.display = 'none';
                $document[0].getElementById('par').style.visibility = 'hidden';
                $document[0].getElementById('featureIframe').src = "";
                $document[0].getElementById("resultItem").classList.add("active");
                $document[0].getElementById("contentItem").classList.remove("active");
                $document[0].getElementById("summaryItem").classList.remove("active");
                $document[0].getElementById('listName').style.display = 'none';
                $document[0].getElementById('insetDivider').style.display = 'none';
                $document[0].getElementById('profileDivider').style.display = 'none';
                $document[0].getElementById('waitMessage').style.display = 'block';
            }
        }
        
        //When clicked on RUN
        vm.submitRun = function submit() {        
            vm.mpdprocessed = false;

            if ($window.location.protocol.indexOf('https') != -1) {
                if (vm.stepData[0].data.url && vm.stepData[0].data.url.indexOf('https') == -1) {
                    $document[0].getElementById("alertMessage").innerHTML = "HTTP content is detected. <span style=\"color:red\"><b>This secure (HTTPS) site cannot process the HTTP content.</b></span> If you wish to continue using this content, please use <a target=\"_blank\" href=\"http://54.72.87.160/conformance/current/DASH-IF-Conformance/Conformance-Frontend/Conformancetest.php\">HTTP-based interface</a> instead.";
                    return false;
                }
            }

            vm.stringurl = [];
            //checking url vs upload
            if (vm.selected_file_method == "MPD_URL") {vm.stringurl[0] = vm.stepData[0].data.url;} else {vm.stringurl[0] = "upload";}
            //getting additional options 
            if (vm.stepData[0].data.segmentvalidation) {vm.stringurl[1] = 0;} else {vm.stringurl[1] = 1;}
            if (vm.stepData[0].data.cmaf) {vm.stringurl[2] = 1;} else {vm.stringurl[2] = 0;}
            if (vm.stepData[0].data.dvb2019) {vm.stringurl[3] = 1;} else {vm.stringurl[3] = 0;}
            if (vm.stepData[0].data.dvb2018) {vm.stringurl[4] = 1;} else {vm.stringurl[4] = 0;}
            if (vm.stepData[0].data.hbbTv) {vm.stringurl[5] = 1;} else {vm.stringurl[5] = 0;}
            if (vm.stepData[0].data.dashIf) {vm.stringurl[6] = 1;} else {vm.stringurl[6] = 0;}
            if (vm.stepData[0].data.ctawave) {vm.stringurl[7] = 1;} else {vm.stringurl[7] = 0;}
            if (vm.stepData[0].data.dashIfll) {vm.stringurl[8] = 1;} else {vm.stringurl[8] = 0;}
            vm.stepData[0].data.dvb = vm.stepData[0].data.dvb2018 || vm.stepData[0].data.dvb2019;
            
            // getting additional schema option
            vm.stringurl[9] = vm.stepData[0].data.schema;
            if(vm.stepData[0].data.schema){
                var filename = vm.stepData[0].data.schema;
                if (filename.split('.').pop() != 'xsd'){
                    $window.alert("Provided DASH Schema is not an XSD file, default DASH schema will be used");
                }
                else{
                    var fileRequest = new XMLHttpRequest();
                    fileRequest.open("GET", vm.stepData[0].data.schema, false);
                    fileRequest.send(null);
                    if (fileRequest.status !== 200) { // analyze HTTP status of the response
                        $window.alert("Provided DASH Schema is not found, default DASH schema will be used");
                    }
                }
            }
            
            //setting cookies
            $cookies.put('segmentvalidation',vm.stepData[0].data.segmentvalidation,{expires: vm.exp});
            $cookies.put('cmaf',vm.stepData[0].data.cmaf,{expires: vm.exp});
            $cookies.put('dvb2019',vm.stepData[0].data.dvb2019,{expires: vm.exp});
            $cookies.put('dvb2018',vm.stepData[0].data.dvb2018,{expires: vm.exp});
            $cookies.put('hbbtv',vm.stepData[0].data.hbbTv,{expires: vm.exp});
            $cookies.put('dashIf',vm.stepData[0].data.dashIf,{expires: vm.exp});
            $cookies.put('ctawave',vm.stepData[0].data.ctawave,{expires: vm.exp});
            $cookies.put('dashIfll',vm.stepData[0].data.dashIfll,{expires: vm.exp});
            
            //initialting variables
            vm.initVariables();
            //setting up tree
            vm.setUpTreeView();
            
            //Generate a random folder name for results in "temp" folder
            vm.SessionID = "id" + Math.floor(100000 + Math.random() * 900000);
            vm.dirid = "id" + Math.floor((Math.random() * 10000000000) + 1);
            
            if (vm.selected_file_method == "MPD_File_Upload") { // In the case of file upload.
                var filename = vm.stepData[0].data.file.name;
                if (!(['mpd', 'txt', 'xml'].includes(filename.split('.').pop()))){
                    $window.alert("Provided file does not have an acceptable file extension, acceptable files are {mpd, xml, txt}");
                    vm.finishTest();
                    return false;
                }
                
                vm.fd.append("afile", vm.stepData[0].data.file);
                vm.fd.append("sessionid", JSON.stringify(vm.SessionID));
                vm.fd.append("foldername", vm.dirid);
                vm.fd.append("urlcode", JSON.stringify(vm.stringurl));
                jq.ajax({
                    type: "POST",
                    url: "../Utils/Process.php",
                    data: vm.fd,
                    contentType: false,
                    processData: false
                });
            } else { // Pass to server only, no JS response model.
                vm.UrlExists(vm.stringurl[0], function (urlStatus) {
                    if (urlStatus == 200 && vm.stringurl[0] != "") {
                        jq.post("../Utils/Process.php", {
                            urlcode: JSON.stringify(vm.stringurl),
                            sessionid: JSON.stringify(vm.SessionID),
                            foldername: vm.dirid
                        });
                    } else { //if(urlStatus == 404)
                        $window.alert("Error loading the MPD, please check the URL.");
                        $interval.cancel(vm.pollingTimer);
                        vm.finishTest();
                        return false;
                    }
                });
            }
            //Start polling of progress.xml for the progress percentage results.
            vm.progressTimer = $interval(function () {
                vm.progressupdate();
            }, 200);
            //Start polling of progress.xml for the MPD conformance results.
            vm.pollingTimer = $interval(function () {
                vm.pollingProgress();
            }, 800); 
        };
        
        vm.initVariables = function initVariables() {
            vm.urlarray.length = 0;
            vm.kidsloc.length = 0;
            vm.dirid = "";
            vm.lastloc = 0;
            vm.counting = 0;
            vm.representationid = 1;
            vm.adaptationid = 1;
            vm.periodid = 1;
            vm.hinindex = 2;
            vm.hinindex2 = 1;
            vm.numPeriods = 0;
            vm.dynamicsegtimeline = false;
            vm.segmentListExist = false;
            vm.mpd_node_index = 0;
            vm.mpdresult_x = 2;
            vm.mpdresult_y = 1;
            vm.branch_added = [0, 0, 0, 0, 0, 0];
            vm.shouldFinishTest = false;
            vm.totalRep = 0;
            vm.repCounter = -1;
            vm.prevRepNum = 0;
            vm.segmentPercentage = 0;
            vm.mpdPercentage = 0;
            vm.flag = 0;
            vm.a="";
            vm.b="";
            vm.Adaptelem;
            vm.flag1 = 0;
        };

        vm.setUpTreeView = function setUpTreeView() {
            if (typeof vm.tree == "undefined") {} else {
                vm.tree.deleteChildItems(0);
                vm.tree.destructor();
            }
            vm.tree = new dhtmlXTreeObject('treeboxbox_tree', '100%', '100%', 0);
            vm.tree.setOnClickHandler(vm.tonsingleclick);
            vm.tree.setOnRightClickHandler(vm.tonrightclick);
            vm.tree.setSkin('dhx_skyblue');
            vm.tree.setImagePath("img/");
            vm.tree.enableDragAndDrop(true);
            vm.tree.attachEvent("onMouseIn", function (id) {
                vm.adjuststylein(id);
            }); //Dhtmlx onMouseIn function is customized. 
            vm.tree.attachEvent("onMouseout", function (id) {
                vm.adjuststyleout(id);
            }); //Dhtmlx onMouseout function is customized.
        };
        
        vm.generateFolderName = function generateFolderName() {
            var now = new Date();
            var folderName = "id" + Math.floor((Math.random() * 10000000000) + 1) + "_";
            folderName += (vm.stepData[0].data.segmentvalidation) ? "S" : "s";
            folderName += (vm.stepData[0].data.cmaf) ? "C" : "c";
            folderName += (vm.stepData[0].data.dvb) ? "V" : "v";
            folderName += (vm.stepData[0].data.hbbTv) ? "H" : "h";
            folderName += (vm.stepData[0].data.dashIf) ? "D" : "d";
            folderName += (vm.stepData[0].data.dashIfll) ? "L" : "l";
            folderName += (vm.stepData[0].data.dashIf) ? "W" : "w" + "_";
            folderName += now.getFullYear().toString();
            folderName += (now.getMonth()+1).toString();
            folderName += now.getDate().toString();
            folderName += now.getHours().toString(); 
            folderName += now.getMinutes().toString();
            folderName += now.getSeconds().toString() + ".";
            folderName += now.getMilliseconds().toString();
            
            return folderName;
        };

        vm.tonsingleclick = function tonsingleclick(id) {
            var urlto = "";
            var position = vm.kidsloc.indexOf(id);
            urlto = vm.urlarray[position];
            //it breaks if you add CALL as optional parameter in insertNewChild function of dhtmlx tree
            if (urlto){
                $window.open(urlto, "frontframe");
                $document[0].getElementById('backFrame').style.display = 'block';
                $document[0].getElementById('frontFrame').style.display = 'block';
                $document[0].getElementsByTagName('body')[0].style.overflow = 'hidden';

                $document[0].getElementById('overlayButton').style.display = 'block';
            }
        };

        vm.tonrightclick = function tonrightclick(id) {
            var intvariable = vm.buttoncontroller;
            jq($document).ready(function () //cretaed to remove the custom right click popup menu from this page
                                {
                jq($document).bind("contextmenu", function (e) {
                    return false;
                });
            });
            aPos = event.clientX; //position of the x coordinate of the right click point in terms of pixels.
            bPos = event.clientY; //position of the y coordinate of the right click point in terms of pixels.
            var scrollTop = jq($window).scrollTop();
            bPos = bPos + scrollTop;
            var urlto = "";
            var position = vm.kidsloc.indexOf(id);
            urlto = vm.urlarray[position];
            if (urlto) { //if this tree element has a corresponding url
                var locarray = urlto.split("/");
                var htmlname = locarray[locarray.length - 1];
                var textname = htmlname.split(".")[0] + ".txt";
                var textloc = $location.href + "/../" + urlto.split(".")[0] + ".txt";
                var arrayurl = textloc.split(".");
                if (intvariable == false && arrayurl[3] != "/Estimate") { //if intvariable is false execute 
                    vm.downloadButtonHandle = $document[0].createElement("BUTTON"); //create a dynamic button
                    var t = $document[0].createTextNode("click to download"); //put this text in to the button
                    vm.downloadButtonHandle.appendChild(t);
                    $document[0].body.appendChild(vm.downloadButtonHandle); //put button in the body of the document
                    var str1 = aPos + 20 + "px"; //x coordinate of the button is adjusted to be 20 pixel right of the click position
                    var str2 = bPos + "px"; //y coordinate of the button is adjusted to be the same with the click position
                    vm.downloadButtonHandle.style.position = 'absolute';
                    vm.downloadButtonHandle.style.left = str1; //x coordinate assigned
                    vm.downloadButtonHandle.style.top = str2; //y coordinate assigned
                    vm.downloadButtonHandle.style.background = "white";

                    vm.downloadButtonHandle.onmouseover = function () {
                        vm.downloadButtonHandle.style.background = "Gainsboro ";
                    }
                    vm.downloadButtonHandle.onmouseout = function () {
                        vm.downloadButtonHandle.style.background = "white";
                    }

                    /*downloadButtonHandle : hover{ = "#F0F8FF";}*/
                    vm.downloadButtonHandle.onclick = function () { //when button is clicked, this function executes
                        vm.downloadLog(textloc, textname);
                        vm.downloadButtonHandle.remove(); //after the file is downloaded, remove the button.
                    }
                } else if (intvariable == false && arrayurl[3] == "/Estimate") {
                    vm.downloadButtonHandle.remove();
                    vm.buttoncontroller = false;
                } else { //if intvariable is correct it means there is already a button in the page so remove it.
                    vm.downloadButtonHandle.remove();
                }
                if (intvariable == false && arrayurl[3] != "/Estimate") { //int variable is created because both in the if statement and between the curly braces of if statement having buttoncontroller cretae some problems during new assignments. 
                    vm.buttoncontroller = true; //if intvariable is false, a button is created after the execution of rightclick. Therefore change the global variable buttoncontroller to be true so that intvariable becomes true...
                    //and function automaticalyy enters into else statement above and button is removed with next right click.

                } else { //if intvariable is correct, a button is removed after the execution of rightclick. Therefore change the global variable buttoncontroller to be false so that intvariable becomes false...
                    //and function automaticalyy enters into if statement above and a button is created with next right click.
                    vm.buttoncontroller = false;
                }
            } else { //if any tree element, other than the ones which have corresponding ids, are right clicked remove the button 
                vm.downloadButtonHandle.remove();
                vm.buttoncontroller = false; //because button is removed, change buttoncontroller to be false so that intvariable becomes false...
                //and function automaticalyy enters into if statement above and a button is created with next right click.
            }

        };

        //Download report on right click
        vm.downloadLog = function downloadLog(url, name) {
            var element = $document[0].getElementById("downloadpar");
            element.href = url;
            element.download = name;
            $document[0].querySelector('#downloadpar').click();
        };

        //Adjust the style of the mouse cursor when it goes onto a Dtmlxtree element.
        vm.adjuststylein = function adjuststylein(id) {
            var urlto = "";
            var position = vm.kidsloc.indexOf(id);

            if (position != -1) { //when id is not in the kidsloc, it returns -1. Therefore this if statement is created.
                urlto = vm.urlarray[position]; // url corresponding to this id, in other words the url of the webpage opened when this element is clicked. 
                if (urlto) { //if url exists, change the cursor to pointer on this tree element.
                    vm.tree.style_pointer = "pointer"; //This makes the cursor pointer when the pointer is exactly on this tree element(it works for texts) 
                    $document[0].getElementById("treeboxbox_tree").style.cursor = "pointer"; //This makes the cursor pointer when the pointer is exactly on this tree element(it works for the icons)
                }
            } else { //This makes the tree pointer when the cursor is exactly on this tree element
                vm.tree.style_pointer = "default"; //If no url exists corresponding to tree element make the cursor default
                $document[0].getElementById("treeboxbox_tree").style.cursor = "default"; //If no url exists corresponding to tree element make the cursor default
            }
        };

        //Adjust the style of the mouse cursor when it leaves a tree element.
        vm.adjuststyleout = function adjuststyleout(id) {
            var urlto = "";
            var position = vm.kidsloc.indexOf(id);
            if (position != -1) {
                urlto = vm.urlarray[position];
                if (urlto) { //If it leaves a tree element that has a corresponding url make the cursor style default.
                    vm.tree.style_pointer = "default";
                    $document[0].getElementById("treeboxbox_tree").style.cursor = "default";
                }
            }
        };
        
        vm.UrlExists = function UrlExists(url, cb) {
            jq.ajax({
                url: url,
                dataType: 'text',
                type: 'GET',
                complete: function (xhr) {
                    if (typeof cb == 'function')
                        cb.apply(this, [xhr.status]);
                }
            });
        };

        vm.progressupdate = function progressupdate() {
            vm.progressXMLRequest = vm.createXMLHttpRequestObject();
            if (vm.progressXMLRequest) {
                try {// try to connect to the server
                    var progressDocURL = 'temp/' + vm.dirid + '/progress.xml';
                    var now = new Date();
                    vm.progressXMLRequest.open("GET", progressDocURL += (progressDocURL.match(/\?/) == null ? "?" : "&") + now.getTime(), false);
                    //initiate server request, trying to bypass cache using tip from 
                    //https://developer.mozilla.org/es/docs/XMLHttpRequest/Usar_XMLHttpRequest#Bypassing_the_cache,
                    vm.progressXMLRequest.onreadystatechange = vm.progressEventHandler;
                    vm.progressXMLRequest.send(null);
                } catch (e) {
                    ; 
                }
            }
        };

        vm.createXMLHttpRequestObject = function createXMLHttpRequestObject() {
            var xmlHttp;
            try {
                xmlHttp = new XMLHttpRequest(); // create an XMLHttpRequest object
            } catch (e) {
                try // assume IE6 or older
                {
                    xmlHttp = new ActiveXObject("Microsoft.XMLHttp");
                } catch (e) {}
            }
            if (!xmlHttp)
                $window.alert("Error creating the XMLHttpRequest object.");
            else
                return xmlHttp;
        };

        vm.progressEventHandler = function progressEventHandler() {
            if (vm.progressXMLRequest.readyState == 4) { // continue if the process is completed
                if (vm.progressXMLRequest.status == 200) { // continue only if HTTP status is "OK" 
                    try {
                        var response = vm.progressXMLRequest.responseXML;
                        vm.progressXML = vm.progressXMLRequest.responseXML.documentElement;

                        var progressPercent = vm.progressXML.getElementsByTagName("percent")[0].childNodes[0].nodeValue;
                        var dataProcessed = vm.progressXML.getElementsByTagName("dataProcessed")[0].childNodes[0].nodeValue;
                        var dataDownloaded = vm.progressXML.getElementsByTagName("dataDownloaded")[0].childNodes[0].nodeValue;
                        dataProcessed = Math.floor(dataProcessed / (1024 * 1024));
                        dataDownloaded = Math.floor(dataDownloaded / (1024));

                        //Get currently running Adaptation and Representation numbers.
                        var lastRep = vm.progressXML.getElementsByTagName("CurrentRep")[0].childNodes[0].nodeValue;
                        var lastAdapt = vm.progressXML.getElementsByTagName("CurrentAdapt")[0].childNodes[0].nodeValue;
                        var lastPeriod = vm.progressXML.getElementsByTagName("CurrentPeriod")[0].childNodes[0].nodeValue;

                        var totalRep =  vm.progressXML.getElementsByTagName("Representation").length;
                        var repNumber = (lastPeriod*100)+(lastAdapt*10)+parseInt(lastRep);

                        var progressText ="";
                        if (lastRep == 1 && lastAdapt == 1 && progressPercent == 0 && dataDownloaded == 0 && dataProcessed == 0) {}//initial state
                        else{
                            //--Edit by Rafay
                            if(repNumber != vm.prevRepNum){
                                vm.prevRepNum = repNumber;
                                vm.repCounter = vm.repCounter + 1;
                            }
                            vm.segmentPercentage = (progressPercent / totalRep) + ( vm.repCounter * (100 / totalRep));
                        }
                        //--Edit not checked
                        if (vm.dynamicsegtimeline) {
                            progressText = "<md-icon aria-label='file' flex='100' class='fileIcon md-default-theme material-icons' md-svg-src='dash_img/file.svg'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' height='100%' width='100%'/></md-icon><font color='red'> Segment timeline for type dynamic is not supported, only MPD will be tested. </font>"
                        }

                        if (vm.segmentListExist) {
                            progressText = "<md-icon aria-label='file' flex='100' class='fileIcon md-default-theme material-icons' md-svg-src='dash_img/file.svg'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' height='100%' width='100%'/></md-icon><font color='red'> SegmentList is not supported, only MPD will be tested. </font>"
                        }
                        //--
                        $document[0].getElementById("par").innerHTML = progressText;
                        $document[0].getElementById('par').style.visibility = 'visible';
                        //update only once
                        if ($document[0].getElementById("profile").innerHTML == "") {
                            vm.profileList = vm.progressXML.getElementsByTagName("Profile")[0].childNodes[0].nodeValue;
                            if (vm.stepData[0].data.dashIf && vm.profileList.search("http://dashif.org/guidelines/dash264") == -1)
                                vm.profileList += ", http://dashif.org/guidelines/dash264";
                            $document[0].getElementById("profile").innerHTML = vm.profileList;
                            $document[0].getElementById('profile').style.display = 'block';
                            $document[0].getElementById('profileHead').style.display = 'block';
                            $document[0].getElementById('profileDivider').style.display = 'block';
                            //--
                        }
                    } catch (e) {
                        ;
                    }
                } else {
                    ;
                }
            }
        };

        vm.pollingProgress = function pollingProgress() {
            vm.xmlDoc_progress = vm.loadXMLDoc("temp/" + vm.dirid + "/progress.xml");

            if (vm.xmlDoc_progress == null) {
                return;
            } else {
                var MPDError = vm.xmlDoc_progress.getElementsByTagName("MPDError");
            }
            if (MPDError.length == 0) {
                return;
            } else {
                vm.totarrstring = MPDError[0].childNodes[0].nodeValue;
            }
            if (vm.totarrstring == 1) { //Check for the error in MPD loading.
                $window.alert("Error loading the MPD, please check the URL.");
                $interval.cancel(vm.pollingTimer);
                vm.finishTest();
                return false;
            }
            //            vm.submitCurrentStep(vm.stepData[0].data);
            $interval.cancel(vm.pollingTimer);
            vm.tree.loadJSONObject({
                id: 0,
                item: [{
                    id: 1,
                    text: "Mpd"
                }]
            });
            vm.profileTimer = $interval(function () {
                vm.profiles();
            }, 50);
        };
        
        // Check the profiles being validated
        vm.profiles = function profiles() {
            vm.xmlDoc_progress = vm.loadXMLDoc("temp/" + vm.dirid + "/progress.xml");
            if (vm.xmlDoc_progress == null) {
                return;
            }
            
            var profiles_elem = vm.xmlDoc_progress.getElementsByTagName("Profile");
            if(profiles_elem.length === 0) {
                return;
            }
            
            var profiles = profiles_elem[0].textContent;
            var hbbtv_profile = 'urn:hbbtv:dash:profile:isoff-live:2012';
            var dvb_profile = ['urn:dvb:dash:profile:dvb-dash:2014', 'urn:dvb:dash:profile:dvb-dash:isoff-ext-live:2014', 'urn:dvb:dash:profile:dvb-dash:isoff-ext-on-demand:2014'];
            var dashif_profile = ['http://dashif.org/guidelines/dash'];
            var dashifll_profile = ['http://www.dashif.org/guidelines/low-latency-live-v5'];
            if(vm.stepData[0].data.hbbTv !== 1 && profiles.includes(hbbtv_profile)) {
                vm.stepData[0].data.hbbTv = 1;
            }
            if(vm.stepData[0].data.dvb2019 !== 1 && vm.stepData[0].data.dvb2018 !== 1) {
                for(var i=0; i<dvb_profile.length; i++) {
                    if(profiles.includes(dvb_profile[i])) {
                        vm.stepData[0].data.dvb2018 = 1;
                        break;
                    }
                }
            }
            if(vm.stepData[0].data.dashIf !== 1 && profiles.includes(dashif_profile)) {
                vm.stepData[0].data.dashIf = 1;
            }
            if(vm.stepData[0].data.dashIfll !== 1 && profiles.includes(dashifll_profile)) {
                vm.stepData[0].data.dashIfll = 1;
            }
            $interval.cancel(vm.profileTimer);
            vm.mpdTimer = $interval(function () {
                vm.mpdProgress();
            }, 110);
        };

        //MPD Validation results
        vm.mpdProgress = function mpdProgress() {
            vm.xmlDoc_mpdresult = vm.loadXMLDoc("temp/" + vm.dirid + "/mpdresult.xml");

            if (vm.xmlDoc_mpdresult == null) {
                return;
            }

            var mpd_node_index_until = vm.xmlDoc_mpdresult.documentElement.childNodes.length;
            if (vm.mpd_node_index == mpd_node_index_until) {
                $interval.cancel(vm.mpdTimer);

                if (!vm.mpdprocessed) {
                    vm.mpdprocessed = true;

                    var currentpath = $window.location.pathname;
                    currentpath = currentpath.substring(0, currentpath.lastIndexOf('/'));
                    //$document[0].getElementById('featureIframe').src = currentpath + '/temp/' + vm.dirid + '/featuretable.html';
                    $window.open(currentpath + '/temp/' + vm.dirid + '/featuretable.html', "featureframe");

                    $document[0].getElementById('waitMessage').style.display = 'none';
                    $document[0].getElementById('listName').style.display = 'block';
                    $document[0].getElementById('insetDivider').style.display = 'block';

                    jq("#featureIframe").on('load',function(){ 
                        document.getElementById('featureIframe').style.height=  (document.getElementById('featureIframe').contentWindow.document.getElementsByTagName('html')[0].offsetHeight+50)+'px';
                    });
                    //--
                    vm.automate(vm.mpdresult_y, vm.mpdresult_x, vm.log_branchName);
                    vm.tree.setItemImage2(vm.mpdresult_x, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                    vm.kidsloc.push(vm.mpdresult_x);
                    vm.urlarray.push("temp/" + vm.dirid + "/mpdreport.html");

                    for (var i = 0; i < 3; i++) {
                        if (vm.xmlDoc_mpdresult.documentElement.childNodes[i].childNodes[0].nodeValue == 'error') {
                            vm.shouldFinishTest = true;
                            break;
                        }
                    }

                    if (vm.shouldFinishTest) {
                        vm.flag = 1; 
                        vm.finishTest();
                        return false;
                    } else {
                        vm.treeTimer = $interval(function () {
                            vm.processmpdresults();
                        }, 100);
                        return;
                    }
                }
            }

            var node = vm.xmlDoc_mpdresult.documentElement.childNodes[vm.mpd_node_index];
            if (!node) {
                $interval.cancel(vm.mpdTimer);
                vm.finishTest();
                return false;
            }
            
            var node_name = node.childNodes[0].parentNode.tagName;
            var node_result = node.childNodes[0].nodeValue;
            var branch_name = '';
            
            switch(node_name) {
                case 'xlink':
                    branch_name = vm.branchName[0];
                    break;
                case 'schema':
                    branch_name = vm.branchName[1];
                    break;
                case 'schematron':
                    branch_name = vm.branchName[2];
                    break;
                case 'dashif':
                    branch_name = vm.branchName[3];
                    break;
                case 'dashif_ll':
                    branch_name = vm.branchName[4];
                    break;
                case 'hbbtv_dvb':
                    branch_name = vm.branchName[5];
                    break;
                default:
                    break;
            }
            
            if(node_result === 'No Result'){
                vm.addToTree(0, branch_name);
                return;
            }
            else if(node_result === 'true'){
                vm.addToTree(1, branch_name);
                vm.mpd_node_index++;
            }
            else if(node_result === 'warning'){
                vm.addToTree(2, branch_name);
                vm.mpd_node_index++;
                vm.log_branchName = "mpd warning log";
            }
            else if(node_result === 'error'){
                vm.addToTree(3, branch_name);
                vm.mpd_node_index++;
                vm.log_branchName = "mpd error log";
            }
            vm.mpdPercentage = vm.mpd_node_index * (100 / mpd_node_index_until);
        };

        vm.addToTree = function addToTree(button, branch_name) {
            var index_value = vm.branch_added[vm.branchName.indexOf(branch_name)];
            
            if (index_value == 0) {
                vm.automate(vm.mpdresult_y, vm.mpdresult_x, branch_name);
                vm.branch_added[vm.branchName.indexOf(branch_name)] = vm.mpdresult_x;
                index_value = vm.mpdresult_x;
                vm.mpdresult_x++;
            }
            
            if (button == 0)
                vm.tree.setItemImage2(index_value, 'ajax-loader.gif', 'ajax-loader.gif', 'ajax-loader.gif');
            else if (button == 1)
                vm.tree.setItemImage2(index_value, 'right.jpg', 'right.jpg', 'right.jpg');
            else if (button == 2)
                vm.tree.setItemImage2(index_value, 'log.jpg', 'log.jpg', 'log.jpg');
            else if (button == 3)
                vm.tree.setItemImage2(index_value, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
        };

        // Form the tree for segment validation
        vm.processmpdresults = function processmpdresults() {
            vm.xmlDoc_progress = vm.loadXMLDoc("temp/" + vm.dirid + "/progress.xml");
            // Check if the MPD is dynamic.
            if (vm.xmlDoc_progress.getElementsByTagName("dynamic").length != 0) {
                if (vm.xmlDoc_progress.getElementsByTagName("dynamic")[0].innerHTML == "true") {
                    if (vm.xmlDoc_progress.getElementsByTagName("SegmentTimeline").length != 0) {
                        vm.dynamicsegtimeline = true;
                    }
                    $document[0].getElementById('dynamicMain').style.display = 'block';
                    $document[0].getElementById("dynamic").href = ('http://vm1.dashif.org/DynamicServiceValidator/?mpdurl='+ vm.stepData[0].data.url);
                }
            }

            // Check if SegmentList exist
            if (vm.xmlDoc_progress.getElementsByTagName("segmentList").length != 0) {
                vm.segmentListExist = true;
            }

            if (vm.dynamicsegtimeline || vm.segmentListExist) {
                vm.finishTest();
                return;
            }

            // Get the number of AdaptationSets, Representations and Periods.
            var Treexml = vm.xmlDoc_progress.getElementsByTagName("Representation");

            if (Treexml.length == 0) {
                vm.flag = 1;    //used for percentage logic
                var complete = vm.xmlDoc_progress.getElementsByTagName("completed");
                if (complete[0].textContent == "true") {
                    vm.finishTest();
                }
                return;
            } else {
                vm.flag = 0;    //used for percentage logic
                //                $document[0].getElementById('segmentProgressContainer').style.visibility = 'visible';
                var Periodxml = vm.xmlDoc_progress.getElementsByTagName("Period");
                var Period_count = Periodxml.length;
                var AdaptRepPeriod_count = Period_count;
                for (var p = 0; p < Period_count; p++) {
                    var Adaptxml = Periodxml[p].getElementsByTagName("Adaptation");
                    var Adapt_count = Adaptxml.length;
                    vm.adaptholder.push(Adapt_count);
                    AdaptRepPeriod_count += ' ' + Adapt_count;
                    for (var v = 0; v < Adapt_count; v++) {
                        AdaptRepPeriod_count += " " + Adaptxml[v].getElementsByTagName("Representation").length;
                    }
                }
            }

            vm.totarr = AdaptRepPeriod_count.split(" ");
            var x = vm.mpdresult_x + 1;
            var y = 1;
            var childno = 1;
            var childno2 = 2;
            var id = x;
            vm.repid = [];
            for (var i = 0; i < vm.totarr[0]; i++) {
                vm.automate(y, x, "Period " + (i + 1));
                vm.perid.push(x);
                vm.tree.setItemImage2(x, 'adapt.jpg', 'adapt.jpg', 'adapt.jpg');

                id++;
                var adaptid_temp = [];
                for (var j = 0; j < vm.totarr[childno]; j++) {
                    vm.automate(x, id, "Adaptationset " + (j + 1));

                    adaptid_temp.push(id);
                    vm.tree.setItemImage2(id, 'adapt.jpg', 'adapt.jpg', 'adapt.jpg');

                    var parentid = id;
                    id++;
                    for (var k = 0; k < vm.totarr[childno2]; k++) {
                        vm.automate(parentid, id, "Representation " + (k + 1));
                        vm.repid.push(adaptid_temp[adaptid_temp.length - 1] + k + 1);
                        id++;

                    }

                    childno2++;
                }

                vm.adaptid.push(adaptid_temp);
                childno = childno2;
                childno2++;
                x = id;
            }

            var period_count = vm.xmlDoc_progress.getElementsByTagName('PeriodCount');
            if (period_count[0].childNodes.length != 0)
                vm.numPeriods = period_count[0].childNodes[0].nodeValue;

            vm.lastloc = x + 1;
            $interval.cancel(vm.treeTimer);
            vm.progressSegmentsTimer = $interval(function () {
                vm.progress()
            }, 100);
            $document[0].getElementById('par').style.visibility = 'visible';
        };
        
        //Segment Validation results
        vm.progress = function progress() {
            vm.xmlDoc_progress = vm.loadXMLDoc("temp/" + vm.dirid + "/progress.xml");
            if (vm.xmlDoc_progress == null) {
                return;
            }
            vm.tree.setItemImage2(vm.repid[vm.counting], 'ajax-loader.gif', 'ajax-loader.gif', 'ajax-loader.gif');  
            
            jq($window).scroll(function(){
                vm.flag1 = 1;
            });
            if((vm.b !== ("#Representation"+ vm.representationid)) && (vm.flag1 === 0)){
                vm.a = "Adaptationset"+vm.adaptationid;
                vm.b = "#Representation"+ (vm.representationid);
                vm.Adaptelem = $document[0].getElementById(vm.a).parentElement.parentElement;
                vm.getNextSibling(vm.Adaptelem, vm.b);    
            }
            
            if (vm.representationid > vm.totarr[vm.hinindex]) {
                var ComparedRepresentations = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("ComparedRepresentations");
                if (vm.stepData[0].data.cmaf == 1 && ComparedRepresentations.length == vm.counter) {
                    return;
                }
                if (vm.stepData[0].data.cmaf && ComparedRepresentations.length != 0) {
                    if (ComparedRepresentations[vm.holder - 1].textContent == "noerror") {
                        vm.tree.setItemImage2(vm.adaptid[vm.periodid - 1][vm.holder - 1], 'right.jpg', 'right.jpg', 'right.jpg');
                        vm.automate(vm.adaptid[vm.periodid - 1][vm.holder - 1], vm.lastloc, "CMAF Compared representations validation success");
                        vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                        vm.lastloc++;
                    } else {
                        vm.tree.setItemImage2(vm.adaptid[vm.periodid - 1][vm.holder - 1], 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                        vm.automate(vm.adaptid[vm.periodid - 1][vm.holder - 1], vm.lastloc, "CMAF Compared representations validation error");
                        vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                        vm.lastloc++;
                        vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                        vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                        vm.kidsloc.push(vm.lastloc);
                        vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Adapt" + (vm.holder - 1) + "_compInfo.html");
                        vm.lastloc++;
                    }
                    vm.counter++;
                }
                vm.entered_cross = false;
                vm.representationid = 1;
                vm.hinindex++;
                vm.adaptationid++;
                vm.holder++;
            } else if (vm.adaptationid > vm.totarr[vm.hinindex2]) {
                vm.holder = 1;
                vm.counter = 0;
                var CrossRepValidation = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("CrossRepresentation");
                var HbbTVDVBComparedRepresentations = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("HbbTVDVBComparedRepresentations");
                var SelectionSet = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("SelectionSet");
                var CmafProfile = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("CMAFProfile");
                var CTAWAVESelectionSet = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("CTAWAVESelectionSet");
                var CTAWAVEProfile = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("CTAWAVEPresentation");
                var DASHIFLLCrossValidation = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("DASHIFLLCrossValidation");
                
                if (CrossRepValidation.length != vm.adaptholder[vm.periodid - 1]) {
                    return;
                }
                if (vm.entered_cross == false) {
                    vm.entered_cross = true;
                    for (var i = 1; i <= CrossRepValidation.length; i++) {
                        if (CrossRepValidation[i - 1].textContent == "noerror") {
                            vm.tree.setItemImage2(vm.adaptid[vm.periodid - 1][i - 1], 'right.jpg', 'right.jpg', 'right.jpg');
                            vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "Cross-representation validation success");
                            vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                            vm.lastloc++;
                        } else {
                            vm.tree.setItemImage2(vm.adaptid[vm.periodid - 1][i - 1], 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                            vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "Cross-representation validation error");
                            vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                            vm.lastloc++;
                            vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "log");
                            vm.tree.setItemImage2(vm.lastloc, 'log.jpg', 'log.jpg', 'log.jpg');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Adapt" + (i - 1) + "_CrossInfofile.html");
                            vm.lastloc++;
                        }
                    }
                }
                
                if (vm.stepData[0].data.hbbTv == 1 || vm.stepData[0].data.dvb2019 == 1 || vm.stepData[0].data.dvb2018 == 1) {
                    if (HbbTVDVBComparedRepresentations.length != vm.adaptholder[vm.periodid - 1]) {
                        return;
                    }
                    if (vm.entered_hbb == false) {
                        vm.entered_hbb = true;
                        for (var i = 1; i <= HbbTVDVBComparedRepresentations.length; i++) {
                            if (HbbTVDVBComparedRepresentations[i - 1].textContent == "noerror") {
                                vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "DVB-HbbTV Compared representations validation success");
                                vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                                vm.lastloc++;
                                vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "log");
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Adapt" + (i - 1) + "_hbbtv_dvb_compInfo.html");
                                vm.lastloc++;
                            } else if (HbbTVDVBComparedRepresentations[i - 1].textContent == "warning") {
                                vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "DVB-HbbTV Compared representations validation warning");
                                vm.tree.setItemImage2(vm.lastloc, 'log.jpg', 'log.jpg', 'log.jpg');
                                vm.lastloc++;
                                vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "log");
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Adapt" + (i - 1) + "_hbbtv_dvb_compInfo.html");
                                vm.lastloc++;
                            } else {
                                vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "DVB-HbbTV Compared representations validation error");
                                vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                                vm.lastloc++;
                                vm.automate(vm.adaptid[vm.periodid - 1][i - 1], vm.lastloc, "log");
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Adapt" + (i - 1) + "_hbbtv_dvb_compInfo.html");
                                vm.lastloc++;
                            }
                        }
                    }
                }
                
                if (vm.stepData[0].data.cmaf == 1) {
                    if (SelectionSet.length == 0 || CmafProfile.length == 0) {
                        return;
                    }
                    if (vm.entered_cmaf == false) {
                        vm.entered_cmaf = true;
                        //Additions for CMAF Selection Set and Presentation Profile.
                        if (SelectionSet.length != 0) {
                            if (SelectionSet[0].textContent == "noerror") {
                                vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CMAF Selection Set");
                                vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                                vm.lastloc++;
                                vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "SelectionSet_infofile.html");
                                vm.lastloc++;
                            } else {
                                vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CMAF Selection Set");
                                vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                                vm.lastloc++;
                                vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "SelectionSet_infofile.html");
                                vm.lastloc++;
                            }
                        }
                        if (CmafProfile.length != 0) {
                            if (CmafProfile[0].textContent == "noerror") {
                                vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CMAF Presentation Profile");
                                vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                                vm.lastloc++;
                                vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Presentation_infofile.html");
                                vm.lastloc++;
                            } else {
                                vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CMAF Presentation Profile");
                                vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                                vm.lastloc++;
                                vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Presentation_infofile.html");
                                vm.lastloc++;
                            }
                        }
                    }
                }
                
                if (vm.stepData[0].data.dashIfll == 1) {
                    if(DASHIFLLCrossValidation.length==0) {
                        return;
                    }

                    if(vm.entered_dashifll == false){
                        vm.entered_dashifll = true;
                        if(DASHIFLLCrossValidation.length!=0) {
                            if (DASHIFLLCrossValidation[0].textContent == "noerror") {
                                vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "DASH-IF LL Cross Validation");
                                vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                                vm.lastloc++;
                                vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "LowLatencyCrossValidation_compInfo.html");
                                vm.lastloc++;
                            } else if(DASHIFLLCrossValidation[0].textContent=="warning"){
                                vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "DASH-IF LL Cross Validation");
                                vm.tree.setItemImage2(vm.lastloc, 'log.jpg','log.jpg','log.jpg');
                                vm.lastloc++;
                                vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "LowLatencyCrossValidation_compInfo.html");
                                vm.lastloc++;
                            } else {
                                vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "DASH-IF LL Cross Validation");
                                vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                                vm.lastloc++;
                                vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                                vm.kidsloc.push(vm.lastloc);
                                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "LowLatencyCrossValidation_compInfo.html");
                                vm.lastloc++;
                            }
                        }
                    }
                }
                
                if (vm.stepData[0].data.ctawave == 1) {
                    if (CTAWAVESelectionSet.length == 0 || CTAWAVEProfile.length == 0) {
                        return;
                    }
                    //Additions for CTA WAVE Selection Set and Presentation Profile.
                    if (CTAWAVESelectionSet.length != 0) {
                        if (CTAWAVESelectionSet[0].textContent == "noerror") {
                            vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CTA WAVE Selection Set");
                            vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "SelectionSet_infofile_ctawave.html");
                            vm.lastloc++;
                        } else if (CTAWAVESelectionSet[0].textContent == "warning") {
                            vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CTA WAVE Selection Set");
                            vm.tree.setItemImage2(vm.lastloc, 'log.jpg', 'log.jpg', 'log.jpg');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "SelectionSet_infofile_ctawave.html");
                            vm.lastloc++;
                        } else {
                            vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CTA WAVE Selection Set");
                            vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "SelectionSet_infofile_ctawave.html");
                            vm.lastloc++;
                        }
                    }
                    if (CTAWAVEProfile.length != 0) {
                        if (CTAWAVEProfile[0].textContent == "noerror") {
                            vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CTA WAVE Presentation Profile");
                            vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Presentation_infofile_ctawave.html");
                            vm.lastloc++;
                        } else if (CTAWAVEProfile[0].textContent == "warning") {
                            vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CTA WAVE Presentation Profile");
                            vm.tree.setItemImage2(vm.lastloc, 'log.jpg', 'log.jpg', 'log.jpg');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Presentation_infofile_ctawave.html");
                            vm.lastloc++;
                        } else {
                            vm.automate(vm.perid[vm.periodid - 1], vm.lastloc, "CTA WAVE Presentation Profile");
                            vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Presentation_infofile_ctawave.html");
                            vm.lastloc++;
                        }
                    }
                }
                
                var BrokenURL=vm.xmlDoc_progress.getElementsByTagName("BrokenURL");
                if(BrokenURL !== null && BrokenURL[0].textContent === "error") {
                    vm.urlarray.push("temp/" + vm.dirid+"/missinglink.html");
                    vm.automate(1,vm.lastloc,"Broken URL list");
                    vm.tree.setItemImage2(vm.lastloc,'404.jpg','404.jpg','404.jpg');
                    vm.lastloc++; 
                }
                
                vm.entered_hbb = false;
                vm.entered_cmaf = false;   
                vm.entered_dashifll = false;
                vm.adaptationid = 1;
                vm.hinindex2 = vm.hinindex;
                vm.hinindex = vm.hinindex2 + 1;
                vm.tree.setItemImage2(vm.perid[vm.periodid - 1], 'right.jpg', 'right.jpg', 'right.jpg');
                vm.periodid++;
            } else if (vm.periodid > vm.totarr[0]) {
                var CTAWAVEBaseline = vm.xmlDoc_progress.getElementsByTagName("CTAWAVESpliceConstraints");
                if (vm.stepData[0].data.ctawave) {
                    if (CTAWAVEBaseline.length == 0) {
                        return;
                    }
                    else {
                        if (CTAWAVEBaseline[0].textContent == "noerror") {
                            vm.automate(1, vm.lastloc, "CTA WAVE Splice Constraint");
                            vm.tree.setItemImage2(vm.lastloc, 'right.jpg', 'right.jpg', 'right.jpg');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "SpliceConstraints_infofile_ctawave.html");
                            vm.lastloc++;
                        } else if (CTAWAVEBaseline[0].textContent == "warning") {
                            vm.automate(1, vm.lastloc, "CTA WAVE Splice Constraint");
                            vm.tree.setItemImage2(vm.lastloc, 'log.jpg', 'log.jpg', 'log.jpg');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "SpliceConstraints_infofile_ctawave.html");
                            vm.lastloc++;
                        } else {
                            vm.automate(1, vm.lastloc, "CTA WAVE Splice Constraint");
                            vm.tree.setItemImage2(vm.lastloc, 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');
                            vm.lastloc++;
                            vm.automate(vm.lastloc - 1, vm.lastloc, "log"); //adaptid[i-1]
                            vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                            vm.kidsloc.push(vm.lastloc);
                            vm.urlarray.push("temp/" + vm.dirid + "/" + "SpliceConstraints_infofile_ctawave.html");
                            vm.lastloc++;
                        }
                    }
                }
                $interval.cancel(vm.progressSegmentsTimer);
                vm.finishTest();
                return;
            } else {
                var AdaptXML = vm.xmlDoc_progress.getElementsByTagName("Period")[vm.periodid - 1].getElementsByTagName("Adaptation");
                if (AdaptXML[vm.adaptationid - 1] == null)
                    return;
                else if (AdaptXML[vm.adaptationid - 1].getElementsByTagName("Representation")[vm.representationid - 1] == null)
                    return;
                else {
                    var RepXML = AdaptXML[vm.adaptationid - 1].getElementsByTagName("Representation")[vm.representationid - 1].textContent;
                    if (RepXML == "")
                        return;
                    vm.representationid++;
                }

                if (RepXML == "noerror")
                    vm.tree.setItemImage2(vm.repid[vm.counting], 'right.jpg', 'right.jpg', 'right.jpg');
                else if (RepXML == "warning")
                    vm.tree.setItemImage2(vm.repid[vm.counting], 'log.jpg', 'log.jpg', 'log.jpg');
                else
                    vm.tree.setItemImage2(vm.repid[vm.counting], 'button_cancel.png', 'button_cancel.png', 'button_cancel.png');

                vm.automate(vm.repid[vm.counting], vm.lastloc, "log");
                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif', 'csh_winstyle/iconText.gif');
                vm.kidsloc.push(vm.lastloc);
                vm.urlarray.push("temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Adapt" + (vm.adaptationid - 1) + "rep" + (vm.representationid - 2) + "log.html");
                vm.lastloc++;

                var location = "temp/" + vm.dirid + "/" + "Period" + (vm.periodid - 1) + "/" + "Adapt" + (vm.adaptationid - 1) + "rep" + (vm.representationid - 2) + "sample_data.xml";
                vm.automate(vm.repid[vm.counting], vm.lastloc, "Calculate bitrate");
                vm.tree.setItemImage2(vm.lastloc, 'csh_winstyle/calculator.gif', 'csh_winstyle/calculator.gif', 'csh_winstyle/calculator.gif');
                vm.kidsloc.push(vm.lastloc);
                vm.urlarray.push("Estimate.php?location=" + location);
                vm.lastloc++;
                vm.counting++;
            }
        };

        //Function in progress for scroll
        vm.getNextSibling = function (elem, selector) {
            // Get the next sibling element
            var sibling = elem.nextElementSibling;
            // If the sibling matches our selector, use it
            // If not, jump to the next sibling and continue the loop
            while (sibling) {
                if (sibling.querySelector(selector)) {
                    sibling.querySelector(selector).scrollIntoView();
                    return;
                }
                sibling = sibling.nextElementSibling;
            }
        };

        //Common functions
        vm.setStatusTextlabel = function setStatusTextlabel(textToSet) {
            var status = textToSet;
            //--Edit by Rafay not checked yet
            if (vm.dynamicsegtimeline) {
                status = status + "<br><md-icon aria-label='file' flex='100' class='fileIcon md-default-theme material-icons' md-svg-src='dash_img/file.svg'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' height='100%' width='100%'/></md-icon><font color='red'> Segment timeline for type dynamic is not supported, only MPD will be tested. </font>";
            }
            if (vm.segmentListExist) {
                status = status + "<br><md-icon aria-label='file' flex='100' class='fileIcon md-default-theme material-icons' md-svg-src='dash_img/file.svg'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' height='100%' width='100%'/></md-icon><font color='red'> SegmentList is not supported, only MPD will be tested. </font>";
            }
            if (vm.ChainedToUrl) {
                status = status + "<br><md-icon aria-label='file' flex='100' class='fileIcon md-default-theme material-icons' md-svg-src='dash_img/file.svg'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' height='100%' width='100%'/></md-icon><font color='red'> Chained-to MPD conformance is opened in new window. </font>";
            }
            //--
            $document[0].getElementById("par").innerHTML = status;
            $document[0].getElementById('par').style.visibility = 'visible';
        };

        vm.loadXMLDoc = function loadXMLDoc(dname) {
            if ($window.XMLHttpRequest) {
                var xhttp = new XMLHttpRequest();
            } else {
                var xhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            xhttp.open("GET", dname, false);
            xhttp.send("");
            return xhttp.responseXML;
        };

        vm.finishTest = function finishTest() {
            $interval.cancel(vm.progressTimer);
            $interval.cancel(vm.progressSegmentsTimer);
            $interval.cancel(vm.mpdTimer);
            $interval.cancel(vm.treeTimer);
            $interval.cancel(vm.profileTimer);
            
            //Open a new window for checking Conformance of Chained-to MPD (if present).
            vm.xmlDoc_progress = vm.loadXMLDoc("temp/" + vm.dirid + "/progress.xml");
            if (vm.xmlDoc_progress != null) {
                var MPDChainingUrl = vm.xmlDoc_progress.getElementsByTagName("MPDChainingURL");

                if (MPDChainingUrl.length != 0) {
                    vm.ChainedToUrl = MPDChainingUrl[0].childNodes[0].nodeValue;
                    $window.open("/start?mpdurl=" + vm.ChainedToUrl);
                }
            }
            
            $document[0].getElementById('customButton').innerHTML = 'Go Back';
            vm.stepData[1].completed=true;
        };

        vm.automate = function automate(y, x, stri) {
            $document[0].getElementById('featureIframe').contentWindow.location.reload();
            $document[0].getElementById('resultBody').style.height= ( $document[0].getElementById('to').offsetHeight + $document[0].getElementById('treepos').offsetHeight + $document[0].getElementById('treeboxbox_tree').offsetHeight+50)+"px";
            vm.tree.insertNewChild(y, x, stri, 0, 0, 0, 0, 'SELECT');
            vm.fixImage(x.valueOf());
            x++;
            y++;
        };

        //Functions in automate
        vm.fixImage = function fixImage(id) {
            switch (vm.tree.getLevel(id)) {
                case 1:
                    vm.tree.setItemImage2(id, 'folderClosed.gif', 'folderOpen.gif', 'folderClosed.gif');
                    break;
                case 2:
                    vm.tree.setItemImage2(id, 'folderClosed.gif', 'folderOpen.gif', 'folderClosed.gif');
                    break;
                case 3:
                    vm.tree.setItemImage2(id, 'folderClosed.gif', 'folderOpen.gif', 'folderClosed.gif');
                    break;
                default:
                    vm.tree.setItemImage2(id, 'leaf.gif', 'folderClosed.gif', 'folderOpen.gif');
                    break;
            }
        };
        
        // To automatically click the RUN button (necessary for regression testing)
        vm.checkAutoRun = function() {
        //    if(vm.stepData[0].data.url && vm.stepData[0].data.autorun) {
        //        vm.submitCurrentStep(vm.stepData[0].data);
        //        vm.submitRun();
        //    }
        }
    }).directive("fileread", [function () {
        return {
            scope: {
                fileread: "="
            },
            link: function (scope, element, attributes) {

                var processDragOverOrEnter;

                processDragOverOrEnter = function (DragEvent) {
                    if (DragEvent !== null) {
                        DragEvent.preventDefault();
                    }
                    DragEvent.dataTransfer.effectAllowed = 'copyMove';
                    //                    DragEvent.dataTransfer.setData('text','anything');
                    return false;
                };

                function handleDropEvent(DragEvent) {

                    if (DragEvent !== null) {
                        DragEvent.preventDefault();
                    }
                    scope.fileread = DragEvent.dataTransfer.files[0];
                    scope.$parent.vm.stepData[0].data.file = scope.fileread;
                    scope.$parent.vm.selected_file_method = "MPD_File_Upload";
                    scope.$parent.vm.stepData[0].data.url = "";
                    document.getElementById("fileTag").style.display = 'block';
                    document.getElementById("triggerUpload").disabled = true;
                    document.getElementById("triggerUpload").style.color = 'gray';
                    document.getElementsByClassName("urlContainer")[0].style.display = 'none';
                    if(jq(window).width() >= 600){
                    }
                }

                element.bind("change", function (changeEvent) {
                    if(changeEvent.target.files !== null) {
                        scope.fileread = changeEvent.target.files[0];
                        scope.$parent.vm.stepData[0].data.file = scope.fileread;
                        scope.$parent.vm.selected_file_method = "MPD_File_Upload";
                        scope.$parent.vm.stepData[0].data.url = "";
                        document.getElementById("fileTag").style.display = 'block';
                        document.getElementById("triggerUpload").disabled = true;
                        document.getElementById("triggerUpload").style.color = 'gray';
                        document.getElementsByClassName("urlContainer")[0].style.display = 'none';
                        if(jq(window).width()>= 600){
                        }
                    }
                });
                element.bind('dragover', processDragOverOrEnter);
                element.bind('dragenter', processDragOverOrEnter);
                element.bind('drop', handleDropEvent);
            }
        }
    }]);

}());
