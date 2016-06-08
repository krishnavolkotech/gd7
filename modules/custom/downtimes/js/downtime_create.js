if(Drupal.jsEnabled) {
  $(document).ready(function() {
     $('.description2').html('');
/*     enddate_toggle();
     var selector_checked = $("input[@class=form-radio]:checked").val();	
     if(selector_checked==1){
       var value=$('#edit-enddate-planned').val();
       if(value==''){  	
	 $('#edit-enddate-planned').addClass("error");
       }
     }
*/
		    });
 }
function enddate_toggle(){
  var selector_checked = $("input[@class=form-radio]:checked").val();
  $('#edit-enddate-planned').removeClass("error");
  if(selector_checked==1){  
    $('#edit-enddate-planned').addClass("required");
    $('label').each(function(i){ if ($(this).attr('for')== "edit-enddate-planned"){
			var str=$(this).text();
			$(this).html(str+"<span title='This field is required.' class='form-required'>*</span>"); }});
  }else{
    $('#edit-enddate-planned').removeClass("required");	
    $('label').each(function(i){ 
		       if ($(this).attr('for')== "edit-enddate-planned"){ 
			 var str=$(this).text();
			 var org_str=str.replace('*',' ');
			 $(this).html(org_str);
		       }
		    });
  }
  
}
