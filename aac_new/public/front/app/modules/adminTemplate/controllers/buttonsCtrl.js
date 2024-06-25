'use strict';

var app = angular.module('ng-laravel',['angular-ladda']);
app.controller('buttonsCtrl',function($scope,$timeout){
    $scope.loading1 = false;
    $scope.clickBtn1 = function() {
        $scope.loading1 = true;
        $timeout(stop1 , 1000);
    };
    function stop1() {
        $scope.loading1 = false;
    }

    $scope.loading2 = false;
    $scope.clickBtn2 = function() {
        $scope.loading2 = true;
        $timeout(stop2 , 2000);
    };
    function stop2() {
        $scope.loading2 = false;
    }


    $scope.loading3 = false;
    $scope.clickBtn3 = function() {
        $scope.loading3 = true;
        $timeout(stop3 , 2000);
    };
    function stop3() {
        $scope.loading3 = false;
    }

    $scope.loading4 = false;
    $scope.clickBtn4 = function() {
        $scope.loading4 = true;
        $timeout(stop4 , 2000);
    };
    function stop4() {
        $scope.loading4 = false;
    }

    $scope.loading5 = false;
    $scope.clickBtn5 = function() {
        $scope.loading5 = true;
        $timeout(stop5 , 2000);
    };
    function stop5() {
        $scope.loading5 = false;
    }


    $scope.loading6 = false;
    $scope.clickBtn6 = function() {
        $scope.loading6 = true;
        $timeout(stop6 , 2000);
    };
    function stop6() {
        $scope.loading6 = false;
    }


    $scope.loading7 = false;
    $scope.clickBtn7 = function() {
        $scope.loading7 = true;
        $timeout(stop7 , 2000);
    };
    function stop7() {
        $scope.loading7 = false;
    }


    $scope.loading8 = false;
    $scope.clickBtn8 = function() {
        $scope.loading8 = true;
        $timeout(stop8 , 2000);
    };
    function stop8() {
        $scope.loading8 = false;
    }

    $scope.loading9 = false;
    $scope.clickBtn9 = function() {
        $scope.loading9 = true;
        $timeout(stop9 , 2000);
    };
    function stop9() {
        $scope.loading9 = false;
    }

    $scope.loading10 = false;
    $scope.clickBtn10 = function() {
        $scope.loading10 = true;
        $timeout(stop10 , 2000);
    };
    function stop10() {
        $scope.loading10 = false;
    }

    $scope.loading11 = false;
    $scope.clickBtn11 = function() {
        $scope.loading11 = true;
        $timeout(inc115, 500);
        $timeout(inc1110, 1000);
        $timeout(inc1115, 1500);
        $timeout(inc1120, 2000);

        function inc115() {
            $scope.loading11= 0.1;
        }
        function inc1110() {
            $scope.loading11 += 0.1;
        }
        function inc1115() {
            $scope.loading11 += 0.1;
        }
        function inc1120() {
            $scope.loading11 = false;
        }
    };




    $scope.loading12 = false;
    $scope.clickBtn12 = function() {
        $scope.loading12 = true;
        $timeout(inc125, 500);
        $timeout(inc1210, 1000);
        $timeout(inc1215, 1500);
        $timeout(inc1220, 2000);
    };

    function inc125() {
        $scope.loading12= 0.1;
    }
    function inc1210() {
        $scope.loading12 += 0.1;
    }
    function inc1215() {
        $scope.loading12 += 0.1;
    }
    function inc1220() {
        $scope.loading12 = false;
    }



    $scope.loading13 = false;
    $scope.clickBtn13 = function() {
        $scope.loading13 = true;
        $timeout(inc500, 500);
        $timeout(inc1000, 1000);
        $timeout(inc1500, 1500);
        $timeout(inc2000, 2000);
    };

    function inc500() {
        $scope.loading13= 0.1;
    }
    function inc1000() {
        $scope.loading13 += 0.1;
    }
    function inc1500() {
        $scope.loading13 += 0.1;
    }
    function inc2000() {
        $scope.loading13 = false;
    }




    $scope.loading14 = false;
    $scope.clickBtn14 = function() {
        $scope.loading14 = true;
        $timeout(stop14 , 2000);
    };
    function stop14() {
        $scope.loading14 = false;
    }


    $scope.loading15 = false;
    $scope.clickBtn15 = function() {
        $scope.loading15 = true;
        $timeout(stop15 , 2000);
    };
    function stop15() {
        $scope.loading15 = false;
    }


    $scope.loading16 = false;
    $scope.clickBtn16 = function() {
        $scope.loading16 = true;
        $timeout(stop16 , 2000);
    };
    function stop16() {
        $scope.loading16 = false;
    }


    angular.module('ladda', ['angular-ladda'])
        .controller('DemoCtrl', function($scope, $timeout) {
            $scope.loading = {};
            $scope.clickBtn = function(style) {
                $scope.loading[style.replace('-','_')] = true;
                $timeout(function() {
                    $scope.loading[style.replace('-','_')] = false;
                }, 2000);
            };
            $scope.clickProgressBtn = function(style) {
                $scope.loading[style.replace('-','_') + "_progress"] = true;
                $timeout(function() {
                    $scope.loading[style.replace('-','_') + "_progress"] = 0.1;
                }, 500);
                $timeout(function() {
                    $scope.loading[style.replace('-','_') + "_progress"] += 0.1;
                }, 1000);
                $timeout(function() {
                    $scope.loading[style.replace('-','_') + "_progress"] += 0.1;
                }, 1500);
                $timeout(function() {
                    $scope.loading[style.replace('-','_') + "_progress"] = false;
                }, 2000);
            };
        });


})