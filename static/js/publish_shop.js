$(function()
{
	if ($('#itemid').length)
	{
		ITEM_ID = $('#itemid').val();
	}
    else
    {
        ITEM_ID = '';
    }

    // 判断是否开启ck编辑器
	if (G_ADVANCED_EDITOR_ENABLE == 'Y')
	{
		// 初始化编辑器
		var editor = CKEDITOR.replace( 'wmd-input' );
	}

    if (ATTACH_ACCESS_KEY != '' && $('.aw-upload-box').length)
    {
    	if (G_ADVANCED_EDITOR_ENABLE == 'Y')
		{
	    	var fileupload = new FileUpload('file', '.aw-editor-box .aw-upload-box .btn', '.aw-editor-box .aw-upload-box .upload-container', G_BASE_URL + '/shop/ajax/attach_upload/id-shop__attach_access_key-' + ATTACH_ACCESS_KEY, {
					'editor' : editor
				});
	    }
	    else {
	    	var fileupload = new FileUpload('file', '.aw-editor-box .aw-upload-box .btn', '.aw-editor-box .aw-upload-box .upload-container', G_BASE_URL + '/shop/ajax/attach_upload/id-shop__attach_access_key-' + ATTACH_ACCESS_KEY, {
					'editor' : $('.wmd-input')
				});
	    }
    }

    if (ITEM_ID && G_UPLOAD_ENABLE == 'Y' && ATTACH_ACCESS_KEY != '')
    {
        if ($(".aw-upload-box .upload-list").length) {
            $.post(G_BASE_URL + '/shop/ajax/attach_edit_list/', 'itemid=' + ITEM_ID, function (data) {
                if (data['err']) {
                    return false;
                } else {
                    $.each(data['rsm']['attachs'], function (i, v) {
                        fileupload.setFileList(v);
                    });
                }
            }, 'json');
        }
    }

});
