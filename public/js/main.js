$(function() {
        $("#allday").button().click(function () {
            $startTime = $('#startTime');
            $endTime = $('#endTime');
            
            if ($(this).is(':checked')){
                $startTime.hide();
                $endTime.hide();
            }else{
                $startTime.show();
                $endTime.show(); 
            }
        });


        $('#startTime').timepicker();
        $('#endTime').timepicker();
        
        $("#cancel").button({
            icons: {
                primary: 'ui-icon-cancel'
            },
            text: true
        }).click(function (){
            window.location.replace('/home/all-events' );
            return false;
        });
        
        $("#startDate").datepicker({
            dateFormat: 'yy-mm-dd',
            onSelect: function(dateText, inst){
	        	var startDate = new Date(dateText);
	            var endDate = new Date($('#endDate').val());
	
	            if (startDate > endDate) $(this).datepicker('setDate',endDate);
            }
        });
        
        $("#endDate").datepicker({
            dateFormat: 'yy-mm-dd',
            onSelect: function(dateText, inst){
	            var startDate = new Date($('#startDate').val());
	            var endDate = new Date(dateText); 
	
	            if (startDate > endDate) $(this).datepicker('setDate',startDate);
	        }
        });
        
});