function initialize(){
  var reviewsapp = document.getElementById("reviewsapp");
  reviewsapp.innerHTML = "<" + "div>"
  +	"<" + "div id='content1'>"
  +    "<" + "/div>"
  +    "<" + "table>"
  +          "<" + "tbody id='content2'>"
  +          "<" + "/tbody>"
  +          
      "<" + "/table>"
  +  "<" + "a id='getmorereviews'>"
  + "<" + "/a>"
  +"<" + "/div>"
  +"<" + "a id='viewformlink' onclick=viewreviewform()>Write a Review</a>"  
  +"<" + "div id='review-form'></div>"
  ;
  
  
  
  
  var x = document.getElementById("review-form");
  var createform = document.createElement('form'); // Create New Element Form
  //createform.setAttribute("action", ""); // Setting Action Attribute on Form
  //createform.setAttribute("method", "post"); // Setting Method Attribute on Form
  createform.setAttribute("id", "revform");
  createform.setAttribute("onsubmit", "return false");
  
  x.appendChild(createform);
  
  // var heading = document.createElement('h2'); // Heading of Form
  // heading.innerHTML = "Review";
  // createform.appendChild(heading);
  
  // var line = document.createElement('hr'); // Giving Horizontal Row After Heading
  // createform.appendChild(line);
  
  // var linebreak = document.createElement('br');
  // createform.appendChild(linebreak);
  
  var namelabel = document.createElement('label'); // Create Label for Name Field
  namelabel.innerHTML = "Review Title : "; // Set Field Labels
  createform.appendChild(namelabel);
  
  var inputelement = document.createElement('input'); // Create Input Field for Name
  inputelement.setAttribute("type", "text");
  inputelement.setAttribute("name", "reviewtitle");
  inputelement.setAttribute("id", "reviewtitle");
  createform.appendChild(inputelement);
  
  var linebreak = document.createElement('br');
  createform.appendChild(linebreak);
  
  var emailbreak = document.createElement('br');
  createform.appendChild(emailbreak);
  
  var reviewlabel = document.createElement('label'); // Create Label for E-mail Field
  reviewlabel.innerHTML = "Your Review: ";
  createform.appendChild(reviewlabel);
  
  var reviewelement = document.createElement('input'); // Create Input Field for E-mail
  reviewelement.setAttribute("type", "text");
  reviewelement.setAttribute("name", "review");
  reviewelement.setAttribute("id", "review");
  createform.appendChild(reviewelement);
  
  var emailbreak = document.createElement('br');
  createform.appendChild(emailbreak);
  
  
  var emailbreak = document.createElement('br');
  createform.appendChild(emailbreak);
  
  var spanelement = document.createElement('span');
  spanelement.setAttribute("name", "stars");
  spanelement.innerHTML = "<" + "div id='star-input'" + ">" +
        "<" + "span class='fa fa-star' onclick='starselect(this.id)' id='1001'></span>"
       + "<" + "span class='fa fa-star' onclick='starselect(this.id)' id='1002'></span>" 
      +  "<" + "span class='fa fa-star' onclick='starselect(this.id)' id='1003'></span>"
     +  "<" + "span class='fa fa-star' onclick='starselect(this.id)' id='1004'></span>"
    +   "<" + "span class='fa fa-star' onclick='starselect(this.id)' id='1005'></span>"
  + "<"+"/div>"
         ;
  
  createform.appendChild(spanelement);
  var messagebreak = document.createElement('br');
  createform.appendChild(messagebreak);
  var submitelement = document.createElement('input'); // Append Submit Button
  submitelement.setAttribute("type", "submit");
  //submitelement.setAttribute("name", "submit");
  submitelement.setAttribute("value", "Submit");
  submitelement.setAttribute("onclick", "submitReviewForm()");
  createform.appendChild(submitelement);
  reviewbegin = 0;
  numrows = 1;
  document.getElementById("hiddenid").style.display = "none";
  document.getElementById("revform").style.display = "none";
}


function viewreviewform(){
  document.getElementById("revform").style.display = "block";
  document.getElementById("viewformlink").style.display = "none";
}  

function paginateSetting() {
  var rev_req = new XMLHttpRequest();
  // POST to httpbin which returns the POST data as JSON
  rev_req.open('GET', 'https://shopify.infoshore.biz/aac/api/resources/reviews/fetchpagination.php?shop='+Shopify.shop, true);
  rev_req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  rev_req.send();
  rev_req.onreadystatechange = function() {
    if (this.readyState === 4 && this.status === 200) {      
      var rev_paginate_settings = this.responseText;
      //console.log(rev_paginate_settings);
      rev_paginate_settings = JSON.parse(rev_paginate_settings);
      numrows = rev_paginate_settings[0].paginatereviews;
      review_enable = rev_paginate_settings[0].showreviews;
      if(review_enable==1) {
        reviewsPagination();
      }       
    } else {      
    
    }
  }
}

function starselect(id) {
  starcount = 0;  
  var x= document.getElementById(id);     
  for(i=1001; i<=x.id; i++) {
    starcount++;    
    document.getElementById(i).className += "fa fa-star checked";        
  }
      		
  for(i=1005; i>1000+starcount; i--) {   
    document.getElementById(i).className += "fa fa-star";
  }  
}


function getMoreReviews() {
  $(document).ready(function(){      
    var rowsShown = numrows;
    var rowsTotal = j;
    var numPages = rowsTotal/rowsShown;
    for(i = 0;i < numPages;i++) {
      var pageNum = i + 1;
      var pages = document.getElementById("getmorereviews");
      pages.innerHTML += '<a onclick=callmorereview(this) rel="'+i+'">'+pageNum+'</a> ';	
    }
  });
}

function callmorereview(x) {
  var i = x.innerHTML; 
  reviewbegin = ((i-1)*numrows);
  reviewsPagination();   
}

function showReviews() {
  var cur_product_id = document.getElementById("productid").value;
  var display1 = document.getElementById("content1");
  var averagestars = document.getElementById("average-stars");
  averagestars.innerHTML="";
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.open("GET", "https://shopify.infoshore.biz/aac/api/resources/reviews/fetchaveragestar.php?product_id="+cur_product_id);
  xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xmlhttp.send();
  xmlhttp.onreadystatechange = function() {
    if (this.readyState === 4 && this.status === 200) {       
      var x = this.responseText;
      mydata = JSON.parse(x);   
      j = mydata.length;
      var clear = document.getElementById("getmorereviews");
   	  clear.innerHTML="";
      var totalstar = 0;
      var avgstar = 0;
      for(i=0; i<j; i++) {
        totalstar = totalstar + parseInt(mydata[i].rating);
      }
	    avgstar=totalstar/j;
      display1.innerHTML = "<h3>Customer Reviews</h3>" ;
      for(i=1; i<avgstar;i++) {
        averagestars.innerHTML = averagestars.innerHTML +  "<span class='fa fa-star checked'></span>";
      }
      for(i=avgstar; i<=5; i++) {
        averagestars.innerHTML = averagestars.innerHTML +  "<span class='fa fa-star'></span>";
      }
      averagestars.innerHTML = averagestars.innerHTML +  "<span> Based on " + j + " Reviews</span>";
      settings(); 
      getMoreReviews();
    } 
  }
}

function reviewsPagination() {
  var cur_product_id = document.getElementById("productid").value;
  var display2 = document.getElementById("content2");
  display2.innerHTML = "";
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.open("GET", "https://shopify.infoshore.biz/aac/api/resources/reviews/fetchreviews.php?product_id="+cur_product_id + "&numrows=" +numrows + "&reviewbegin=" +reviewbegin);  
  xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xmlhttp.send();
  xmlhttp.onreadystatechange = function() {
    if (this.readyState === 4 && this.status === 200) {
      var x = this.responseText;
      mydata2 = JSON.parse(x); 
      j2 = mydata2.length;
         
      for(i=0; i<j2; i++) {
        var starprint = "" ;
        var date = Date.parse(mydata2[i].reviewDate);
        for(k=1; k<=mydata2[i].rating; k++) {
          starprint = starprint + "<span class='fa fa-star checked'></span>";
        }

        for(k=mydata2[i].rating; k<5; k++) {
          starprint = starprint +  "<span class='fa fa-star'></span>";
        }
        display2.innerHTML += "<tr><td class = 'review_row'>" + starprint + "<br/><br/><b>"  + mydata2[i].reviewTitle + "</b><br/><span style='color:#808080'><i>" + mydata2[i].authorName + " on " + mydata2[i].reviewDate + "</i></span><br/><br/>" + mydata2[i].reviewDetails + "<br/><br/></td></tr>" ;
      }
    }     
	}
  showReviews();
}

function settings() {
  var setting_request = new XMLHttpRequest();
  // POST to httpbin which returns the POST data as JSON
  setting_request.open('GET', 'https://shopify.infoshore.biz/aac/api/resources/reviews/fetchcustomization.php', true);
	setting_request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  setting_request.send();
  setting_request.onreadystatechange = function() {
    if (this.readyState === 4 && this.status === 200) {
      console.log('yes review123 is running');
      var rev_settings = this.responseText;       
      var rev_settings = JSON.parse(rev_settings);        
      var star_icon_color = rev_settings[0].starcolorreviews;
      var border_color = rev_settings[0].bordercolorreviews;       
      var padding = rev_settings[0].paddingreviews;             
      var setc = document.getElementsByClassName('checked');
       
      for(i = 0; i < setc.length; i++) {         
   		  setc[i].style.color = star_icon_color;
  	  }
       
      var setc2 = document.getElementsByClassName('review_row');
       
      for(i = 0; i < setc2.length; i++)  {         
   		  setc2[i].style.border = '1px solid ' + border_color;
        setc2[i].style.padding = padding + 'px 14px';
  	  }       
       
      var setc3 = document.getElementsByClassName('review_row');
       
      for(i = 0; i < setc3.length; i++) {         
   		  setc3[i].style.border = '1px solid ' + border_color;
  	  }        
    } else {
      
    }
  }
}

function submitReviewForm() {
  var current_product_id = document.getElementById("productid").value;
  var current_customer_name = document.getElementById("customername").value;
  var shopdomain = document.getElementById("shopdomain").value;
  var review = document.getElementById("review").value;
  var reviewtitle = document.getElementById("reviewtitle").value;
  if(current_customer_name=="") {
    current_customer_name="Anonymus";
  }
  
  var request = new XMLHttpRequest();
  
  request.open('GET', 'https://shopify.infoshore.biz/aac/api/resources/reviews/addreview.php?product_id=' + current_product_id + '&shopdomain=' + shopdomain + '&review=' + review + '&title='+ reviewtitle +'&customername='+current_customer_name+'&star='+starcount, true);
	request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  request.send();

  request.onreadystatechange = function() {
    if (this.readyState === 4 && this.status === 200) {       
       var x = this.responseText;       
       var j_floor = Math.floor(j);       
       reviewbegin = j_floor;       
       reviewsPagination();           
       var clear = document.getElementById("getmorereviews");
       clear.innerHTML="";
    } else {
      //alert('review submission failed!!')
    }
  }   
}

$(document).ready(function(){  
  initialize();    
  paginateSetting();
     });