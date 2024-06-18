
function settings2()
{	
  	var setting_request = new XMLHttpRequest();  	
    setting_request.open('GET', 'https://shopify.infoshore.biz/aac/api/resources/reviews/fetchcustomization.php', true);
	setting_request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");        
    setting_request.send();    
	setting_request.onreadystatechange = function() {
     if (this.readyState === 4 && this.status === 200) {       
       var rev_settings = this.responseText;              
       var rev_settings = JSON.parse(rev_settings);        
       star_icon_color = rev_settings[0].starcolorreviews;                            
       var setc = document.getElementsByClassName('checked');       
       for(i = 0; i < setc.length; i++) 
       {         
   		 setc[i].style.color = star_icon_color;
  	   }          
     } 
     
     else {      
       };
   }
}
var coll_prod_count = document.getElementById("coll_prod_count").value;
var productIdList=new Array();  
for(i=0; i<coll_prod_count; i++)
{  
    if(document.getElementsByClassName("productid")[i] != null){
        var product_id = document.getElementsByClassName("productid")[i].value;
        productIdList.push(product_id);  
    }
}
var productListJson = JSON.stringify(productIdList);  
var averagestars = document.getElementById("avgrating"+product_id);
var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("GET", "https://shopify.infoshore.biz/aac/api/resources/reviews/fetchaveragestarscollection.php?product_id="+productListJson);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.send();
    xmlhttp.onreadystatechange = function() 
    {
       if (this.readyState === 4 && this.status === 200)
       {                  
         var x = this.responseText;
         y = JSON.parse(x);   
         for(i=0; i<y.length; i++)
         {
             if (y[i].length > 0) {
               var totalstar=0;
               var avgstar=0;    
               if(document.getElementById("avgrating"+y[i][0].product_asin) != null){ 
                   var averagestars = document.getElementById("avgrating"+y[i][0].product_asin); 
               }
              
               for(j=0; j<y[i].length; j++)
               {
                 totalstar = totalstar + y[i][j].rating;               
                 avgstar=totalstar/y[i].length;               
               }            
               for(j=0; j<avgstar;j++)
               {       	
                 averagestars.innerHTML = averagestars.innerHTML +  "<span class='fa fa-star checked'></span>";
               }             
               for(j=avgstar; j<=4; j++)
               {       
                    averagestars.innerHTML = averagestars.innerHTML +  "<span class='fa fa-star'></span>";
               }
             }
         }                 
       }          
    }  
