/*! publish-to-twitter - v1.1.0 - Copyright (c) 2014 */
!function(a,b){a.document;b(".ptt-add-another").on("click",function(){var a=b("#ptt-twitter-category-pairing-clone").clone(),c=b("#ptt-twitter-category-pairings");a.css({visibility:"visible",height:""}).appendTo(c)}),b(".ptt-delete").on("click",function(){var a=b(this),c=a.parents(".ptt-twitter-category-pairing");return c.fadeOut("fast",function(){c.remove()}),!1}),b(".ptt-chosen-terms").select2({formatNoMatches:function(){return"No terms match"},multiple:!0,minimumInputLength:2,ajax:{url:a.ajaxurl,dataType:"json",data:function(a){return{q:a,action:"ptt-select",limit:5}},results:function(a){return a}},initSelection:function(a,c){var d=[];b(a.val().split(",")).each(function(){var a=this.split(":");d.push({id:a[0]+":"+a[1],text:a[2]})}),c(d)}}),b(".ptt-chosen-accounts").select2({formatNoMatches:"No accounts match"})}(this,jQuery);