(function() {
    angular.module('ConformanceSoftware').controller('startController',function($scope, $document, $mdSidenav, $interval, ngIntroService){
        $scope.IntroOptions ={
            steps:[
                {
                    element: '#step1',
                    intro: "Menu!",
                    position:'right'
                },
                {
                    element: '#step3',
                    intro: 'write url or drop a file, you can also upload file.',
                    position: 'bottom'
                },
                {
                    element: '#step4',
                    intro: "007 alert! include your desired profiles.",
                    position: 'bottom'
                },
                {
                    element: '#step5',
                    intro: "Not coming slow...",
                    position: 'bottom'

                },
                {
                    element: '#step6',
                    intro: 'Summury',
                    position:'bottom'
                }
            ],
            showStepNumbers:false,
            showBullets: true,
            exitOnOverlayClick: false,
            exitOnEsc:false,
            nextLabel: '<span style="color: rgb(2,119,189)">Next</span>',
            prevLabel: '<span style="color: rgb(2,119,189)">Previous</span>',
            skipLabel: '<span style="color: rgb(2,119,189)">Exit</span>',
            doneLabel: '<span style="color: rgb(2,119,189)"><strong>Now you are good to go!</strong></span>',
            disableInteraction:false,
            scrollToElement: true,
            scrollTo:"tooltip"
        };

        $scope.BeforeChangeEvent= function(){
            if(ngIntroService.intro._currentStep=="0"){
            }
            else if(ngIntroService.intro._currentStep=="2"){
            }                                   
            else if(ngIntroService.intro._currentStep=="3"){
            }
            else if(ngIntroService.intro._currentStep=="4"){
                if(ngIntroService.intro._direction=="forward")
                {
                    $document[0].getElementById("step5").disabled=false;
                    $document[0].getElementById("step5").click();
                    setTimeout(function(){
                    },7000);
                }
            }
            else if(ngIntroService.intro._currentStep=="5"){
                $document[0].getElementById("run").click();
                setTimeout(function(){
                },5000);
            }
        };

        $scope.AfterChangeEvent= function(){
            if(ngIntroService.intro._currentStep=="0"){
            }
            else if(ngIntroService.intro._currentStep=="1"){

            }
            else if(ngIntroService.intro._currentStep=="2"){

            }
            else if(ngIntroService.intro._currentStep=="3"){
            }
            else if(ngIntroService.intro._currentStep=="4"){
            }
            else if(ngIntroService.intro._currentStep=="5"){
            }
        };

        $scope.CompletedEvent = function(){
            localStorage.setItem('EventTour', 'Completed');
        };

        $scope.ExitEvent= function(){
            localStorage.setItem('EventTour', 'Completed');
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
    });
}());
