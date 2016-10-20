$(function()
{
	ITEM_ID = $('#threadid').val();

    if (ATTACH_ACCESS_KEY != '' && $('.aw-upload-box').length)
    {
    	var fileupload = new FileUpload('file', '.aw-editor-box .aw-upload-box .btn', '.aw-editor-box .aw-upload-box .upload-container', G_BASE_URL + '/group/ajax/attach_upload/id-thread__attach_access_key-' + ATTACH_ACCESS_KEY)
    }
    
    if (ITEM_ID && G_UPLOAD_ENABLE == 'Y' && ATTACH_ACCESS_KEY != '')
    {
        if ($(".aw-upload-box .upload-list").length) {
            $.post(G_BASE_URL + '/group/ajax/attach_edit_list/', 'threadid=' + ITEM_ID, function (data) {
                if (data['err']) {
                    return false;
                } else {
                    $.each(data['rsm']['attachs'], function (i, v) {
                    	console.log(v);
                        fileupload.setFileList(v);
                    });
                }
            }, 'json');
        }
    } 
});