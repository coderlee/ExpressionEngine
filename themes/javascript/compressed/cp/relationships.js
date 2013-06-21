/*!
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		EllisLab Dev Team
 * @copyright	Copyright (c) 2003 - 2013, EllisLab, Inc.
 * @license		http://ellislab.com/expressionengine/user-guide/license.html
 * @link		http://ellislab.com
 * @since		Version 2.6
 * @filesource
 */

(function(d){function h(a){this.root=d(a).find(".multiselect");this.active=d(a).find(".multiselect-active");this.searchField=d(a).find(".multiselect-filter input");this.activeMap={};this.listItems=this.root.find("li");this.cache=_.map(this.root.find("label"),function(a,c){return d(a).text()});this.createItem=_.template(this.active.data("template"));this.defaultList=_.object(_.range(this.listItems.length),_.map(this.listItems,d));this.init()}h.prototype={init:function(){this._checkScrollBars();this._disallowClickSelection();
this._bindSelectToClick();this._bindDeselectToRemove();this._bindAddActiveOnSelect();this._bindScrollToActiveClick();this._bindSortable();this._setupFilter()},_checkScrollBars:function(){this.root.prop("scrollHeight")<=this.root.prop("clientHeight")&&this.root.removeClass("force-scroll");this.active.prop("scrollHeight")<=this.active.prop("clientHeight")&&this.active.removeClass("force-scroll")},_bindSelectToClick:function(){var a=this;this.root.on("click","li",function(b){b.preventDefault();b=d(this).find(":checkbox");
wasChecked=b.is(":checked");d(this).toggleClass("selected",!wasChecked);b.attr("checked",!wasChecked);_.defer(d.proxy(a.searchField,"focus"))})},_bindDeselectToRemove:function(){var a=this;this.active.on("click",".remove-item",function(){var b=a._index(this);a.listItems.eq(b).trigger("click");return!1})},_bindScrollToActiveClick:function(){var a=this;this.active.on("click","li",function(){var b=a._index(this),b=a.listItems.eq(b),c;c=a.root.offset().top-a.root.scrollTop();a.root.animate({scrollTop:b.offset().top-
c})})},_bindAddActiveOnSelect:function(){var a=this,b;b={activeLength:0,moveOver:function(b){var c=d(a.createItem({title:a.cache[b]}));c.data("list-index",b);a.active.find("ul").append(c);a.activeMap[b]=c;this.activeLength++;a.defaultList[b].find("input:text").val(this.activeLength)},moveBack:function(b){if(a.defaultList[b].find("input:text").val()<this.activeLength){var c=a.activeMap[b],f=c.index()+1;c.nextAll().each(function(){a.defaultList[a._index(this)].find("input:text").val(f++)})}this.activeLength--;
a.defaultList[b].find("input:text").val(0);a.activeMap[b].remove();delete a.activeMap[b]}};a.active.addClass("force-scroll");var c=_.map(this.root.find(":checked"),function(a,b){var c=d(a).closest("li"),g=c.find("input:text");return[c,+g.val()]}),c=_.sortBy(c,function(a){return a[1]});_.each(c,function(c,e){var f=a.listItems.index(c[0]);b.moveOver(f)});a._checkScrollBars();this.root.on("click.moveover","li",function(c){a.active.addClass("force-scroll");c=d(this).find(":checkbox");var e=a.listItems.index(this);
c.is(":checked")?b.moveOver(e):b.moveBack(e);a._checkScrollBars()})},_bindSortable:function(){var a=this,b,c;c=function(b){return+a.defaultList[a._index(b)].find("input:text").val()};this.active.find("ul").sortable({axis:"y",start:function(a,e){b=c(e.item)},update:function(c,e){var f=e.item,d=f.index()+1,k;d!=b&&(d<b?(k=d,f=f.nextAll().andSelf()):(k=1,f=f.prevAll().andSelf()),f.each(function(){a.defaultList[a._index(this)].find("input:text").val(k++)}))}})},_index:function(a){return d(a).closest("li").data("list-index")},
_setupFilter:function(){var a=this.root.find("ul");this.searchField.keydown(function(a){13==a.keyCode&&a.preventDefault()});this.searchField.on("interact",_.debounce(d.proxy(this,"_filterResults",this.defaultList,a),100))},_filterResults:function(a,b,c){this.root.addClass("force-scroll");c=c.target.value.toLowerCase();var d=c.length;b.find("li").detach();if(0==d)return _.each(a,function(a){a[0].style.display=""}),this._insertInOrder(b,a),this._checkScrollBars();var e=_.map(this.cache,_.partial(this._scoreString,
c));_.each(a,function(a,b){a[0].style.display=0===e[b]?"none":""});c=_.sortBy(_.range(this.cache.length),function(a){return-e[a]});this._insertInOrder(b,a,c);this._checkScrollBars()},_scoreString:function(a,b){var c=0,d=1,e=a.length;b=b.toLowerCase();b[0]==a[0]&&(c+=1);for(var f=0;f<e;f++){var g=b.indexOf(a.charAt(f).toLowerCase());switch(g){case -1:return 0;case 0:c+=0.6;f==d&&(c+=0.4);break;default:c+=0.4/d}d+=g;b=b.substr(g+1)}return c/e*(e/d)},_insertInOrder:function(a,b,c){c||(c=_.range(_.size(b)));
var d=document.createElement("ul");_.each(c,function(a){d.appendChild(b[a][0])});var e=_.toArray(d.childNodes),f=e.length,g=0;(function l(){a.append(e.slice(g++,100));g<f&&_.defer(l)})()},_disallowClickSelection:function(){var a=0,b=this;this.root.dblclick(b._deselect).click(function(){a++;_.debounce(function(){a=0},500);2<=a&&b._deselect()})},_deselect:function(){window.getSelection?window.getSelection().removeAllRanges():document.selection&&document.selection.empty()}};EE.setup_relationship_field=
function(a){if("f"==a[0])return new h(d("#sub_hold_"+a.replace("id_","")));Grid.bind("relationship","display",function(a){new h(a)})}})(jQuery);
