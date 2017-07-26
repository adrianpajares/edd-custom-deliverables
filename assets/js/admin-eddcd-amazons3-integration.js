jQuery(document).ready(function ($) {

	$('body').on('click', '.eddcd_upload_file_button', function(e) {

		window.edd_fileurl = $(this).parent().prev().find('input');
		window.edd_filename = $(this).parent().parent().parent().prev().find('input');

	});

});
