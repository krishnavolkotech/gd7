Drupal.behaviors.sitewide_maintenance_windows = function(context) {
$('.mw-remove').click(function(e) {
   
   var ele_id = $(this).attr('id');
   var index = ele_id.replace('remove-', '');

   $('#edit-remove-item').val(index);
   $('#edit-addmore').trigger('click');
   e.preventDefault();
});
}
