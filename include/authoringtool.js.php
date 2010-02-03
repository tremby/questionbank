<?php
include "constants.php";
header("Content-Type: text/javascript");
?>
qtitinymceoptions = {
	script_url: "<?php echo SITEROOT_WEB; ?>include/tiny_mce/tiny_mce.js",
	theme: "advanced",
	plugins: "safari,table,iespell,inlinepopups,contextmenu,paste,xhtmlxtras,template",
	theme_advanced_buttons1: "bold,italic,|,sub,sup,|,formatselect",
	theme_advanced_buttons2: "cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,link,unlink",
	theme_advanced_buttons3: "tablecontrols",
	theme_advanced_buttons4: "undo,redo,|,hr,charmap,image,template,|,iespell,removeformat,cleanup,code",
	theme_advanced_toolbar_location: "external",
	theme_advanced_statusbar_location: "bottom",
	valid_elements: "@[id|class|lang|label],"
		+ "a[href|type],"
		+ "strong/b,em/i,"
		+ "#p,-ol,-ul,-li,br,"
		+ "img[!src|!alt=|longdesc|height|width],"
		+ "-sub,-sup,"
		+ "-blockquote[cite],"
		+ "-table[summary],tr,"
		+ "tbody,thead,tfoot,"
		+ "#td[headers|scope|abbr|axis|rowspan|colspan],"
		+ "#th[headers|scope|abbr|axis|rowspan|colspan],"
		+ "caption,-div,"
		+ "-span,-code,-pre,address,-h1,-h2,-h3,-h4,-h5,-h6,hr,"
		+ "dd,dl,dt,cite,abbr,acronym,"
		+ "object[!data|!type|width|height],param[!name|!value|!valuetype|type],"
		+ "col[span],colgroup,"
		+ "dfn,kbd,"
		+ "q[cite],samp,small,"
		+ "tt,var,big",
	entities: "34,quot,38,amp,39,apos,60,lt,62,gt",
	content_css: "<?php echo SITEROOT_WEB; ?>include/tinymce.css",
};
$(document).ready(function() {
	$("textarea.qtitinymce").focus(focustinymce);
});
focustinymce = function() {
	if (typeof tinyMCE != "undefined" && tinyMCE.get($(this).attr("id")))
		return;

	removetinymces();
	qtitinymceoptions.theme_advanced_resizing = $(this).is(".resizable");
	$(this).tinymce(qtitinymceoptions);
};
removetinymces = function() {
	$("textarea.qtitinymce").each(function() {
		if (typeof tinyMCE != "undefined" && tinyMCE.get($(this).attr("id")))
			tinyMCE.execCommand("mceRemoveControl", false, $(this).attr("id"));
	});
};
