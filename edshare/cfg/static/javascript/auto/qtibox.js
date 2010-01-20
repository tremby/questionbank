qtibox_playitem = function(docid) {
	var div = $("qtibox_document_" + docid);
	div.innerHTML = '<iframe src="/cgi/qtibox_playitem?docid=' + docid + '" style="width: 100%; height: 30em;" />';
};
